<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class BukuController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

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

    // ── Create ───────────────────────────────────────────────────────────────

    public function create()
    {
        return view('buku.create');
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        // ── Validation ──────────────────────────────────────────────────────
        $request->validate([
            'judul_idn'    => 'required|string|max:255',
            'judul_sn'     => 'nullable|string|max:255',
            'penulis'      => 'nullable|string|max:100',
            'ilustrator'   => 'nullable|string|max:100',
            'deskripsi_idn'=> 'nullable|string',
            'deskripsi_sn' => 'nullable|string',
            // 50 MB = 51200 KB
            'pdf_file'     => 'required|file|mimes:pdf|max:51200',
        ], [
            'pdf_file.max'   => 'Ukuran file PDF maksimal 50MB.',
            'pdf_file.mimes' => 'File harus berformat PDF.',
        ]);

        // ── Duplicate judul check ────────────────────────────────────────────
        $duplicateTitle = Buku::whereRaw('LOWER(judul_idn) = ?', [strtolower($request->judul_idn)])->exists();
        if ($duplicateTitle) {
            return back()
                ->withInput()
                ->withErrors(['duplicate_title' => 'Judul buku sudah ada. Silakan gunakan judul yang berbeda.']);
        }

        // ── Duplicate PDF filename check ─────────────────────────────────────
        // We store original file names in the buku table (original_pdf_name column).
        // If your schema doesn't have this column yet, add it via migration:
        //   $table->string('original_pdf_name')->nullable();
        $uploadedFileName = $request->file('pdf_file')->getClientOriginalName();

        $duplicatePdf = Buku::where('original_pdf_name', $uploadedFileName)->exists();
        if ($duplicatePdf) {
            return back()
                ->withInput()
                ->withErrors(['duplicate_title' => "File PDF \"{$uploadedFileName}\" sudah pernah diunggah. Gunakan nama file yang berbeda."]);
        }

        // ── Process ──────────────────────────────────────────────────────────
        DB::beginTransaction();

        try {
            // 1. Save PDF temporarily
            $pdfPath = $request->file('pdf_file')->store('buku/pdf', 'public');

            // 2. Create Buku record
            $buku = Buku::create([
                'id_pengelola'      => Auth::id(),
                'judul_idn'         => $request->judul_idn,
                'judul_sn'          => $request->judul_sn          ?? null,
                'penulis'           => $request->penulis           ?? null,
                'ilustrator'        => $request->ilustrator        ?? null,
                'deskripsi_idn'     => $request->deskripsi_idn     ?? null,
                'deskripsi_sn'      => $request->deskripsi_sn      ?? null,
                'path_cover'        => null,
                'status_publikasi'  => 'Draft',
                'original_pdf_name' => $uploadedFileName,
            ]);

            // 3. Convert PDF pages → WebP images via Imagick
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
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

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

            // 4. Remove temporary PDF
            Storage::disk('public')->delete($pdfPath);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }

            \Log::error('BukuController@store error: ' . $e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memproses PDF: ' . $e->getMessage()]);
        }

        return redirect('/buku')->with('success', 'Buku berhasil ditambahkan & diproses!');
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function show(Buku $buku)
    {
        $buku->load('halaman');
        return view('buku.show', compact('buku'));
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    public function edit(Buku $buku)
    {
        return view('buku.edit', compact('buku'));
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, Buku $buku)
    {
        $validated = $request->validate([
            'judul_idn'    => 'required|string|max:255',
            'judul_sn'     => 'nullable|string|max:255',
            'penulis'      => 'nullable|string|max:100',
            'ilustrator'   => 'nullable|string|max:100',
            'deskripsi_idn'=> 'nullable|string',
            'deskripsi_sn' => 'nullable|string',
        ]);

        $buku->update($validated);

        return back()->with('success', 'Informasi buku berhasil diperbarui');
    }

    // ── Update Status (Terbit / Draft) ───────────────────────────────────────

    public function updateStatus(Buku $buku, Request $request)
    {
        $request->validate([
            'status_publikasi' => 'required|in:Draft,Terbit',
        ]);

        $newStatus = $request->status_publikasi;

        if ($newStatus === 'Terbit') {
            $errors = [];

            if (empty($buku->judul_idn)) {
                $errors[] = 'Judul buku harus diisi';
            }
            if ($buku->halaman()->count() === 0) {
                $errors[] = 'Buku harus memiliki minimal 1 halaman';
            }

            if (!empty($errors)) {
                return back()->withErrors([
                    'publication' => 'Buku belum dapat dipublikasikan. ' . implode(' | ', $errors),
                ]);
            }
        }

        if ($buku->status_publikasi === 'Terbit' && $newStatus === 'Draft') {
            if (!($request->has('confirm_unpublish') && $request->confirm_unpublish === 'yes')) {
                return back()->withErrors([
                    'publication' => 'Konfirmasi pembatalan publikasi diperlukan.',
                ]);
            }
        }

        $buku->update(['status_publikasi' => $newStatus]);

        if ($newStatus === 'Terbit') {
            $this->generateMetadataJson($buku);
        }

        $statusLabel = $newStatus === 'Terbit' ? 'dipublikasikan' : 'disimpan sebagai draft';
        return back()->with('success', "Buku berhasil {$statusLabel}");
    }

    // ── Generate metadata.json ───────────────────────────────────────────────

    private function generateMetadataJson(Buku $buku): void
    {
        try {
            $halaman = $buku->halaman()->with('areaInteraktif')->orderBy('nomor_halaman')->get();

            $metadata = [
                'id'                => (string) $buku->id_buku,
                'judul_idn'         => $buku->judul_idn,
                'judul_sn'          => $buku->judul_sn,
                'penulis'           => $buku->penulis,
                'ilustrator'        => $buku->ilustrator,
                'deskripsi_idn'     => $buku->deskripsi_idn,
                'deskripsi_sn'      => $buku->deskripsi_sn,
                'cover'             => $buku->path_cover ? asset('storage/' . $buku->path_cover) : null,
                'status_publikasi'  => $buku->status_publikasi,
                'tanggal_publikasi' => $buku->updated_at->toIso8601String(),
                'total_halaman'     => $halaman->count(),
                'halaman'           => $halaman->map(function ($page) {
                    return [
                        'id'            => (string) $page->id_halaman,
                        'nomor'         => $page->nomor_halaman,
                        'gambar'        => asset('storage/' . $page->path_gambar),
                        'narasi_indo'   => $page->narasi_indo  ? asset('storage/' . $page->narasi_indo)  : null,
                        'narasi_sunda'  => $page->narasi_sunda ? asset('storage/' . $page->narasi_sunda) : null,
                        'backsound'     => $page->backsound    ? asset('storage/' . $page->backsound)    : null,
                        'area_interaktif' => $page->areaInteraktif->map(function ($area) {
                            return [
                                'id'          => (string) $area->id_area,
                                'label'       => $area->label       ?? null,
                                'x'           => $area->x,
                                'y'           => $area->y,
                                'x_pct'       => $area->x_pct       ?? null,
                                'y_pct'       => $area->y_pct       ?? null,
                                'w_pct'       => $area->w_pct       ?? null,
                                'h_pct'       => $area->h_pct       ?? null,
                                'lebar'       => $area->lebar_area,
                                'tinggi'      => $area->panjang_area,
                                'audio_indo'  => $area->audio_indo  ? asset('storage/' . $area->audio_indo)  : null,
                                'audio_sunda' => $area->audio_sunda ? asset('storage/' . $area->audio_sunda) : null,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];

            $jsonPath = 'buku/metadata/' . $buku->id_buku . '/metadata.json';
            Storage::disk('public')->put(
                $jsonPath,
                json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );

        } catch (\Exception $e) {
            \Log::error('generateMetadataJson error (book ' . $buku->id_buku . '): ' . $e->getMessage());
        }
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

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

            // Delete metadata.json
            $metaPath = 'buku/metadata/' . $buku->id_buku . '/metadata.json';
            if (Storage::disk('public')->exists($metaPath)) {
                Storage::disk('public')->delete($metaPath);
            }

            $buku->delete();

            return redirect('/buku')->with('success', 'Buku berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus buku: ' . $e->getMessage()]);
        }
    }

    // ── Dashboard ────────────────────────────────────────────────────────────

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

    // ── API Endpoints ─────────────────────────────────────────────────────────

    public function apiBooks()
    {
        $buku = Buku::where('status_publikasi', 'Terbit')->with('halaman')->get();
        return response()->json(['buku' => $buku]);
    }

    public function apiBookDetail($id)
    {
        $buku = Buku::find($id);
        if (!$buku) {
            return response()->json(['error' => 'Buku tidak ditemukan'], 404);
        }
        $buku->load('halaman.areaInteraktif');
        return response()->json(['buku' => $buku]);
    }
}