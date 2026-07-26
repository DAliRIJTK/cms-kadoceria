<?php


namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Buku;
use App\Models\Halaman;
use App\Models\User;
use App\Services\ProcessPdfService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Contracts\Filesystem\Filesystem;

class ProcessPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_pdf_berhasil_memecah_halaman_dan_simpan_ke_storage()
    {
        // 1. Persiapan Storage Fake
        Storage::fake('local');
        Storage::fake('s3');

        // 2. Persiapan Data Dummy
        $user = User::factory()->create();
        $buku = Buku::factory()->create([
            'id_pengelola' => $user->id,
            'judul_idn' => 'Buku Cerita Dummy',
            'is_processing' => true,
        ]);

        $pdfPath = 'buku/pdf/dummy.pdf';
        
        // Buat PDF asli berukuran kecil secara fisik agar \Imagick tidak melempar Exception.
        // Ganti path copy di bawah dengan lokasi file PDF dummy di direktori testing kamu.
        $dummyPdfContent = file_get_contents(base_path('tests/Fixtures/dummy_1_page.pdf'));
        Storage::disk('local')->put($pdfPath, $dummyPdfContent);

        // 3. Eksekusi
        $service = new ProcessPdfService();
        $service->process($buku, $pdfPath);

        // 4. Verifikasi Database
        // Memastikan is_processing berubah menjadi false
        $this->assertEquals(0, $buku->fresh()->is_processing);        
        // Memastikan tabel halaman terisi
        $this->assertDatabaseCount('halaman', 1);
        $halaman = Halaman::first();
        
        // Memastikan cover diupdate ke halaman pertama
        $this->assertEquals($halaman->path_gambar, $buku->fresh()->path_cover);

        // 5. Verifikasi Storage
        // Memastikan file di S3 benar-benar tersimpan
        Storage::disk('s3')->assertExists($halaman->path_gambar);
        
        // Memastikan file temporary lokal telah dihapus
        Storage::disk('local')->assertMissing($pdfPath);
    }
    
    public function test_gagal_jika_file_tidak_ditemukan_dan_pastikan_cleanup_lokal()
    {
        Storage::fake('local');
        $buku = Buku::factory()->create(['is_processing' => true]);
        $pdfPath = 'buku/pdf/missing.pdf';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("File PDF tidak ditemukan: {$pdfPath}");

        try {
            (new ProcessPdfService())->process($buku, $pdfPath);
        } finally {
            // Skenario 5: Memastikan file lokal tetap dihapus (jika ada) meskipun terjadi Exception
            Storage::disk('local')->assertMissing($pdfPath);
        }
    }

    public function test_file_corrupt_menghentikan_proses_dan_reset_status()
    {
        Storage::fake('local');
        $buku = Buku::factory()->create(['is_processing' => true]);
        $pdfPath = 'buku/pdf/corrupt.pdf';
        
        // Membuat file lokal yang bukan PDF untuk memicu error pada \Imagick->readImage()
        Storage::disk('local')->put($pdfPath, 'ini bukan file pdf valid');

        $this->expectException(\Exception::class);

        try {
            (new ProcessPdfService())->process($buku, $pdfPath);
        } finally {
            // Skenario 4: Status is_processing dikembalikan menjadi false (diwakili 0)
            $this->assertEquals(0, $buku->fresh()->is_processing);
            
            // Skenario 5: File PDF corrupt di lokal tetap dibersihkan
            Storage::disk('local')->assertMissing($pdfPath);
        }
    }

    public function test_kegagalan_sistem_memicu_rollback_db_dan_cleanup_storage()
    {
        $buku = Buku::factory()->create(['is_processing' => true]);
        $pdfPath = 'buku/pdf/dummy_2.pdf';
        
        // Gunakan PDF dengan MINIMAL 2 HALAMAN
        $dummyPdfContent = file_get_contents(base_path('tests/Fixtures/dummy_2_pages.pdf'));

        // Mock Storage Local
        $localMock = Mockery::mock(Filesystem::class);
        $localMock->shouldReceive('get')->with($pdfPath)->andReturn($dummyPdfContent);
        $localMock->shouldReceive('delete')->with($pdfPath)->once();

        // Mock Storage S3
        $s3Mock = Mockery::mock(Filesystem::class);
        
        $callCount = 0;
        // Skenario 3: Memaksa S3 melempar error saat mengunggah halaman KEDUA
        // Halaman pertama akan berhasil masuk ke array $uploadedS3Files
        $s3Mock->shouldReceive('put')->andReturnUsing(function() use (&$callCount) {
            $callCount++;
            if ($callCount === 2) {
                throw new \Exception('S3 Down saat upload halaman 2');
            }
        });
        
        // Verifikasi Skenario 3: Memastikan S3 menghapus file halaman 1 yang sempat berhasil diunggah
        $s3Mock->shouldReceive('delete')->once(); 

        Storage::shouldReceive('disk')->with('local')->andReturn($localMock);
        Storage::shouldReceive('disk')->with('s3')->andReturn($s3Mock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('S3 Down saat upload halaman 2');

        try {
            (new ProcessPdfService())->process($buku, $pdfPath);
        } finally {
            // Skenario 2: Rollback Database terpicu (tabel halaman tidak boleh menyimpan data halaman 1)
            $this->assertDatabaseCount('halaman', 0);
            
            // Skenario 4: Status gagal dirubah kembali menjadi false
            $this->assertEquals(0, $buku->fresh()->is_processing);
        }
    }
}