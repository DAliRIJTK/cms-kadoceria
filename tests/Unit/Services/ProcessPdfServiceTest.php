<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Buku;
use App\Models\Halaman;
use App\Services\ProcessPdfService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcessPdfServiceTest extends TestCase
{
    // Menggunakan RefreshDatabase agar database selalu bersih setiap kali test dijalankan
    use RefreshDatabase;

    public function test_pdf_berhasil_dikonversi_menjadi_gambar_dan_diunggah_ke_s3()
    {
        // 1. PERSIAPAN (Arrange)
        // Kita memalsukan (mock) storage local dan s3 agar file tidak benar-benar terunggah ke AWS
        Storage::fake('local');
        Storage::fake('s3');

        // Buat data buku dummy di database
        $buku = Buku::factory()->create([
            'judul_idn' => 'Buku Petualangan Budi',
            'is_processing' => true,
            'status_konversi' => false,
        ]);

        // Ambil file PDF dummy dari folder fixtures yang sudah Anda siapkan
        $fixturePath = base_path('tests/Fixtures/dummy_2_pages.pdf');
        $pdfPath = 'buku/pdf/dummy.pdf';
        
        // Simpan file dummy ke dalam fake local storage
        Storage::disk('local')->put($pdfPath, file_get_contents($fixturePath));

        // 2. TINDAKAN (Act)
        // Jalankan service konversi PDF
        $service = new ProcessPdfService();
        $service->process($buku, $pdfPath);

        // 3. PEMBUKTIAN (Assert)
        // Refresh model buku untuk mendapatkan data terbaru dari database
        $buku->refresh();

        // Pastikan status buku berubah menjadi sukses diproses
        $this->assertFalse((bool) $buku->is_processing, 'Buku seharusnya sudah tidak dalam status processing');
        $this->assertTrue((bool) $buku->status_konversi, 'Status konversi buku seharusnya true');
        $this->assertNull($buku->local_pdf_path, 'File PDF lokal seharusnya sudah dihapus dari database');

        // Pastikan file PDF asli dihapus dari fake local storage
        Storage::disk('local')->assertMissing($pdfPath);

        // Pastikan ada 2 halaman yang terbuat di database (karena dummy_2_pages.pdf memiliki 2 halaman)
        $halaman = Halaman::where('id_buku', $buku->id_buku)->orderBy('nomor_halaman')->get();
        $this->assertCount(2, $halaman, 'Harus ada 2 halaman yang terbuat di database');

        // Pastikan halaman 1 menjadi cover, dan gambar fisiknya benar-benar ada di fake S3 storage
        $halaman1 = $halaman->firstWhere('nomor_halaman', 1);
        $this->assertEquals($buku->path_cover, $halaman1->path_gambar, 'Path cover buku harus sama dengan path gambar halaman 1');
        Storage::disk('s3')->assertExists($halaman1->path_gambar);

        // Pastikan halaman 2 juga gambar fisiknya ada di fake S3 storage
        $halaman2 = $halaman->firstWhere('nomor_halaman', 2);
        Storage::disk('s3')->assertExists($halaman2->path_gambar);
    }

    public function test_rollback_dan_cleanup_s3_saat_terjadi_kegagalan_database()
    {
        // 1. PERSIAPAN (Arrange)
        Storage::fake('local');
        Storage::fake('s3');

        $buku = Buku::factory()->create([
            'judul_idn' => 'Buku Uji Rollback',
            'is_processing' => true,
            'status_konversi' => false,
        ]);

        $fixturePath = base_path('tests/Fixtures/dummy_2_pages.pdf');
        $pdfPath = 'buku/pdf/dummy_fail.pdf';
        Storage::disk('local')->put($pdfPath, file_get_contents($fixturePath));

        // SABOTASE DATABASE: Kita menggunakan event listener Laravel untuk memaksa
        // Model Halaman selalu melempar Exception saat akan disimpan ke database.
        Halaman::creating(function () {
            throw new \Exception('Simulasi kegagalan database');
        });

        // 2. TINDAKAN (Act)
        $service = new ProcessPdfService();
        $exceptionThrown = false;
        
        try {
            $service->process($buku, $pdfPath);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            // Memastikan error yang tertangkap adalah error sabotase kita
            $this->assertEquals('Simulasi kegagalan database', $e->getMessage());
        }

        // 3. PEMBUKTIAN (Assert)
        
        // Memastikan kode Anda melempar exception ke atas (sesuai komentar di ProcessPdfService)
        $this->assertTrue($exceptionThrown, 'Exception seharusnya dilempar oleh service');

        // Memastikan rollback database berjalan (0 data tersimpan)
        $this->assertDatabaseMissing('halaman', [
            'id_buku' => $buku->id_buku,
        ]);

        // Memastikan cleanup S3 berjalan (Gambar yang telanjur naik, dihapus kembali)
        $bookDir = $buku->slugify($buku->judul_idn);
        $s3Files = Storage::disk('s3')->allFiles('buku/' . $bookDir);
        $this->assertEmpty($s3Files, 'Direktori S3 untuk buku ini seharusnya kosong karena file di-cleanup');

        // Memastikan status is_processing tetap true (karena Job yang bertugas mengubahnya jadi false)
        $buku->refresh();
        $this->assertTrue((bool) $buku->is_processing, 'Buku harus tetap dalam status processing sesuai perancangan service');
    }

    public function test_rollback_hanya_pada_halaman_yang_gagal_dan_halaman_sebelumnya_tetap_aman()
    {
        // 1. PERSIAPAN (Arrange)
        Storage::fake('local');
        Storage::fake('s3');

        $buku = Buku::factory()->create([
            'judul_idn' => 'Buku Partial Fail',
            'is_processing' => true,
            'status_konversi' => false,
        ]);

        $fixturePath = base_path('tests/Fixtures/dummy_2_pages.pdf');
        $pdfPath = 'buku/pdf/dummy_partial_fail.pdf';
        Storage::disk('local')->put($pdfPath, file_get_contents($fixturePath));

        // SABOTASE DATABASE SPESIFIK: 
        // Biarkan halaman 1 sukses, tapi gagalkan saat menyimpan halaman 2
        Halaman::creating(function ($halaman) {
            if ($halaman->nomor_halaman === 2) {
                throw new \Exception('Simulasi kegagalan database pada halaman 2');
            }
        });

        // 2. TINDAKAN (Act)
        $service = new ProcessPdfService();
        $exceptionThrown = false;
        
        try {
            $service->process($buku, $pdfPath);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertEquals('Simulasi kegagalan database pada halaman 2', $e->getMessage());
        }

        // 3. PEMBUKTIAN (Assert)
        $this->assertTrue($exceptionThrown, 'Exception seharusnya dilempar saat halaman 2 gagal');

        // BUKTI 1: Halaman 1 TETAP AMAN di Database dan S3 (Tidak ikut ter-rollback)
        $this->assertDatabaseHas('halaman', [
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 1,
        ]);
        $halaman1 = Halaman::where('id_buku', $buku->id_buku)->where('nomor_halaman', 1)->first();
        Storage::disk('s3')->assertExists($halaman1->path_gambar); // Gambar tetap ada di S3

        // BUKTI 2: Halaman 2 GAGAL TERSIMPAN di Database (Ter-rollback)
        $this->assertDatabaseMissing('halaman', [
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 2,
        ]);
        
        // BUKTI 3: Gambar Halaman 2 DIHAPUS dari S3 (Cleanup S3 HANYA untuk halaman 2)
        $bookDir = $buku->slugify($buku->judul_idn);
        Storage::disk('s3')->assertMissing('buku/' . $bookDir . '/halaman/halaman1.webp'); 
        
        // Pastikan total data di database hanya ada 1 halaman yang lolos
        $this->assertCount(1, Halaman::where('id_buku', $buku->id_buku)->get());
    }

    public function test_sistem_melanjutkan_konversi_dari_halaman_yang_belum_diproses()
    {
        // 1. PERSIAPAN (Arrange)
        Storage::fake('local');
        Storage::fake('s3');

        $buku = Buku::factory()->create([
            'judul_idn' => 'Buku Resume Konversi',
            'is_processing' => true,
            'status_konversi' => false,
        ]);

        // KITA SIMULASIKAN HALAMAN 1 SUDAH BERHASIL TERBUAT SEBELUMNYA
        Halaman::factory()->create([
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 1,
            'path_gambar' => 'buku/buku_resume_konversi/halaman/cover.webp'
        ]);

        $fixturePath = base_path('tests/Fixtures/dummy_2_pages.pdf');
        $pdfPath = 'buku/pdf/dummy_resume.pdf';
        Storage::disk('local')->put($pdfPath, file_get_contents($fixturePath));

        // 2. TINDAKAN (Act)
        $service = new ProcessPdfService();
        $service->process($buku, $pdfPath); // Menjalankan ulang PDF 2 halaman

        // 3. PEMBUKTIAN (Assert)
        // Jika logika startIndex berfungsi, sistem TIDAK akan membuat Halaman 1 lagi
        $halaman = Halaman::where('id_buku', $buku->id_buku)->orderBy('nomor_halaman')->get();
        
        $this->assertCount(2, $halaman, 'Hanya boleh ada 2 halaman, tidak boleh ada duplikasi halaman 1');
        $this->assertEquals(1, $halaman[0]->nomor_halaman);
        $this->assertEquals(2, $halaman[1]->nomor_halaman);
        
        // Memastikan buku telah ditandai selesai
        $buku->refresh();
        $this->assertTrue((bool) $buku->status_konversi);
    }

    public function test_melempar_exception_jika_file_pdf_tidak_ditemukan()
    {
        // 1. PERSIAPAN (Arrange)
        Storage::fake('local');
        
        $buku = Buku::factory()->create();
        $pdfPath = 'buku/pdf/file_hantu.pdf'; 
        // KITA SENGAJA TIDAK MENYIMPAN FILE KE STORAGE

        $service = new ProcessPdfService();
        $exceptionThrown = false;

        // 2. TINDAKAN (Act)
        try {
            $service->process($buku, $pdfPath);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            // 3. PEMBUKTIAN (Assert)
            $this->assertEquals("File PDF tidak ditemukan: {$pdfPath}", $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Service harus melempar exception saat PDF hilang');
    }

    public function test_rollback_database_jika_s3_gagal_menerima_file()
    {
        // 1. PERSIAPAN
        // Simpan instance fake local disk ke dalam variabel sebelum Storage kita bajak
        $localDisk = Storage::fake('local');
        
        $buku = Buku::factory()->create([
            'judul_idn' => 'Buku Uji Gagal S3',
            'is_processing' => true,
            'status_konversi' => false,
        ]);

        $fixturePath = base_path('tests/Fixtures/dummy_1_page.pdf');
        $pdfPath = 'buku/pdf/dummy_s3_fail.pdf';
        
        // Gunakan $localDisk langsung untuk meletakkan file dummy
        $localDisk->put($pdfPath, file_get_contents($fixturePath));

        // SABOTASE S3 & LINDUNGI LOCAL:
        // Kita buat mock khusus untuk menyabotase S3
        $s3Mock = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $s3Mock->shouldReceive('put')->andThrow(new \Exception('S3 Connection Timeout'));
        $s3Mock->shouldReceive('delete')->never(); // Pastikan S3 tidak pernah menghapus file karena file gagal terunggah

        // Beri tahu facade Storage: 
        // "Jika memanggil disk('local'), gunakan fake local disk. Jika disk('s3'), gunakan mock sabotase kita."
        Storage::shouldReceive('disk')->with('local')->andReturn($localDisk);
        Storage::shouldReceive('disk')->with('s3')->andReturn($s3Mock);
        
        // 2. TINDAKAN
        $service = new ProcessPdfService();
        $exceptionThrown = false;
        
        try {
            $service->process($buku, $pdfPath);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertEquals('S3 Connection Timeout', $e->getMessage());
        }

        // 3. PEMBUKTIAN
        $this->assertTrue($exceptionThrown, 'Exception harus dilempar ketika S3 gagal');

        // Pastikan Database di-rollback (0 data Halaman untuk buku ini)
        $this->assertDatabaseMissing('halaman', [
            'id_buku' => $buku->id_buku,
        ]);
        
        // Pastikan is_processing tetap true agar bisa dilempar dan diproses oleh Job
        $buku->refresh();
        $this->assertTrue((bool) $buku->is_processing);
    }

    public function test_sistem_langsung_update_status_jika_semua_halaman_sudah_terproses()
    {
        // 1. PERSIAPAN
        Storage::fake('local');
        Storage::fake('s3');

        $buku = Buku::factory()->create([
            'judul_idn' => 'Buku Skip Loop',
            'is_processing' => true,
            'status_konversi' => false,
        ]);

        // KITA SIMULASIKAN KEDUA HALAMAN SUDAH ADA DI DATABASE
        // Karena dummy_2_pages.pdf punya 2 halaman, maka startIndex nanti akan bernilai 2
        Halaman::factory()->create(['id_buku' => $buku->id_buku, 'nomor_halaman' => 1]);
        Halaman::factory()->create(['id_buku' => $buku->id_buku, 'nomor_halaman' => 2]);

        $fixturePath = base_path('tests/Fixtures/dummy_2_pages.pdf');
        $pdfPath = 'buku/pdf/dummy_skip.pdf';
        Storage::disk('local')->put($pdfPath, file_get_contents($fixturePath));

        // 2. TINDAKAN
        $service = new ProcessPdfService();
        $service->process($buku, $pdfPath);

        // 3. PEMBUKTIAN
        // Pastikan tidak ada halaman ke-3 yang tercipta secara gaib
        $halaman = Halaman::where('id_buku', $buku->id_buku)->get();
        $this->assertCount(2, $halaman, 'Jumlah halaman harus tetap 2');

        // Pastikan blok di luar loop (update status & hapus file lokal) tetap tereksekusi dengan benar
        $buku->refresh();
        $this->assertFalse((bool) $buku->is_processing, 'Buku harus selesai diproses');
        $this->assertTrue((bool) $buku->status_konversi, 'Status konversi harus true');
        
        // Memastikan file fisik PDF tetap dihapus meski loop dilewati
        Storage::disk('local')->assertMissing($pdfPath);
    }

    public function test_halaman_tetap_tersimpan_meskipun_gagal_update_status_buku_di_akhir_proses()
    {
        // 1. PERSIAPAN
        Storage::fake('local');
        Storage::fake('s3');

        $buku = Buku::factory()->create([
            'judul_idn' => 'Buku Gagal Update Status',
            'is_processing' => true,
            'status_konversi' => false,
            'local_pdf_path' => 'buku/pdf/dummy_fail_update.pdf'
        ]);

        $fixturePath = base_path('tests/Fixtures/dummy_2_pages.pdf');
        $pdfPath = $buku->local_pdf_path;
        Storage::disk('local')->put($pdfPath, file_get_contents($fixturePath));

        // SABOTASE DATABASE BUKU: Lempar exception HANYA saat status_konversi diubah menjadi true
        Buku::updating(function ($model) {
            if ($model->isDirty('status_konversi') && $model->status_konversi === true) {
                throw new \Exception('Simulasi database mati saat update status buku');
            }
        });

        // 2. TINDAKAN
        $service = new ProcessPdfService();
        $exceptionThrown = false;
        
        try {
            $service->process($buku, $pdfPath);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertEquals('Simulasi database mati saat update status buku', $e->getMessage());
        }

        // 3. PEMBUKTIAN
        $this->assertTrue($exceptionThrown, 'Exception dari sabotase harus terlempar');

        // BUKTI 1: Halaman 1 & 2 TETAP TERSIMPAN di database (karena commit dilakukan per iterasi)
        $this->assertCount(2, Halaman::where('id_buku', $buku->id_buku)->get());

        // BUKTI 2: Status buku GAGAL terupdate (mengalami inkonsistensi yang sesuai dengan alur kode)
        $buku->refresh();
        $this->assertTrue((bool) $buku->is_processing, 'Buku masih nyangkut di status processing');
        $this->assertFalse((bool) $buku->status_konversi, 'Buku masih nyangkut di status konversi false');

        // BUKTI 3: File PDF Master TIDAK Dihapus dari storage lokal (karena terhenti sebelum baris delete)
        Storage::disk('local')->assertExists($pdfPath);
    }
}