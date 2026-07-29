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
        $lastProcessedPage = Halaman::where('id_buku', $buku->id_buku)->max('nomor_halaman') ?? 0;
        $startIndex = $lastProcessedPage;

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
        // $uploadedS3Files = [];
        try {
            $imagick->pingImage($tempPdfPath);
            $totalPages = $imagick->getNumberImages();
            $imagick->clear();

            $bookDir = $buku->slugify($buku->judul_idn);

            DB::beginTransaction();

            for ($index = $startIndex; $index < $totalPages; $index++) {
                $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256 * 1024 * 1024);
                $imagick->setResolution(120, 120);

                // TARGETED READING: Hanya menarik 1 halaman spesifik ke dalam RAM
                $imagick->readImage($tempPdfPath . '[' . $index . ']');
                
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality(80);

                $imageContents = $imagick->getImageBlob();
                if ($imageContents === false) {
                    throw new \Exception('Gagal membaca gambar halaman ke-' . ($index + 1));
                }

                list($width, $height) = getimagesizefromstring($imageContents);

                $baseName = ($index === 0) ? 'cover' : 'halaman' . $index;
                $fileName = 'buku/' . $bookDir . '/halaman/' . $baseName . '.webp';

                // 4. PAGE-LEVEL ATOMICITY: Transaksi dipindah ke dalam iterasi per halaman
                DB::beginTransaction();
                $uploadedToS3 = false;

                try {
                    // Proses S3
                    Storage::disk('s3')->put($fileName, $imageContents);
                    $uploadedToS3 = true;

                    // Proses Database
                    $halaman = Halaman::create([
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

                    // Hanya Commit untuk halaman ini!
                    DB::commit();

                } catch (\Exception $pageException) {
                    // Rollback HANYA untuk halaman yang gagal ini
                    DB::rollBack();
                    
                    // Cleanup HANYA file fisik halaman yang gagal ini (jika telanjur naik ke S3)
                    if ($uploadedToS3) {
                        Storage::disk('s3')->delete($fileName);
                    }

                    // Lempar exception ke atas agar tertangkap Job untuk mekanisme Retry!
                    throw $pageException; 
                }

                // Bersihkan RAM object halaman ini sebelum lanjut ke iterasi berikutnya
                $imagick->clear();
            }

            $buku->update([
                'is_processing' => false,
                'status_konversi' => true,
                'local_pdf_path' => null
            ]);

            Storage::disk('local')->delete($pdfPath);

        } catch (\Exception $e) {
            DB::rollBack();

            if (!empty($uploadedS3Files)) {
                Storage::disk('s3')->delete($uploadedS3Files);
            }

            $buku->update(['is_processing' => false]);
            
            throw $e;
        } finally {
            if (isset($imagick)) {
                $imagick->clear();
                $imagick->destroy();
            }

            if (file_exists($tempPdfPath)) {
                @unlink($tempPdfPath);
            }
        }
    }
}
