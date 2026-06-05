<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Pdf;

class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $buku;
    public function __construct(Buku $buku)
    {
        $this->buku = $buku;
    }

    public function handle()
    {
        $relativePdfPath = "books/{$this->buku->nama_folder}/mentahan_{$this->buku->nama_folder}.pdf";
        
        if (!Storage::disk('public')->exists($relativePdfPath)) {
            logger("ProcessPdfJob Gagal: File PDF mentahan tidak ditemukan untuk Buku ID {$this->buku->id_buku}");
            return;
        }

        $absolutePdfPath = Storage::disk('public')->path($relativePdfPath);
        
        try {
            $pdf = new Pdf($absolutePdfPath);
            $totalHalaman = $pdf->pageCount();
            $outputFolder = "books/{$this->buku->nama_folder}/halaman";

            Storage::disk('public')->makeDirectory($outputFolder);

            for ($i = 1; $i <= $totalHalaman; $i++) {
                $namaFileGambar = "halaman_{$i}.webp";
                $relativeImagePath = "{$outputFolder}/{$namaFileGambar}";
                $absoluteImagePath = Storage::disk('public')->path($relativeImagePath);

                $pdf->selectPage($i)
                    ->format(\Spatie\PdfToImage\Enums\OutputFormat::Webp)
                    ->save($absoluteImagePath);

                if (file_exists($absoluteImagePath)) {
                    list($width, $height) = getimagesize($absoluteImagePath);

                    Halaman::updateOrCreate(
                        [
                            'id_buku'       => $this->buku->id_buku,
                            'nomor_halaman' => $i
                        ],
                        [
                            'path_gambar'     => $relativeImagePath,
                            'panjang_halaman' => $height,
                            'lebar_halaman'   => $width,
                        ]
                    );
                }
            }

        } catch (\Exception $e) {
            logger("Error saat memproses PDF Buku ID {$this->buku->id_buku}: " . $e->getMessage());
            throw $e;
        }
    }
}