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
        $tempPdfPath = tempnam(sys_get_temp_dir(), 'pdf_');
        if ($tempPdfPath === false) {
            throw new \Exception('Tidak dapat membuat file temporer untuk PDF');
        }

        $pdfContents = Storage::disk('s3')->get($pdfPath);
        if ($pdfContents === null || $pdfContents === false) {
            throw new \Exception("File PDF tidak ditemukan: {$pdfPath}");
        }

        file_put_contents($tempPdfPath, $pdfContents);

        $imagick = new \Imagick();
        try {
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256);
            $imagick->setResolution(120, 120);
            $imagick->readImage($tempPdfPath);

            foreach ($imagick as $index => $page) {
                $page->setImageFormat('webp');
                $page->setImageCompressionQuality(80);

                $fileName = 'buku/halaman/' . uniqid() . '_halaman_' . ($index + 1) . '.webp';
                $tempImagePath = tempnam(sys_get_temp_dir(), 'page_');
                if ($tempImagePath === false) {
                    throw new \Exception('Tidak dapat membuat file temporer untuk gambar halaman');
                }

                if (!$page->writeImage($tempImagePath) || !file_exists($tempImagePath)) {
                    throw new \Exception('Gagal menyimpan gambar halaman ke-' . ($index + 1));
                }

                $imageContents = file_get_contents($tempImagePath);
                if ($imageContents === false) {
                    throw new \Exception('Gagal membaca gambar halaman ke-' . ($index + 1));
                }

                Storage::disk('s3')->put($fileName, $imageContents);
                unlink($tempImagePath);

                // Get page image dimensions
                list($width, $height) = getimagesizefromstring($imageContents);

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
            Storage::disk('s3')->delete($pdfPath);
        }
    }
}
