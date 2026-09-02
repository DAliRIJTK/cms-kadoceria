<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Buku extends Model
{
    use HasFactory;

    private function storageDisk()
    {
        return Storage::disk(config('filesystems.default', 'public'));
    }

    protected $table = 'buku';
    protected $primaryKey = 'id_buku';
    public $timestamps = true;

    protected $fillable = [
        'id_pengelola',
        'judul_idn',
        'judul_sn',
        'penulis',
        'ilustrator',
        'path_cover',
        'original_pdf_name',
        'status_publikasi',
        'deskripsi_idn',
        'deskripsi_sn',
        'warna_primer',
        'warna_sekunder',
        'zip_bundle_path',
        'pdf_hash',
        'local_pdf_path',
        'is_processing',
        'status_konversi',
        'version',
        'published_at',
    ];

    public function pengelola()
    {
        return $this->belongsTo(User::class, 'id_pengelola', 'id');
    }

    public function halaman()
    {
        return $this->hasMany(Halaman::class, 'id_buku', 'id_buku');
    }

    public function getRouteKeyName()
    {
        return 'judul_idn';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->where('judul_idn', $value);

        if (is_numeric($value)) {
            $query->orWhere('id_buku', $value);
        }

        return $query->firstOrFail();
    }

    public function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s_-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '_', $text);
        return trim($text, '_');
    }

    public function buildPageAssetPath(Halaman $page, string $directory, string $extension, ?string $suffix = null): string
    {
        $bookDir = $this->slugify($this->judul_idn);
        
        // Terapkan logika cover dan halaman
        if ($page->nomor_halaman === 1) {
            $baseName = 'cover';
        } else {
            $baseName = 'halaman' . ($page->nomor_halaman - 1);
        }

        if ($suffix) {
            $baseName .= '_' . $suffix;
        }

        return 'buku/' . $bookDir . '/' . trim($directory, '/') . '/' . $baseName . '.' . ltrim($extension, '.');
    }

    public function syncStorageStructure(): void
    {
        $bookDir = $this->slugify($this->judul_idn);
        
        // 1. Pastikan direktori utama S3 tersedia
        $dirs = [
            'buku/' . $bookDir . '/halaman',
            'buku/' . $bookDir . '/audio narasi indonesia',
            'buku/' . $bookDir . '/audio narasi sunda',
            'buku/' . $bookDir . '/audio backsound',
            'buku/' . $bookDir . '/audio objek',
        ];
        foreach ($dirs as $dir) {
            if (!$this->storageDisk()->exists($dir)) {
                $this->storageDisk()->makeDirectory($dir);
            }
        }

        $halamanList = $this->halaman()->orderBy('nomor_halaman')->get();
        
        // 2. PERSIAPAN: Kumpulkan rencana pembaruan Database dan S3
        $dbUpdates = ['halaman' => [], 'area' => []];
        $s3Moves = [];

        foreach ($halamanList as $page) {
            $uniq = uniqid();
            
            // Pengecekan Gambar Halaman
            if ($page->path_gambar && $this->storageDisk()->exists($page->path_gambar)) {
                $ext = pathinfo($page->path_gambar, PATHINFO_EXTENSION);
                $tempPath = 'buku/' . $bookDir . '/halaman/temp_' . $uniq . '.' . $ext;
                $finalPath = $this->buildPageAssetPath($page, 'halaman', $ext);
                
                $s3Moves[] = ['old' => $page->path_gambar, 'temp' => $tempPath, 'final' => $finalPath];
                $dbUpdates['halaman'][$page->id_halaman]['path_gambar'] = $finalPath;
            }

            // Pengecekan Narasi Indonesia
            if ($page->narasi_indo && $this->storageDisk()->exists($page->narasi_indo)) {
                $ext = pathinfo($page->narasi_indo, PATHINFO_EXTENSION);
                $tempPath = 'buku/' . $bookDir . '/audio narasi indonesia/temp_' . $uniq . '.' . $ext;
                $finalPath = $this->buildPageAssetPath($page, 'audio narasi indonesia', $ext);
                
                $s3Moves[] = ['old' => $page->narasi_indo, 'temp' => $tempPath, 'final' => $finalPath];
                $dbUpdates['halaman'][$page->id_halaman]['narasi_indo'] = $finalPath;
            }

            // Pengecekan Narasi Sunda
            if ($page->narasi_sunda && $this->storageDisk()->exists($page->narasi_sunda)) {
                $ext = pathinfo($page->narasi_sunda, PATHINFO_EXTENSION);
                $tempPath = 'buku/' . $bookDir . '/audio narasi sunda/temp_' . $uniq . '.' . $ext;
                $finalPath = $this->buildPageAssetPath($page, 'audio narasi sunda', $ext);
                
                $s3Moves[] = ['old' => $page->narasi_sunda, 'temp' => $tempPath, 'final' => $finalPath];
                $dbUpdates['halaman'][$page->id_halaman]['narasi_sunda'] = $finalPath;
            }

            // Salin Backsound (Hanya di-copy jika belum ada, tidak perlu via temp)
            if ($page->audioLatar && $page->audioLatar->path_file) {
                $src = $page->audioLatar->path_file;
                if ($this->storageDisk()->exists($src)) {
                    $ext = pathinfo($src, PATHINFO_EXTENSION);
                    $destName = $this->slugify($page->audioLatar->nama_audio) . '.' . $ext;
                    $destPath = 'buku/' . $bookDir . '/audio backsound/' . $destName;
                    if (!$this->storageDisk()->exists($destPath)) {
                        $this->storageDisk()->copy($src, $destPath);
                    }
                }
            }

            // Pengecekan Area Interaktif
            foreach ($page->areaInteraktif as $area) {
                $safeLabel = $this->slugify($area->label ?? 'objek');
                $areaUniq = uniqid();

                if ($area->audio_indo && $this->storageDisk()->exists($area->audio_indo)) {
                    $ext = pathinfo($area->audio_indo, PATHINFO_EXTENSION);
                    $tempPath = 'buku/' . $bookDir . '/audio objek/temp_indo_' . $areaUniq . '.' . $ext;
                    $finalPath = $this->buildPageAssetPath($page, 'audio objek', $ext, $safeLabel . '_indonesia');
                    
                    $s3Moves[] = ['old' => $area->audio_indo, 'temp' => $tempPath, 'final' => $finalPath];
                    $dbUpdates['area'][$area->id_area]['audio_indo'] = $finalPath;
                }

                if ($area->audio_sunda && $this->storageDisk()->exists($area->audio_sunda)) {
                    $ext = pathinfo($area->audio_sunda, PATHINFO_EXTENSION);
                    $tempPath = 'buku/' . $bookDir . '/audio objek/temp_sunda_' . $areaUniq . '.' . $ext;
                    $finalPath = $this->buildPageAssetPath($page, 'audio objek', $ext, $safeLabel . '_sunda');
                    
                    $s3Moves[] = ['old' => $area->audio_sunda, 'temp' => $tempPath, 'final' => $finalPath];
                    $dbUpdates['area'][$area->id_area]['audio_sunda'] = $finalPath;
                }
            }
        }

        // 3. ATOMISITAS TRANSAKSI (Mulai Kunci DB)
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Tulis semua pembaruan path ke Database sekaligus
            foreach ($halamanList as $page) {
                if (!empty($dbUpdates['halaman'][$page->id_halaman])) {
                    $page->update($dbUpdates['halaman'][$page->id_halaman]);
                }
                foreach ($page->areaInteraktif as $area) {
                    if (!empty($dbUpdates['area'][$area->id_area])) {
                        $area->update($dbUpdates['area'][$area->id_area]);
                    }
                }
            }

            // Fix path cover pada Buku jika diperlukan
            $firstPage = $halamanList->first();
            if ($firstPage && !empty($dbUpdates['halaman'][$firstPage->id_halaman]['path_gambar'])) {
                $this->update(['path_cover' => $dbUpdates['halaman'][$firstPage->id_halaman]['path_gambar']]);
            }

            // 4. EKSEKUSI PEMINDAHAN FISIK DI S3
            // Pass 1: Pindahkan semua ke nama Temp (menghindari nama tertimpa jika halaman ditukar)
            foreach ($s3Moves as $move) {
                $this->storageDisk()->move($move['old'], $move['temp']);
            }
            // Pass 2: Pindahkan dari nama Temp ke nama Final
            foreach ($s3Moves as $move) {
                if ($this->storageDisk()->exists($move['final']) && $move['temp'] !== $move['final']) { 
                    $this->storageDisk()->delete($move['final']); 
                }
                $this->storageDisk()->move($move['temp'], $move['final']);
            }

            // 5. KOMIT: Jika Database & S3 Sukses
            \Illuminate\Support\Facades\DB::commit();

        } catch (\Exception $e) {
            // 6. ROLLBACK: Jika ada satu saja S3 Move yang gagal, kembalikan DB ke versi lama
            \Illuminate\Support\Facades\DB::rollBack();
            
            \Illuminate\Support\Facades\Log::error("Gagal melakukan syncStorageStructure pada buku {$this->id_buku}: " . $e->getMessage());
            throw $e; // Lempar exception agar Job / Controller tahu bahwa proses gagal
        }
    }
}
