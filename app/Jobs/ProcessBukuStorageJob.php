<?php

namespace App\Jobs;

use App\Models\Buku;
use App\Services\BukuBundleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessBukuStorageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    public function __construct(
        public Buku $buku, 
        public ?string $oldTitle = null
    ) {}

    public function handle(BukuBundleService $bundleService): void
    {
        try {
        // 1. Eksekusi perpindahan folder S3 dan Path DB jika judul berubah
        if ($this->oldTitle && $this->oldTitle !== $this->buku->judul_idn) {
            $oldBookDir = $this->buku->slugify($this->oldTitle);
            $newBookDir = $this->buku->slugify($this->buku->judul_idn);

            if ($oldBookDir !== $newBookDir) {
                $oldPath = 'buku/' . $oldBookDir;
                $newPath = 'buku/' . $newBookDir;
                
                $this->moveDirectory($oldPath, $newPath);

                // Update path di database
                if ($this->buku->path_cover) {
                    $this->buku->path_cover = str_replace($oldPath . '/', $newPath . '/', $this->buku->path_cover);
                }
                if ($this->buku->zip_bundle_path) {
                    $this->buku->zip_bundle_path = str_replace($oldPath . '/', $newPath . '/', $this->buku->zip_bundle_path);
                }
                $this->buku->save();

                foreach ($this->buku->halaman as $halaman) {
                    if ($halaman->path_gambar) $halaman->path_gambar = str_replace($oldPath . '/', $newPath . '/', $halaman->path_gambar);
                    if ($halaman->narasi_indo) $halaman->narasi_indo = str_replace($oldPath . '/', $newPath . '/', $halaman->narasi_indo);
                    if ($halaman->narasi_sunda) $halaman->narasi_sunda = str_replace($oldPath . '/', $newPath . '/', $halaman->narasi_sunda);
                    $halaman->save();

                    foreach ($halaman->areaInteraktif as $area) {
                        if ($area->audio_indo) $area->audio_indo = str_replace($oldPath . '/', $newPath . '/', $area->audio_indo);
                        if ($area->audio_sunda) $area->audio_sunda = str_replace($oldPath . '/', $newPath . '/', $area->audio_sunda);
                        $area->save();
                    }
                }
            }
        }

        // 2. Sinkronisasi S3 lanjutan
        $this->buku->syncStorageStructure();

        // 3. Generate ulang metadata/bundle
        if ($this->buku->status_publikasi === 'Terbit') {
            $bundleService->generateAndPackageBundle($this->buku);
        } else {
            $bundleService->generateMetadataJson($this->buku);
        }
        
        } catch (\Exception $e) {
            // Tangani kesalahan jika diperlukan, misalnya log error
            \Log::error('Job Storage Buku Gagal: ' . $e->getMessage());
            throw $e;
        } finally {
            $this->buku->update(['is_processing' => false]);
        }
    }

    private function moveDirectory(string $oldPath, string $newPath): void
    {
        $files = Storage::disk('s3')->allFiles($oldPath);
        foreach ($files as $file) {
            $relativePath = str_replace($oldPath . '/', '', $file);
            $newFilePath = $newPath . '/' . $relativePath;
            Storage::disk('s3')->makeDirectory(dirname($newFilePath));
            Storage::disk('s3')->copy($file, $newFilePath);
        }
        Storage::disk('s3')->deleteDirectory($oldPath);
    }
}