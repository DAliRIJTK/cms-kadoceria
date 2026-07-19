<?php

namespace App\Jobs;

use App\Models\Buku;
use App\Services\BukuBundleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBundleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set batas waktu eksekusi
    public $timeout = 900;

    public function __construct(public Buku $buku) {}

    public function handle(BukuBundleService $bundleService): void
    {
        $bundleService->generateAndPackageBundle($this->buku);
    }
}