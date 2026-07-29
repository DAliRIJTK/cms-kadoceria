<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Buku;
use App\Services\BukuBundleService;

class ApiController extends Controller
{
    /**
     * Daftar informasi buku yang sudah dipublikasikan.
     * GET /api/get/dataInformasiBuku
     */
    public function dataInformasiBuku()
    {
        $bukuList = Buku::where('status_publikasi', 'Terbit')->get();

        $result = $bukuList->map(function ($buku) {
            $fileSize = null;
            if (!empty($buku->zip_bundle_path) && Storage::disk('s3')->exists($buku->zip_bundle_path)) {
                $bytes = Storage::disk('s3')->size($buku->zip_bundle_path);
                $fileSize = round($bytes / 1048576, 1) . ' MB';
            }

            return [
                'id_buku'              => (string) $buku->id_buku,
                'judulBukuIndonesia'   => $buku->judul_idn,
                'judulBukuSunda'       => $buku->judul_sn,
                'penulis'              => $buku->penulis,
                'illustrator'          => $buku->ilustrator,
                'coverImagePath'       => $buku->path_cover
                                            ? Storage::disk('s3')->url($buku->path_cover)
                                            : null,
                'descriptionsIndonesia' => $buku->deskripsi_idn,
                'descriptionsSunda'    => $buku->deskripsi_sn,
                'primaryColor'         => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
                'secondaryColor'       => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
                'version'              => (int) $buku->version,
                'fileSize'             => $fileSize,
            ];
        })->values()->toArray();

        return response()->json($result);
    }

    /**
     * URL download bundle ZIP buku.
     * GET /api/get/kontenBuku?id={id}
     */
    public function kontenBuku(Request $request, $id = null)
    {
        $idBuku = $id ?? $request->input('id');

        if (!$idBuku) {
            return response()->json(['error' => 'Parameter id diperlukan'], 400);
        }

        $buku = Buku::where('id_buku', $idBuku)
                    ->where('status_publikasi', 'Terbit')
                    ->first();

        if (!$buku) {
            return response()->json(['error' => 'Buku tidak ditemukan atau belum dipublikasikan'], 404);
        }

        $zipRelPath = null;

        if (!empty($buku->zip_bundle_path) && Storage::disk('s3')->exists($buku->zip_bundle_path)) {
            $zipRelPath = $buku->zip_bundle_path;
        }

        if (!$zipRelPath) {
            $files = Storage::disk('s3')->files('buku/bundle');
            $bundleFiles = array_values(array_filter($files, fn($path) => preg_match('/^buku\/bundle\/' . preg_quote($buku->id_buku, '/') . '_v.*\.zip$/', $path) === 1));
            if (!empty($bundleFiles)) {
                usort($bundleFiles, fn($a, $b) => Storage::disk('s3')->lastModified($b) <=> Storage::disk('s3')->lastModified($a));
                $zipRelPath = $bundleFiles[0];
            }
        }

        if (!$zipRelPath) {
            return response()->json(['error' => 'Bundle buku belum tersedia. Coba publikasikan ulang.'], 404);
        }

        $downloadUrl = $zipRelPath ? Storage::disk('s3')->url($zipRelPath) : null;
        return response()->json(['downloadUrl' => $downloadUrl]);
    }

    /*
     * Detail lengkap buku beserta halaman & area interaktif (format offline bundle).
     * GET /api/get/detailBuku?id={id}
     */
    public function detailBuku(Request $request, $id = null)
    {
        $idBuku = $id ?? $request->input('id');

        if (!$idBuku) {
            return response()->json(['error' => 'Parameter id diperlukan'], 400);
        }

        $buku = Buku::where('id_buku', $idBuku)
                    ->where('status_publikasi', 'Terbit')
                    ->first();

        if (!$buku) {
            return response()->json(['error' => 'Buku tidak ditemukan atau belum dipublikasikan'], 404);
        }

        // Ambil array format JSON yang dijamin sudah 100% konsisten dengan Bundle ZIP
        $bundleService = app(\App\Services\BukuBundleService::class);
        $data = $bundleService->getMetadataArray($buku);

        return response()->json($data);
    }

    /**
     * Pemicu manual generate bundle buku via API.
     * POST /api/buku/{id}/generate
     */
    public function generateBundle(Request $request, $id, BukuBundleService $bundleService)
    {
        $buku = Buku::where('id_buku', $id)->first();

        if (!$buku) {
            return response()->json(['error' => 'Buku tidak ditemukan'], 404);
        }

        if ($buku->status_publikasi !== 'Terbit') {
            return response()->json(['error' => 'Buku harus dipublikasikan (status Terbit) terlebih dahulu sebelum dapat di-generate'], 400);
        }

        try {
            GenerateBundleJob::dispatch($buku);

            $fileSize = null;
            if (!empty($buku->zip_bundle_path) && Storage::disk('s3')->exists($buku->zip_bundle_path)) {
                $bytes = Storage::disk('s3')->size($buku->zip_bundle_path);
                $fileSize = round($bytes / 1048576, 1) . ' MB';
            }

            return response()->json([
                'success' => true,
                'message' => 'Bundle dan metadata buku berhasil di-generate',
                'downloadUrl' => $buku->zip_bundle_path ? Storage::disk('s3')->url($buku->zip_bundle_path) : null,
                'fileSize' => $fileSize,
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal memproses bundle: ' . $e->getMessage()
            ], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function rgbToHex(?string $value, string $default = '#FFFFFF'): string
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

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s_-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '_', $text);
        return trim($text, '_');
    }
}
