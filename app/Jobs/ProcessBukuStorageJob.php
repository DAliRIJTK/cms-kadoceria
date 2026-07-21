<?php

namespace App\Jobs;

use App\Models\Buku;
use App\Services\BukuBundleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBukuStorageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900; // Samakan dengan job lain karena melibatkan S3

    public function __construct(public Buku $buku) {}

    public function handle(BukuBundleService $bundleService): void
    {
        // 1. Eksekusi sinkronisasi file S3 terlebih dahulu
        $this->buku->syncStorageStructure();

        // 2. Generate ulang metadata/bundle setelah sinkronisasi S3 selesai
        //    untuk memastikan path yang digunakan adalah path final.
        if ($this->buku->status_publikasi === 'Terbit') {
            $bundleService->generateAndPackageBundle($this->buku);
        } else {
            $bundleService->generateMetadataJson($this->buku);
        }
    }
}