<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Buku extends Model
{
    use HasFactory;
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
        $baseName = 'page-' . $page->id_halaman;

        if ($suffix) {
            $baseName .= '_' . $suffix;
        }

        return 'buku/' . $bookDir . '/' . trim($directory, '/') . '/' . $baseName . '.' . ltrim($extension, '.');
    }

    public function syncStorageStructure(): void
    {
        $bookDir = $this->slugify($this->judul_idn);

        // Ensure directories exist
        $dirs = [
            'buku/' . $bookDir . '/halaman',
            'buku/' . $bookDir . '/audio narasi indonesia',
            'buku/' . $bookDir . '/audio narasi sunda',
            'buku/' . $bookDir . '/audio backsound',
            'buku/' . $bookDir . '/audio objek',
        ];

        foreach ($dirs as $dir) {
            if (!Storage::disk('s3')->exists($dir)) {
                Storage::disk('s3')->makeDirectory($dir);
            }
        }

        // Get all pages ordered by nomor_halaman
        $halamanList = $this->halaman()->orderBy('nomor_halaman')->get();

        $tempFiles = [];

        // Pass 1: Move to temporary names to avoid collision
        foreach ($halamanList as $page) {
            $uniq = uniqid();

            // 1. Page image
            if ($page->path_gambar && Storage::disk('s3')->exists($page->path_gambar)) {
                $ext = pathinfo($page->path_gambar, PATHINFO_EXTENSION);
                $tempPath = 'buku/' . $bookDir . '/halaman/temp_' . $uniq . '.' . $ext;
                Storage::disk('s3')->move($page->path_gambar, $tempPath);
                $page->path_gambar = $tempPath;
                $page->save();
                $tempFiles['image'][$page->id_halaman] = [
                    'ext' => $ext,
                    'temp_path' => $tempPath,
                    'final_path' => $this->buildPageAssetPath($page, 'halaman', $ext)
                ];
            }

            // 2. Narasi Indonesia
            if ($page->narasi_indo && Storage::disk('s3')->exists($page->narasi_indo)) {
                $ext = pathinfo($page->narasi_indo, PATHINFO_EXTENSION);
                $tempPath = 'buku/' . $bookDir . '/audio narasi indonesia/temp_' . $uniq . '.' . $ext;
                Storage::disk('s3')->move($page->narasi_indo, $tempPath);
                $page->narasi_indo = $tempPath;
                $page->save();
                $tempFiles['narasi_indo'][$page->id_halaman] = [
                    'ext' => $ext,
                    'temp_path' => $tempPath,
                    'final_path' => $this->buildPageAssetPath($page, 'audio narasi indonesia', $ext)
                ];
            }

            // 3. Narasi Sunda
            if ($page->narasi_sunda && Storage::disk('s3')->exists($page->narasi_sunda)) {
                $ext = pathinfo($page->narasi_sunda, PATHINFO_EXTENSION);
                $tempPath = 'buku/' . $bookDir . '/audio narasi sunda/temp_' . $uniq . '.' . $ext;
                Storage::disk('s3')->move($page->narasi_sunda, $tempPath);
                $page->narasi_sunda = $tempPath;
                $page->save();
                $tempFiles['narasi_sunda'][$page->id_halaman] = [
                    'ext' => $ext,
                    'temp_path' => $tempPath,
                    'final_path' => $this->buildPageAssetPath($page, 'audio narasi sunda', $ext)
                ];
            }

            // 4. Backsound
            if ($page->audioLatar && $page->audioLatar->path_file) {
                $src = $page->audioLatar->path_file;
                if (Storage::disk('s3')->exists($src)) {
                    $ext = pathinfo($src, PATHINFO_EXTENSION);
                    $destName = $this->slugify($page->audioLatar->nama_audio) . '.' . $ext;
                    $destPath = 'buku/' . $bookDir . '/audio backsound/' . $destName;
                    if (!Storage::disk('s3')->exists($destPath)) {
                        Storage::disk('s3')->copy($src, $destPath);
                    }
                }
            }

            // 5. Area Interaktif
            foreach ($page->areaInteraktif as $area) {
                $safeLabel = $this->slugify($area->label ?? 'objek');

                if ($area->audio_indo && Storage::disk('s3')->exists($area->audio_indo)) {
                    $ext = pathinfo($area->audio_indo, PATHINFO_EXTENSION);
                    $areaUniqIndo = uniqid('indo_');
                    $tempPath = 'buku/' . $bookDir . '/audio objek/temp_' . $areaUniqIndo . '.' . $ext;
                    Storage::disk('s3')->move($area->audio_indo, $tempPath);
                    $area->audio_indo = $tempPath;
                    $area->save();
                    $tempFiles['area_indo'][$area->id_area] = [
                        'ext' => $ext,
                        'temp_path' => $tempPath,
                        'final_path' => $this->buildPageAssetPath($page, 'audio objek', $ext, $safeLabel . '_indonesia')
                    ];
                }

                if ($area->audio_sunda && Storage::disk('s3')->exists($area->audio_sunda)) {
                    $ext = pathinfo($area->audio_sunda, PATHINFO_EXTENSION);
                    $areaUniqSunda = uniqid('sunda_');
                    $tempPath = 'buku/' . $bookDir . '/audio objek/temp_' . $areaUniqSunda . '.' . $ext;
                    Storage::disk('s3')->move($area->audio_sunda, $tempPath);
                    $area->audio_sunda = $tempPath;
                    $area->save();
                    $tempFiles['area_sunda'][$area->id_area] = [
                        'ext' => $ext,
                        'temp_path' => $tempPath,
                        'final_path' => $this->buildPageAssetPath($page, 'audio objek', $ext, $safeLabel . '_sunda')
                    ];
                }
            }
        }

        // Pass 2: Rename from temp names to final clean names
        foreach ($halamanList as $page) {
            // Finalize page image
            if (isset($tempFiles['image'][$page->id_halaman])) {
                $info = $tempFiles['image'][$page->id_halaman];
                $finalPath = $info['final_path'];
                if (Storage::disk('s3')->exists($info['temp_path'])) {
                    if (Storage::disk('s3')->exists($finalPath)) {
                        Storage::disk('s3')->delete($finalPath);
                    }
                    Storage::disk('s3')->move($info['temp_path'], $finalPath);
                    $page->path_gambar = $finalPath;
                    $page->save();
                }
            }

            // Finalize Narasi Indonesia
            if (isset($tempFiles['narasi_indo'][$page->id_halaman])) {
                $info = $tempFiles['narasi_indo'][$page->id_halaman];
                $finalPath = $info['final_path'];
                if (Storage::disk('s3')->exists($info['temp_path'])) {
                    if (Storage::disk('s3')->exists($finalPath)) {
                        Storage::disk('s3')->delete($finalPath);
                    }
                    Storage::disk('s3')->move($info['temp_path'], $finalPath);
                    $page->narasi_indo = $finalPath;
                    $page->save();
                }
            }

            // Finalize Narasi Sunda
            if (isset($tempFiles['narasi_sunda'][$page->id_halaman])) {
                $info = $tempFiles['narasi_sunda'][$page->id_halaman];
                $finalPath = $info['final_path'];
                if (Storage::disk('s3')->exists($info['temp_path'])) {
                    if (Storage::disk('s3')->exists($finalPath)) {
                        Storage::disk('s3')->delete($finalPath);
                    }
                    Storage::disk('s3')->move($info['temp_path'], $finalPath);
                    $page->narasi_sunda = $finalPath;
                    $page->save();
                }
            }

            // Finalize Area Interaktif
            foreach ($page->areaInteraktif as $area) {
                if (isset($tempFiles['area_indo'][$area->id_area])) {
                    $info = $tempFiles['area_indo'][$area->id_area];
                    $finalPath = $info['final_path'];
                    if (Storage::disk('s3')->exists($info['temp_path'])) {
                        if (Storage::disk('s3')->exists($finalPath)) {
                            Storage::disk('s3')->delete($finalPath);
                        }
                        Storage::disk('s3')->move($info['temp_path'], $finalPath);
                        $area->audio_indo = $finalPath;
                        $area->save();
                    }
                }

                if (isset($tempFiles['area_sunda'][$area->id_area])) {
                    $info = $tempFiles['area_sunda'][$area->id_area];
                    $finalPath = $info['final_path'];
                    if (Storage::disk('s3')->exists($info['temp_path'])) {
                        if (Storage::disk('s3')->exists($finalPath)) {
                            Storage::disk('s3')->delete($finalPath);
                        }
                        Storage::disk('s3')->move($info['temp_path'], $finalPath);
                        $area->audio_sunda = $finalPath;
                        $area->save();
                    }
                }
            }
        }

        // Fix cover on the book if necessary
        $firstPage = $halamanList->first();
        if ($firstPage) {
            $this->path_cover = $firstPage->path_gambar;
            $this->save();
        }
    }
}
