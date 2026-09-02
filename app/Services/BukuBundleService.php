<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BukuBundleService
{
    private function getStorageDiskName(): string
    {
        return config('filesystems.default', 'local');
    }

    private function storageDisk()
    {
        return Storage::disk($this->getStorageDiskName());
    }

    /**
     * Generate metadata.json and ZIP bundle for the given book.
     *
     * @param Buku $buku
     * @return void
     * @throws \Exception
     */
    /**
     * Sentralisasi array JSON agar format API, S3, dan ZIP Bundle persis sama
     */
    public function getMetadataArray(Buku $buku): array
    {
        $halaman = $buku->relationLoaded('halaman')
            ? $buku->halaman
            : $buku->halaman()->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman')->get();

        $folderName = $buku->slugify($buku->judul_idn);
        $baseS3Path = 'buku/' . $folderName . '/';

        return [
            'id'             => (string) $buku->id_buku,
            'title_id'       => $buku->judul_idn,
            'title_su'       => $buku->judul_sn,
            'folderName'     => $folderName,
            'description_id' => $buku->deskripsi_idn,
            'description_su' => $buku->deskripsi_sn,
            'author'         => $buku->penulis,
            'illustrator'    => $buku->ilustrator,
            'coverImage'     => $this->getRelativeS3Path($buku->path_cover, $baseS3Path),
            'theme'          => [
                'primary'   => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
                'secondary' => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
            ],
            // [FIX BUG] Filter nomor_halaman == 1 agar cover tidak masuk ke dalam list array pages
            'pages'          => $halaman->filter(function ($page) {
                return $page->nomor_halaman !== 1;
            })->values()->map(function ($page) use ($baseS3Path, $buku) {
                
                $backsoundRelPath = null;
                if ($page->audioLatar && $page->audioLatar->path_file) {
                    $ext = pathinfo($page->audioLatar->path_file, PATHINFO_EXTENSION);
                    $destName = $buku->slugify($page->audioLatar->nama_audio) . '.' . $ext;
                    $backsoundRelPath = 'audio backsound/' . $destName;
                }

                return [
                    'image'              => $this->getRelativeS3Path($page->path_gambar, $baseS3Path),
                    'backsound'          => $backsoundRelPath,
                    'narationId'         => $this->getRelativeS3Path($page->narasi_indo, $baseS3Path),
                    'narationSd'         => $this->getRelativeS3Path($page->narasi_sunda, $baseS3Path),
                    'widthImage'         => (float) ($page->lebar_halaman ?? 0),
                    'heightImage'        => (float) ($page->panjang_halaman ?? 0),
                    'interactiveObjects' => $page->areaInteraktif->map(function ($area) use ($page, $baseS3Path) {
                        return [
                            'audioObjectId' => $this->getRelativeS3Path($area->audio_indo, $baseS3Path),
                            'audioObjectSd' => $this->getRelativeS3Path($area->audio_sunda, $baseS3Path),
                            'x'             => (float) (($area->x_pct / 100) * $page->lebar_halaman),
                            'y'             => (float) (($area->y_pct / 100) * $page->panjang_halaman),
                            'width'         => (float) (($area->w_pct / 100) * $page->lebar_halaman),
                            'height'        => (float) (($area->h_pct / 100) * $page->panjang_halaman),
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate the metadata.json file using relative paths identical to Bundle
     */
    public function generateMetadataJson(Buku $buku): void
    {
        $folderName = $buku->slugify($buku->judul_idn);
        $metadata = $this->getMetadataArray($buku);

        $this->storageDisk()->put(
            'buku/' . $folderName . '/metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Generate the ZIP bundle mirroring exact S3 directory structure
     */
    public function generateZipBundle(Buku $buku): void
    {
        $folderName = $buku->slugify($buku->judul_idn);
        $baseS3Path = 'buku/' . $folderName . '/';

        // [FIX BUG] Pastikan root directory `tmp` benar-benar eksis sebelum memproses
        $tmpBaseDir = storage_path('app/tmp');
        if (!is_dir($tmpBaseDir)) {
            @mkdir($tmpBaseDir, 0777, true);
        }

        $tmpDir = $tmpBaseDir . '/bundle_' . $buku->id_buku . '_' . time();
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }
        
        $metadataJson = $this->getMetadataArray($buku);
        $filesToCopy = [];
        
        $registerFile = function($s3Path) use (&$filesToCopy, $baseS3Path) {
            if ($s3Path) {
                $filesToCopy[$s3Path] = $this->getRelativeS3Path($s3Path, $baseS3Path);
            }
        };

        $registerFile($buku->path_cover);

        $halaman = $buku->relationLoaded('halaman')
            ? $buku->halaman
            : $buku->halaman()->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman')->get();

        foreach ($halaman as $page) {
            $registerFile($page->path_gambar);
            if ($page->audioLatar && $page->audioLatar->path_file) {
                $ext = pathinfo($page->audioLatar->path_file, PATHINFO_EXTENSION);
                $destName = $buku->slugify($page->audioLatar->nama_audio) . '.' . $ext;
                $originalS3Path = $page->audioLatar->path_file;
                $relPath = 'audio backsound/' . $destName;
                $filesToCopy[$originalS3Path] = $relPath;
            }
            $registerFile($page->narasi_indo);
            $registerFile($page->narasi_sunda);

            foreach ($page->areaInteraktif as $area) {
                $registerFile($area->audio_indo);
                $registerFile($area->audio_sunda);
            }
        }

        foreach ($filesToCopy as $s3Path => $relPath) {
            $localDest = $tmpDir . '/' . $relPath;
            $dir = dirname($localDest);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (!file_exists($localDest)) {
                $this->copyFromStorageToLocal($s3Path, $localDest);
            }
        }

        file_put_contents(
            $tmpDir . '/metadata.json',
            json_encode($metadataJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $zipFilename = $buku->id_buku . '_v' . ($buku->updated_at->timestamp) . '.zip';
        
        // [FIX BUG] Tempatkan ZIP di LUAR folder $tmpDir agar tidak ikut ter-loop dan error
        $zipTempPath = $tmpBaseDir . '/' . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Tidak dapat membuat file ZIP: ' . $zipTempPath);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmpDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath     = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tmpDir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        $zipContent = file_get_contents($zipTempPath);
        if ($zipContent === false) {
            throw new \Exception('Tidak dapat membaca file ZIP yang dihasilkan');
        }

        $this->storageDisk()->put('buku/bundle/' . $zipFilename, $zipContent);

        @unlink($zipTempPath);
        $newVersion = (empty($buku->zip_bundle_path) || $buku->version == 0) 
                      ? 1 
                      : $buku->version + 1;
        $buku->update([
            'zip_bundle_path' => 'buku/bundle/' . $zipFilename,
            'version' => $newVersion // <-- Simpan versi baru ke database
        ]);
        $this->deleteTmpDir($tmpDir);
    }

    /**
     * Helper to get relative path removing the 'buku/judul/' base string
     */
    private function getRelativeS3Path(?string $path, string $baseS3Path): ?string
    {
        if (!$path) return null;
        return ltrim(str_replace($baseS3Path, '', $path), '/');
    }

    /**
     * Convert RGB string (r,g,b) to HEX string.
     *
     * @param string|null $value
     * @param string $default
     * @return string
     */
    public function rgbToHex(?string $value, string $default = '#FFFFFF'): string
    {
        if (!$value) return $default;
        $value = trim($value);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return strtoupper($value);
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) !== 3) return $default;

        $r = max(0, min(255, (int) $parts[0]));
        $g = max(0, min(255, (int) $parts[1]));
        $b = max(0, min(255, (int) $parts[2]));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    private function storageUrl(?string $path): ?string
    {
        return $path ? $this->storageDisk()->url($path) : null;
    }

    private function copyFromStorageToLocal(?string $path, string $destPath): bool
    {
        if (!$path) {
            return false;
        }

        try {
            $contents = $this->storageDisk()->get($path);
        } catch (\Exception $e) {
            return false;
        }

        if ($contents === null || $contents === false) {
            return false;
        }

        file_put_contents($destPath, $contents);
        return true;
    }

    /**
     * Delete temporary directory recursively.
     *
     * @param string $dir
     * @return void
     */
    private function deleteTmpDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }
}
