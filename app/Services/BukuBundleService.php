<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BukuBundleService
{
    /**
     * Generate metadata.json and ZIP bundle for the given book.
     *
     * @param Buku $buku
     * @return void
     * @throws \Exception
     */
    public function generateAndPackageBundle(Buku $buku): void
    {
        try {
            $buku->load(['halaman' => function ($q) {
                $q->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman');
            }]);

            $this->generateMetadataJson($buku);
            $this->generateZipBundle($buku);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate the metadata.json file.
     *
     * @param Buku $buku
     * @return void
     */
    public function generateMetadataJson(Buku $buku): void
    {
        $halaman = $buku->relationLoaded('halaman')
            ? $buku->halaman
            : $buku->halaman()->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman')->get();

        $metadata = [
            'id'                => (string) $buku->id_buku,
            'judul_idn'         => $buku->judul_idn,
            'judul_sn'          => $buku->judul_sn,
            'penulis'           => $buku->penulis,
            'ilustrator'        => $buku->ilustrator,
            'deskripsi_idn'     => $buku->deskripsi_idn,
            'deskripsi_sn'      => $buku->deskripsi_sn,
            'warna_primer'      => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
            'warna_sekunder'    => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
            'cover'             => $this->storageUrl($buku->path_cover),
            'status_publikasi'  => $buku->status_publikasi,
            'tanggal_publikasi' => $buku->updated_at->toIso8601String(),
            'total_halaman'     => $halaman->count(),
            'halaman'           => $halaman->map(function ($page) use ($buku) {
                $isCover = $page->nomor_halaman === 1;
                return [
                    'id'           => (string) $page->id_halaman,
                    'nomor'        => $page->nomor_halaman,
                    'gambar'       => $this->storageUrl($page->path_gambar),
                    'narasi_indo'  => $this->storageUrl($page->narasi_indo),
                    'narasi_sunda' => $this->storageUrl($page->narasi_sunda),
                    'backsound'    => $isCover ? null : ($page->audioLatar ? $this->storageUrl($page->audioLatar->path_file) : null),
                    'area_interaktif' => $isCover ? [] : $page->areaInteraktif->map(function ($area) {
                        return [
                            'id'          => (string) $area->id_area,
                            'label'       => $area->label        ?? null,
                            'x'           => $area->x,
                            'y'           => $area->y,
                            'x_pct'       => $area->x_pct        ?? null,
                            'y_pct'       => $area->y_pct        ?? null,
                            'w_pct'       => $area->w_pct        ?? null,
                            'h_pct'       => $area->h_pct        ?? null,
                            'lebar'       => $area->lebar_area,
                            'tinggi'      => $area->panjang_area,
                            'audio_indo'  => $this->storageUrl($area->audio_indo),
                            'audio_sunda' => $this->storageUrl($area->audio_sunda),
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        Storage::disk('s3')->put(
            'buku/' . $buku->slugify($buku->judul_idn) . '/metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Generate the ZIP bundle containing all assets and a data.json mapping.
     *
     * @param Buku $buku
     * @return void
     * @throws \Exception
     */
    public function generateZipBundle(Buku $buku): void
    {
        $halaman = $buku->relationLoaded('halaman')
            ? $buku->halaman
            : $buku->halaman()->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman')->get();

        $folderName = $buku->slugify($buku->judul_idn);

        $tmpDir = storage_path('app/tmp/bundle_' . $buku->id_buku . '_' . time());
        @mkdir($tmpDir . '/images', 0777, true);
        @mkdir($tmpDir . '/audio',  0777, true);

        $coverRelPath = null;
        if ($buku->path_cover) {
            $coverFilename = 'cover' . '.' . pathinfo($buku->path_cover, PATHINFO_EXTENSION);
            if ($this->copyFromStorageToLocal($buku->path_cover, $tmpDir . '/images/' . $coverFilename)) {
                $coverRelPath = 'images/' . $coverFilename;
            }
        }

        $pagesData = [];
        $pageIndex  = 1; // nomor urut halaman di ZIP (halaman 2 DB → page_1, dst.)
        foreach ($halaman as $page) {
            // Halaman 1 adalah cover, sudah disalin sebagai cover.* — lewati
            if ($page->nomor_halaman === 1) {
                continue;
            }

            $pageRelPath = null;
            if ($page->path_gambar) {
                $pageFilename = 'page_' . $pageIndex . '.' . pathinfo($page->path_gambar, PATHINFO_EXTENSION);
                if ($this->copyFromStorageToLocal($page->path_gambar, $tmpDir . '/images/' . $pageFilename)) {
                    $pageRelPath = 'images/' . $pageFilename;
                }
            }

            $backsoundRelPath = null;
            if ($page->audioLatar && $page->audioLatar->path_file) {
                $bgmFilename = 'bgm_' . $page->audioLatar->id_audio_latar . '.' . pathinfo($page->audioLatar->path_file, PATHINFO_EXTENSION);
                if (!file_exists($tmpDir . '/audio/' . $bgmFilename) && $this->copyFromStorageToLocal($page->audioLatar->path_file, $tmpDir . '/audio/' . $bgmFilename)) {
                    $backsoundRelPath = 'audio/' . $bgmFilename;
                }
            }

            $narasiIdRelPath = null;
            if ($page->narasi_indo) {
                $narasiIdFilename = 'narasi_id_' . $page->id_halaman . '.' . pathinfo($page->narasi_indo, PATHINFO_EXTENSION);
                if ($this->copyFromStorageToLocal($page->narasi_indo, $tmpDir . '/audio/' . $narasiIdFilename)) {
                    $narasiIdRelPath = 'audio/' . $narasiIdFilename;
                }
            }

            $narasiSuRelPath = null;
            if ($page->narasi_sunda) {
                $narasiSuFilename = 'narasi_su_' . $page->id_halaman . '.' . pathinfo($page->narasi_sunda, PATHINFO_EXTENSION);
                if ($this->copyFromStorageToLocal($page->narasi_sunda, $tmpDir . '/audio/' . $narasiSuFilename)) {
                    $narasiSuRelPath = 'audio/' . $narasiSuFilename;
                }
            }

            $interactiveObjects = [];
            foreach ($page->areaInteraktif as $area) {

                $audioObjIdRelPath = null;
                if ($area->audio_indo) {
                    $objIdFilename = 'objek_id_' . $area->id_area . '.' . pathinfo($area->audio_indo, PATHINFO_EXTENSION);
                    if ($this->copyFromStorageToLocal($area->audio_indo, $tmpDir . '/audio/' . $objIdFilename)) {
                        $audioObjIdRelPath = 'audio/' . $objIdFilename;
                    }
                }

                $audioObjSuRelPath = null;
                if ($area->audio_sunda) {
                    $objSuFilename = 'objek_su_' . $area->id_area . '.' . pathinfo($area->audio_sunda, PATHINFO_EXTENSION);
                    if ($this->copyFromStorageToLocal($area->audio_sunda, $tmpDir . '/audio/' . $objSuFilename)) {
                        $audioObjSuRelPath = 'audio/' . $objSuFilename;
                    }
                }

                $interactiveObjects[] = [
                    'x'             => (int)   $area->x,
                    'y'             => (int)   $area->y,
                    'width'         => (int)   $area->lebar_area,
                    'height'        => (int)   $area->panjang_area,
                    'audioObjectId' => $audioObjIdRelPath,
                    'audioObjectSd' => $audioObjSuRelPath,
                ];
            }

            $pagesData[] = [
                'image'              => $pageRelPath,
                'backsound'          => $backsoundRelPath,
                'widthImage'         => (int) ($page->lebar_halaman  ?? 0),
                'heightImage'        => (int) ($page->panjang_halaman ?? 0),
                'narationId'         => $narasiIdRelPath,
                'narationSd'         => $narasiSuRelPath,
                'interactiveObjects' => $interactiveObjects,
            ];

            $pageIndex++;
        }

        $dataJson = [
            'id'           => (string) $buku->id_buku,
            'title_id'     => $buku->judul_idn,
            'title_su'     => $buku->judul_sn,
            'folderName'   => $folderName,
            'description_id' => $buku->deskripsi_idn,
            'description_su' => $buku->deskripsi_sn,
            'author'       => $buku->penulis,
            'illustrator'  => $buku->ilustrator,
            'coverImage'   => $coverRelPath,
            'theme'        => [
                'primary'   => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
                'secondary' => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
            ],
            'pages' => $pagesData,
        ];

        file_put_contents(
            $tmpDir . '/data.json',
            json_encode($dataJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $zipFilename = $buku->id_buku . '_v' . ($buku->updated_at->timestamp) . '.zip';
        $zipTempPath = $tmpDir . '/' . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipTempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Tidak dapat membuat file ZIP: ' . $zipTempPath);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmpDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath    = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tmpDir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();
        $zipContent = file_get_contents($zipTempPath);
        if ($zipContent === false) {
            throw new \Exception('Tidak dapat membaca file ZIP yang dihasilkan');
        }

        Storage::disk('s3')->put('buku/bundle/' . $zipFilename, $zipContent);
        @unlink($zipTempPath);

        $buku->update(['zip_bundle_path' => 'buku/bundle/' . $zipFilename]);

        $this->deleteTmpDir($tmpDir);
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
        return $path ? Storage::disk('s3')->url($path) : null;
    }

    private function copyFromStorageToLocal(?string $path, string $destPath): bool
    {
        if (!$path) {
            return false;
        }

        try {
            $contents = Storage::disk('s3')->get($path);
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
