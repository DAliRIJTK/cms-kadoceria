<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

class BukuController extends Controller
{

    public function index(Request $request)
    {
        $query = Buku::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul_idn', 'like', "%{$search}%")
                  ->orWhere('judul_sn', 'like', "%{$search}%")
                  ->orWhere('penulis', 'like', "%{$search}%")
                  ->orWhere('ilustrator', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_publikasi', $request->status);
        }

        if ($request->filled('sort')) {
            match ($request->sort) {
                'title_asc'   => $query->orderBy('judul_idn', 'asc'),
                'title_desc'  => $query->orderBy('judul_idn', 'desc'),
                'date_newest' => $query->orderBy('created_at', 'desc'),
                'date_oldest' => $query->orderBy('created_at', 'asc'),
                default       => $query->orderBy('created_at', 'desc'),
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $buku = $query->paginate(8);
        return view('buku.index', compact('buku'));
    }

    public function create()
    {
        return view('buku.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul_idn'      => 'required|string|max:255',
            'judul_sn'       => 'nullable|string|max:255',
            'penulis'        => 'nullable|string|max:100',
            'ilustrator'     => 'nullable|string|max:100',
            'deskripsi_idn'  => 'nullable|string',
            'deskripsi_sn'   => 'nullable|string',
            'warna_primer'   => 'nullable|string|max:20',
            'warna_sekunder' => 'nullable|string|max:20',
            'pdf_file'       => 'required|file|mimes:pdf|max:51200',
        ], [
            'pdf_file.max'   => 'Ukuran file PDF maksimal 50MB.',
            'pdf_file.mimes' => 'File harus berformat PDF.',
        ]);

        $judulIdn = $request->judul_idn;
        $judulSn  = $request->judul_sn;

        $duplicateTitle = Buku::where(function ($q) use ($judulIdn) {
                $q->whereRaw('LOWER(judul_idn) = ?', [strtolower($judulIdn)])
                ->orWhereRaw('LOWER(judul_sn) = ?', [strtolower($judulIdn)]);
            })
            ->when($judulSn, function ($query) use ($judulSn) {
                $query->orWhere(function ($q) use ($judulSn) {
                    $q->whereRaw('LOWER(judul_idn) = ?', [strtolower($judulSn)])
                    ->orWhereRaw('LOWER(judul_sn) = ?', [strtolower($judulSn)]);
                });
            })
            ->exists();

        if ($duplicateTitle) {
            return back()->withInput()
                ->withErrors(['duplicate_title' => 'Judul buku (Bahasa Indonesia/Sunda) sudah digunakan pada buku lain. Silakan gunakan judul yang berbeda.']);
        }

        $uploadedFileName = $request->file('pdf_file')->getClientOriginalName();
        if (Buku::where('original_pdf_name', $uploadedFileName)->exists()) {
            return back()->withInput()
                ->withErrors(['duplicate_title' => "File PDF \"{$uploadedFileName}\" sudah pernah diunggah. Gunakan nama file yang berbeda."]);
        }

        DB::beginTransaction();
        try {
            $pdfPath = $request->file('pdf_file')->store('buku/pdf', 'public');

            $buku = Buku::create([
                'id_pengelola'      => Auth::id(),
                'judul_idn'         => $request->judul_idn,
                'judul_sn'          => $request->judul_sn        ?? null,
                'penulis'           => $request->penulis         ?? null,
                'ilustrator'        => $request->ilustrator      ?? null,
                'deskripsi_idn'     => $request->deskripsi_idn   ?? null,
                'deskripsi_sn'      => $request->deskripsi_sn    ?? null,
                'warna_primer'      => $this->sanitizeRgb($request->warna_primer,   '99,102,241'),
                'warna_sekunder'    => $this->sanitizeRgb($request->warna_sekunder, '168,85,247'),
                'path_cover'        => null,
                'status_publikasi'  => 'Draft',
                'original_pdf_name' => $uploadedFileName,
            ]);

            $fullPdfPath = storage_path('app/public/' . $pdfPath);
            $imagick = new \Imagick();
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256);
            $imagick->setResolution(120, 120);
            $imagick->readImage($fullPdfPath);

            foreach ($imagick as $index => $page) {
                $page->setImageFormat('webp');
                $page->setImageCompressionQuality(80);

                $fileName = 'buku/halaman/' . uniqid() . '_halaman_' . ($index + 1) . '.webp';
                $fullPath = storage_path('app/public/' . $fileName);

                $dir = dirname($fullPath);
                if (!file_exists($dir)) mkdir($dir, 0777, true);

                if (!$page->writeImage($fullPath) || !file_exists($fullPath)) {
                    throw new \Exception('Gagal menyimpan gambar halaman ke-' . ($index + 1));
                }

                Halaman::create([
                    'id_buku'       => $buku->id_buku,
                    'nomor_halaman' => $index + 1,
                    'path_gambar'   => $fileName,
                ]);

                if ($index === 0) {
                    $buku->path_cover = $fileName;
                    $buku->save();
                }
            }

            $imagick->clear();
            $imagick->destroy();
            Storage::disk('public')->delete($pdfPath);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($pdfPath)) Storage::disk('public')->delete($pdfPath);
            \Log::error('BukuController@store: ' . $e->getMessage());
            return back()->withInput()
                ->withErrors(['error' => 'Gagal memproses PDF: ' . $e->getMessage()]);
        }

        // [FIX #1] Redirect ke halaman show/informasi buku, bukan ke daftar buku
        return redirect()->route('buku.show', $buku->id_buku)
            ->with('success', 'Buku berhasil ditambahkan & diproses!');
    }

    public function show(Buku $buku)
    {
        $buku->load('halaman');

        $this->fixCoverIfMissing($buku);

        return view('buku.show', compact('buku'));
    }

    private function fixCoverIfMissing(Buku $buku): void
    {
        $needsFix = empty($buku->path_cover)
            || !Storage::disk('public')->exists($buku->path_cover);

        if (!$needsFix) return;

        $firstPage = $buku->halaman()
            ->orderBy('nomor_halaman', 'asc')
            ->first();

        if ($firstPage && $firstPage->path_gambar && Storage::disk('public')->exists($firstPage->path_gambar)) {
            $buku->path_cover = $firstPage->path_gambar;
            $buku->save();
        }
    }

    public function edit(Buku $buku)
    {
        if ($buku->status_publikasi === 'Terbit') {
            return redirect()->route('buku.show', $buku->id_buku)
                ->withErrors(['publication' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
        }
        return view('buku.edit', compact('buku'));
    }

    public function update(Request $request, Buku $buku)
    {
        if ($buku->status_publikasi === 'Terbit') {
            return back()->withErrors(['error' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
        }

        $validated = $request->validate([
            'judul_idn'      => 'required|string|max:255',
            'judul_sn'       => 'nullable|string|max:255',
            'penulis'        => 'nullable|string|max:100',
            'ilustrator'     => 'nullable|string|max:100',
            'deskripsi_idn'  => 'nullable|string',
            'deskripsi_sn'   => 'nullable|string',
            'warna_primer'   => 'nullable|string|max:20',
            'warna_sekunder' => 'nullable|string|max:20',
        ]);

        $validated['warna_primer']   = $this->sanitizeRgb($validated['warna_primer']   ?? null, '99,102,241');
        $validated['warna_sekunder'] = $this->sanitizeRgb($validated['warna_sekunder'] ?? null, '168,85,247');

        $buku->update($validated);

        return back()->with('success', 'Informasi buku berhasil diperbarui');
    }

    public function updateStatus(Buku $buku, Request $request)
    {
        $request->validate(['status_publikasi' => 'required|in:Draft,Terbit']);
        $newStatus = $request->status_publikasi;

        if ($newStatus === 'Terbit') {
            $errors = [];
            if (empty($buku->judul_idn)) {
                $errors[] = 'Judul buku harus diisi';
            }
            if ($buku->halaman()->count() === 0) {
                $errors[] = 'Buku harus memiliki minimal 1 halaman';
            }

            $halamanList = $buku->halaman()->with('areaInteraktif')->get();

            foreach ($halamanList as $page) {
                if (empty($page->narasi_indo)) {
                    $errors[] = "Halaman {$page->nomor_halaman} tidak memiliki audio narasi Indonesia.";
                }
                if (empty($page->id_audio_latar)) {
                    $errors[] = "Halaman {$page->nomor_halaman} tidak memiliki audio backsound.";
                }

                foreach ($page->areaInteraktif as $area) {
                    if (empty($area->audio_indo)) {
                        $errors[] = "Halaman {$page->nomor_halaman}: Area interaktif '{$area->label}' tidak memiliki file audio yang ditautkan.";
                    }
                }
            }

            if (!empty($errors)) {
                return back()->withErrors(['publication' => 'Buku belum dapat dipublikasikan. ' . implode(' | ', $errors)]);
            }
        }

        if ($buku->status_publikasi === 'Terbit' && $newStatus === 'Draft') {
            if (!($request->has('confirm_unpublish') && $request->confirm_unpublish === 'yes')) {
                return back()->withErrors(['publication' => 'Konfirmasi pembatalan publikasi diperlukan.']);
            }
        }

        $buku->update(['status_publikasi' => $newStatus]);

        if ($newStatus === 'Terbit') {
            $this->generateAndPackageBundle($buku);
        }

        $statusLabel = $newStatus === 'Terbit' ? 'dipublikasikan' : 'disimpan sebagai draft';
        return back()->with('success', "Buku berhasil {$statusLabel}");
    }

    
    private function generateAndPackageBundle(Buku $buku): void
    {
        try {
            $buku->load(['halaman' => function ($q) {
                $q->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman');
            }]);

            $this->generateMetadataJson($buku);
            $this->generateZipBundle($buku);

        } catch (\Exception $e) {
            \Log::error('generateAndPackageBundle error (book ' . $buku->id_buku . '): ' . $e->getMessage());
        }
    }

    private function generateMetadataJson(Buku $buku): void
    {
        $halaman = $buku->relationLoaded('halaman')
            ? $buku->halaman
            : $buku->halaman()->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman')->get();

        $metadata = [
            'id'                => (string) $buku->id_buku,
            'judul_idn'         => $buku->judul_idn,
            'judul_sn'          => $buku->judul_sn,
            'penulis'           => $buku->penulis,
            'ilustrator'        => $buku->ilustrator,
            'deskripsi_idn'     => $buku->deskripsi_idn,
            'deskripsi_sn'      => $buku->deskripsi_sn,
            'warna_primer'      => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
            'warna_sekunder'    => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
            'cover'             => $buku->path_cover ? asset('storage/' . $buku->path_cover) : null,
            'status_publikasi'  => $buku->status_publikasi,
            'tanggal_publikasi' => $buku->updated_at->toIso8601String(),
            'total_halaman'     => $halaman->count(),
            'halaman'           => $halaman->map(function ($page) {
                return [
                    'id'           => (string) $page->id_halaman,
                    'nomor'        => $page->nomor_halaman,
                    'gambar'       => asset('storage/' . $page->path_gambar),
                    'narasi_indo'  => $page->narasi_indo  ? asset('storage/' . $page->narasi_indo)  : null,
                    'narasi_sunda' => $page->narasi_sunda ? asset('storage/' . $page->narasi_sunda) : null,
                    'backsound'    => $page->audioLatar   ? asset('storage/' . $page->audioLatar->path_file) : null,
                    'area_interaktif' => $page->areaInteraktif->map(function ($area) {
                        return [
                            'id'          => (string) $area->id_area,
                            'label'       => $area->label        ?? null,
                            'x'           => $area->x,
                            'y'           => $area->y,
                            'x_pct'       => $area->x_pct        ?? null,
                            'y_pct'       => $area->y_pct        ?? null,
                            'w_pct'       => $area->w_pct        ?? null,
                            'h_pct'       => $area->h_pct        ?? null,
                            'lebar'       => $area->lebar_area,
                            'tinggi'      => $area->panjang_area,
                            'audio_indo'  => $area->audio_indo   ? asset('storage/' . $area->audio_indo)  : null,
                            'audio_sunda' => $area->audio_sunda  ? asset('storage/' . $area->audio_sunda) : null,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        Storage::disk('public')->put(
            'buku/metadata/' . $buku->id_buku . '/metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    private function generateZipBundle(Buku $buku): void
    {
        $halaman = $buku->relationLoaded('halaman')
            ? $buku->halaman
            : $buku->halaman()->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman')->get();

        $folderName = $this->slugify($buku->judul_idn);

        $tmpDir = storage_path('app/tmp/bundle_' . $buku->id_buku . '_' . time());
        @mkdir($tmpDir . '/images', 0777, true);
        @mkdir($tmpDir . '/audio',  0777, true);

        $coverRelPath = null;
        if ($buku->path_cover) {
            $srcCover = storage_path('app/public/' . $buku->path_cover);
            if (file_exists($srcCover)) {
                $coverFilename = 'cover' . '.' . pathinfo($buku->path_cover, PATHINFO_EXTENSION);
                copy($srcCover, $tmpDir . '/images/' . $coverFilename);
                $coverRelPath = 'images/' . $coverFilename;
            }
        }

        $pagesData = [];
        foreach ($halaman as $page) {

            $pageRelPath = null;
            if ($page->path_gambar) {
                $srcImg = storage_path('app/public/' . $page->path_gambar);
                if (file_exists($srcImg)) {
                    $pageFilename = 'page_' . $page->nomor_halaman . '.' . pathinfo($page->path_gambar, PATHINFO_EXTENSION);
                    copy($srcImg, $tmpDir . '/images/' . $pageFilename);
                    $pageRelPath = 'images/' . $pageFilename;
                }
            }

            $backsoundRelPath = null;
            if ($page->audioLatar && $page->audioLatar->path_file) {
                $srcAudio = storage_path('app/public/' . $page->audioLatar->path_file);
                if (file_exists($srcAudio)) {
                    $bgmFilename = 'bgm_' . $page->audioLatar->id_audio_latar . '.' . pathinfo($page->audioLatar->path_file, PATHINFO_EXTENSION);
                    if (!file_exists($tmpDir . '/audio/' . $bgmFilename)) {
                        copy($srcAudio, $tmpDir . '/audio/' . $bgmFilename);
                    }
                    $backsoundRelPath = 'audio/' . $bgmFilename;
                }
            }

            $narasiIdRelPath = null;
            if ($page->narasi_indo) {
                $srcNarasi = storage_path('app/public/' . $page->narasi_indo);
                if (file_exists($srcNarasi)) {
                    $narasiIdFilename = 'narasi_id_' . $page->id_halaman . '.' . pathinfo($page->narasi_indo, PATHINFO_EXTENSION);
                    copy($srcNarasi, $tmpDir . '/audio/' . $narasiIdFilename);
                    $narasiIdRelPath = 'audio/' . $narasiIdFilename;
                }
            }

            $narasiSuRelPath = null;
            if ($page->narasi_sunda) {
                $srcNarasiSu = storage_path('app/public/' . $page->narasi_sunda);
                if (file_exists($srcNarasiSu)) {
                    $narasiSuFilename = 'narasi_su_' . $page->id_halaman . '.' . pathinfo($page->narasi_sunda, PATHINFO_EXTENSION);
                    copy($srcNarasiSu, $tmpDir . '/audio/' . $narasiSuFilename);
                    $narasiSuRelPath = 'audio/' . $narasiSuFilename;
                }
            }

            $interactiveObjects = [];
            foreach ($page->areaInteraktif as $area) {

                $audioObjIdRelPath = null;
                if ($area->audio_indo) {
                    $srcObjId = storage_path('app/public/' . $area->audio_indo);
                    if (file_exists($srcObjId)) {
                        $objIdFilename = 'objek_id_' . $area->id_area . '.' . pathinfo($area->audio_indo, PATHINFO_EXTENSION);
                        copy($srcObjId, $tmpDir . '/audio/' . $objIdFilename);
                        $audioObjIdRelPath = 'audio/' . $objIdFilename;
                    }
                }

                $audioObjSuRelPath = null;
                if ($area->audio_sunda) {
                    $srcObjSu = storage_path('app/public/' . $area->audio_sunda);
                    if (file_exists($srcObjSu)) {
                        $objSuFilename = 'objek_su_' . $area->id_area . '.' . pathinfo($area->audio_sunda, PATHINFO_EXTENSION);
                        copy($srcObjSu, $tmpDir . '/audio/' . $objSuFilename);
                        $audioObjSuRelPath = 'audio/' . $objSuFilename;
                    }
                }

                $interactiveObjects[] = [
                    'x'             => (int)   $area->x,
                    'y'             => (int)   $area->y,
                    'width'         => (int)   $area->lebar_area,
                    'height'        => (int)   $area->panjang_area,
                    'audioObjectId' => $audioObjIdRelPath,
                    'audioObjectSd' => $audioObjSuRelPath,
                ];
            }

            $pagesData[] = [
                'image'              => $pageRelPath,
                'backsound'          => $backsoundRelPath,
                'widthImage'         => (int) ($page->lebar_halaman  ?? 0),
                'heightImage'        => (int) ($page->panjang_halaman ?? 0),
                'narationId'         => $narasiIdRelPath,
                'narationSd'         => $narasiSuRelPath,
                'interactiveObjects' => $interactiveObjects,
            ];
        }

        $dataJson = [
            'id'           => (string) $buku->id_buku,
            'title_id'     => $buku->judul_idn,
            'title_su'     => $buku->judul_sn,
            'folderName'   => $folderName,
            'description_id' => $buku->deskripsi_idn,
            'description_su' => $buku->deskripsi_sn,
            'author'       => $buku->penulis,
            'illustrator'  => $buku->ilustrator,
            'coverImage'   => $coverRelPath,
            'theme'        => [
                'primary'   => $this->rgbToHex($buku->warna_primer,   '#FFFFFF'),
                'secondary' => $this->rgbToHex($buku->warna_sekunder, '#FFFFFF'),
            ],
            'pages' => $pagesData,
        ];

        file_put_contents(
            $tmpDir . '/data.json',
            json_encode($dataJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $zipDir      = storage_path('app/public/buku/bundle');
        $zipFilename = $buku->id_buku . '_v' . ($buku->updated_at->timestamp) . '.zip';
        $zipPath     = $zipDir . '/' . $zipFilename;

        if (!file_exists($zipDir)) mkdir($zipDir, 0777, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Tidak dapat membuat file ZIP: ' . $zipPath);
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tmpDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath    = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tmpDir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            $zip->addFile($filePath, $relativePath);
        }

        $zip->close();

        $oldFiles = glob($zipDir . '/' . $buku->id_buku . '_v*.zip');
        foreach ((array) $oldFiles as $oldFile) {
            if (realpath($oldFile) !== realpath($zipPath)) {
                @unlink($oldFile);
            }
        }

        $buku->update(['zip_bundle_path' => 'buku/bundle/' . $zipFilename]);

        $this->deleteTmpDir($tmpDir);
    }


    public function apiDataInformasiBuku()
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

    public function apiKontenBuku(Request $request)
    {
        $idBuku = $request->query('id');

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

    public function destroy(Buku $buku)
    {
        try {
            foreach ($buku->halaman as $halaman) {
                foreach ($halaman->areaInteraktif as $area) {
                    foreach (['audio_indo', 'audio_sunda'] as $field) {
                        if ($area->$field && Storage::disk('public')->exists($area->$field)) {
                            Storage::disk('public')->delete($area->$field);
                        }
                    }
                }
                if ($halaman->path_gambar && Storage::disk('public')->exists($halaman->path_gambar)) {
                    Storage::disk('public')->delete($halaman->path_gambar);
                }
            }

            if ($buku->path_cover && Storage::disk('public')->exists($buku->path_cover)) {
                Storage::disk('public')->delete($buku->path_cover);
            }

            $metaPath = 'buku/metadata/' . $buku->id_buku . '/metadata.json';
            if (Storage::disk('public')->exists($metaPath)) {
                Storage::disk('public')->delete($metaPath);
            }

            $zipDir = storage_path('app/public/buku/bundle');
            foreach ((array) glob($zipDir . '/' . $buku->id_buku . '_v*.zip') as $f) {
                @unlink($f);
            }

            $buku->delete();
            return redirect('/buku')->with('success', 'Buku berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus buku: ' . $e->getMessage()]);
        }
    }

    public function dashboard(Request $request)
    {
        $query = Buku::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul_idn', 'like', "%{$search}%")
                  ->orWhere('judul_sn', 'like', "%{$search}%")
                  ->orWhere('penulis', 'like', "%{$search}%")
                  ->orWhere('ilustrator', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status_publikasi', $request->status);
        }

        if ($request->filled('sort')) {
            match ($request->sort) {
                'title_asc'   => $query->orderBy('judul_idn', 'asc'),
                'title_desc'  => $query->orderBy('judul_idn', 'desc'),
                'date_newest' => $query->orderBy('created_at', 'desc'),
                'date_oldest' => $query->orderBy('created_at', 'asc'),
                default       => $query->orderBy('created_at', 'desc'),
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $buku           = $query->paginate(8);
        $totalBooks     = Buku::count();
        $publishedBooks = Buku::where('status_publikasi', 'Terbit')->count();
        $draftBooks     = Buku::where('status_publikasi', 'Draft')->count();
        $totalPages     = Halaman::count();

        return view('dashboard', compact('buku', 'totalBooks', 'publishedBooks', 'draftBooks', 'totalPages'));
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

    private function sanitizeRgb(?string $value, string $default): string
    {
        if (!$value) return $default;

        $value = trim($value);

        if (preg_match('/^#([0-9A-Fa-f]{6})$/', $value, $m)) {
            $r = hexdec(substr($m[1], 0, 2));
            $g = hexdec(substr($m[1], 2, 2));
            $b = hexdec(substr($m[1], 4, 2));
            return "{$r},{$g},{$b}";
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) !== 3) return $default;

        $sanitized = array_map(function ($v) {
            return max(0, min(255, (int) $v));
        }, $parts);

        return implode(',', $sanitized);
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s_-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '_', $text);
        return trim($text, '_');
    }

    private function deleteTmpDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }

    public function apiDetailBuku(Request $request)
    {
        $idBuku = $request->query('id');

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
            $pageExt     = pathinfo($page->path_gambar ?? '', PATHINFO_EXTENSION);
            $pageRelPath = $page->path_gambar ? 'images/page_' . $page->nomor_halaman . '.' . $pageExt : null;

            $backsoundRelPath = null;
            if ($page->audioLatar) {
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

            $interactiveObjects = $page->areaInteraktif->map(function ($area) {
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
            })->values()->toArray();

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
}