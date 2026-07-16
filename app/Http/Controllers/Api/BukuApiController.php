<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buku;
use App\Services\BukuBundleService;

class BukuApiController extends Controller
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
            if (!empty($buku->zip_bundle_path)) {
                $zipAbsPath = storage_path('app/public/' . $buku->zip_bundle_path);
                if (file_exists($zipAbsPath)) {
                    $bytes = filesize($zipAbsPath);
                    $fileSize = round($bytes / 1048576, 1) . ' MB';
                }
            }

            return [
                'id_buku'              => (string) $buku->id_buku,
                'judulBukuIndonesia'   => $buku->judul_idn,
                'judulBukuSunda'       => $buku->judul_sn,
                'penulis'              => $buku->penulis,
                'illustrator'          => $buku->ilustrator,
                'coverImagePath'       => $buku->path_cover
                                            ? asset('storage/' . $buku->path_cover)
                                            : null,
                'descriptionsIndonesia' => $buku->deskripsi_idn,
                'descriptionsSunda'    => $buku->deskripsi_sn,
                'primaryColor'         => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
                'secondaryColor'       => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
                'version'              => 1,
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

        if (!empty($buku->zip_bundle_path)) {
            $abs = storage_path('app/public/' . $buku->zip_bundle_path);
            if (file_exists($abs)) {
                $zipRelPath = $buku->zip_bundle_path;
            }
        }

        if (!$zipRelPath) {
            $pattern = storage_path('app/public/buku/bundle/' . $buku->id_buku . '_v*.zip');
            $files   = glob($pattern);
            if (!empty($files)) {
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                $abs        = $files[0];
                $zipRelPath = 'buku/bundle/' . basename($abs);
            }
        }

        if (!$zipRelPath) {
            return response()->json(['error' => 'Bundle buku belum tersedia. Coba publikasikan ulang.'], 404);
        }

        $downloadUrl = asset('storage/' . $zipRelPath);
        return response()->json(['downloadUrl' => $downloadUrl]);
    }

    /**
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

        $halaman = $buku->halaman()
                        ->with(['areaInteraktif', 'audioLatar'])
                        ->orderBy('nomor_halaman')
                        ->get();

        $folderName = $this->slugify($buku->judul_idn);
        $coverRelPath = null;
        if ($buku->path_cover) {
            $ext          = pathinfo($buku->path_cover, PATHINFO_EXTENSION);
            $coverRelPath = 'images/cover.' . $ext;
        }

        $pagesData = $halaman->map(function ($page) {
            $isCover     = $page->nomor_halaman === 1;
            $pageExt     = pathinfo($page->path_gambar ?? '', PATHINFO_EXTENSION);
            $pageRelPath = $page->path_gambar ? 'images/page_' . $page->nomor_halaman . '.' . $pageExt : null;

            $backsoundRelPath = null;
            if (!$isCover && $page->audioLatar) {
                $bgmExt           = pathinfo($page->audioLatar->path_file, PATHINFO_EXTENSION);
                $backsoundRelPath = 'audio/bgm_' . $page->audioLatar->id_audio_latar . '.' . $bgmExt;
            }

            $narasiIdRelPath = null;
            if ($page->narasi_indo) {
                $ext             = pathinfo($page->narasi_indo, PATHINFO_EXTENSION);
                $narasiIdRelPath = 'audio/narasi_id_' . $page->id_halaman . '.' . $ext;
            }

            $narasiSuRelPath = null;
            if ($page->narasi_sunda) {
                $ext             = pathinfo($page->narasi_sunda, PATHINFO_EXTENSION);
                $narasiSuRelPath = 'audio/narasi_su_' . $page->id_halaman . '.' . $ext;
            }

            $interactiveObjects = !$isCover ? $page->areaInteraktif->map(function ($area) {
                $audioIdRelPath = null;
                if ($area->audio_indo) {
                    $ext            = pathinfo($area->audio_indo, PATHINFO_EXTENSION);
                    $audioIdRelPath = 'audio/objek_id_' . $area->id_area . '.' . $ext;
                }

                $audioSuRelPath = null;
                if ($area->audio_sunda) {
                    $ext            = pathinfo($area->audio_sunda, PATHINFO_EXTENSION);
                    $audioSuRelPath = 'audio/objek_su_' . $area->id_area . '.' . $ext;
                }

                return [
                    'x'             => (int) $area->x,
                    'y'             => (int) $area->y,
                    'width'         => (int) $area->lebar_area,
                    'height'        => (int) $area->panjang_area,
                    'audioObjectId' => $audioIdRelPath,
                    'audioObjectSd' => $audioSuRelPath,
                ];
            })->values()->toArray() : [];

            return [
                'image'              => $pageRelPath,
                'backsound'          => $backsoundRelPath,
                'widthImage'         => (int) ($page->lebar_halaman  ?? 0),
                'heightImage'        => (int) ($page->panjang_halaman ?? 0),
                'narationId'         => $narasiIdRelPath,
                'narationSd'         => $narasiSuRelPath,
                'interactiveObjects' => $interactiveObjects,
            ];
        })->values()->toArray();

        $data = [
            'id'             => (string) $buku->id_buku,
            'title_id'       => $buku->judul_idn,
            'title_su'       => $buku->judul_sn,
            'folderName'     => $folderName,
            'description_id' => $buku->deskripsi_idn,
            'description_su' => $buku->deskripsi_sn,
            'author'         => $buku->penulis,
            'illustrator'    => $buku->ilustrator,
            'coverImage'     => $coverRelPath,
            'theme'          => [
                'primary'   => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
                'secondary' => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
            ],
            'pages' => $pagesData,
        ];

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
            $bundleService->generateAndPackageBundle($buku);

            $fileSize = null;
            if (!empty($buku->zip_bundle_path)) {
                $zipAbsPath = storage_path('app/public/' . $buku->zip_bundle_path);
                if (file_exists($zipAbsPath)) {
                    $bytes = filesize($zipAbsPath);
                    $fileSize = round($bytes / 1048576, 1) . ' MB';
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bundle dan metadata buku berhasil di-generate',
                'downloadUrl' => $buku->zip_bundle_path ? asset('storage/' . $buku->zip_bundle_path) : null,
                'fileSize' => $fileSize,
            ]);
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
