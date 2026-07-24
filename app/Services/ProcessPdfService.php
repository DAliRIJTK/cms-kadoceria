<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\DB;
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

        $pdfContents = Storage::disk('local')->get($pdfPath);
        if ($pdfContents === null || $pdfContents === false) {
            throw new \Exception("File PDF tidak ditemukan: {$pdfPath}");
        }

        file_put_contents($tempPdfPath, $pdfContents);

        $imagick = new \Imagick();
        $uploadedS3Files = [];
        try {
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256 * 1024 * 1024);
            $imagick->setResolution(120, 120);
            $imagick->readImage($tempPdfPath);

            $bookDir = $buku->slugify($buku->judul_idn);

            DB::beginTransaction();

            foreach ($imagick as $index => $page) {
                $page->setImageFormat('webp');
                $page->setImageCompressionQuality(80);

                $imageContents = $page->getImageBlob();

                if ($imageContents === false) {
                    throw new \Exception('Gagal membaca gambar halaman ke-' . ($index + 1));
                }

                list($width, $height) = getimagesizefromstring($imageContents);

                $halaman = Halaman::create([
                    'id_buku'       => $buku->id_buku,
                    'nomor_halaman' => $index + 1,
                    'path_gambar'   => '',
                    'panjang_halaman' => $height,
                    'lebar_halaman'   => $width,
                ]);

                // Menyiapkan path final S3 tanpa harus memanggil syncStorageStructure()
                $fileName = 'buku/' . $bookDir . '/halaman/page-' . $halaman->id_halaman . '.webp';

                // Mengunggah langsung ke S3 ke tujuan final
                Storage::disk('s3')->put($fileName, $imageContents);

                $uploadedS3Files[] = $fileName;

                // Update kembali path final ke database
                $halaman->update(['path_gambar' => $fileName]);

                if ($index === 0) {
                    $buku->path_cover = $fileName;
                    $buku->save();
                }
            }

            DB::commit();

            // Clean up the main Imagick object resources before calling syncStorageStructure,
            // as syncing might rename/move the generated images.
            $imagick->clear();
            $imagick->destroy();
            $buku->update(['is_processing' => false]);

        } catch (\Exception $e) {
            DB::rollBack();

            if (!empty($uploadedS3Files)) {
                Storage::disk('s3')->delete($uploadedS3Files);
            }

            $buku->update(['is_processing' => false]);
            
            if (isset($imagick)) {
                $imagick->clear();
                $imagick->destroy();
            }
            throw $e;
        } finally {
            if (file_exists($tempPdfPath)) {
                @unlink($tempPdfPath);
            }
            Storage::disk('local')->delete($pdfPath);
        }
    }
}
