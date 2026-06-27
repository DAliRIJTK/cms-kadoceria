<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;

class ProcessPdfService
{
    /**
     * Process PDF file for a given book: convert each page to an image,
     * create page records, sync storage, and delete the temporary PDF.
     *
     * @param Buku $buku
     * @param string $pdfPath Relative path to the uploaded PDF file under public disk
     * @return void
     * @throws \Exception
     */
    public function process(Buku $buku, string $pdfPath): void
    {
        $fullPdfPath = storage_path('app/public/' . $pdfPath);

        if (!file_exists($fullPdfPath)) {
            throw new \Exception("File PDF tidak ditemukan: {$fullPdfPath}");
        }

        $imagick = new \Imagick();
        try {
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256);
            $imagick->setResolution(120, 120);
            $imagick->readImage($fullPdfPath);

            foreach ($imagick as $index => $page) {
                $page->setImageFormat('webp');
                $page->setImageCompressionQuality(80);

                $fileName = 'buku/halaman/' . uniqid() . '_halaman_' . ($index + 1) . '.webp';
                $fullPath = storage_path('app/public/' . $fileName);

                $dir = dirname($fullPath);
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                if (!$page->writeImage($fullPath) || !file_exists($fullPath)) {
                    throw new \Exception('Gagal menyimpan gambar halaman ke-' . ($index + 1));
                }

                // Get page image dimensions
                list($width, $height) = getimagesize($fullPath);

                Halaman::create([
                    'id_buku'       => $buku->id_buku,
                    'nomor_halaman' => $index + 1,
                    'path_gambar'   => $fileName,
                    'panjang_halaman' => $height,
                    'lebar_halaman'   => $width,
                ]);

                if ($index === 0) {
                    $buku->path_cover = $fileName;
                    $buku->save();
                }
            }

            // Clean up the main Imagick object resources before calling syncStorageStructure,
            // as syncing might rename/move the generated images.
            $imagick->clear();
            $imagick->destroy();

            // Organize storage layout
            $buku->syncStorageStructure();

        } catch (\Exception $e) {
            if (isset($imagick)) {
                $imagick->clear();
                $imagick->destroy();
            }
            throw $e;
        } finally {
            Storage::disk('public')->delete($pdfPath);
        }
    }
}
