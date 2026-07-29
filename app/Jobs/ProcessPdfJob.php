<?php

namespace App\Jobs;

use App\Models\Buku;
use App\Services\ProcessPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set batas waktu eksekusi (contoh: 15 menit)
    public $timeout = 900; 

    public $tries = 3;

    public function __construct(
        public Buku $buku, 
        public string $pdfPath
    ) {}

    public function handle(ProcessPdfService $pdfService): void
    {
        $pdfService->process($this->buku, $this->pdfPath);
    }

    public function failed(\Throwable $exception): void
    {
        // Ubah is_processing jadi false agar buku tidak terkunci (walaupun gagal sebagian)
        $this->buku->update(['is_processing' => false]);

        \Illuminate\Support\Facades\Log::error("Konversi PDF Gagal Total pada Buku ID {$this->buku->id_buku}: " . $exception->getMessage());
    }
}