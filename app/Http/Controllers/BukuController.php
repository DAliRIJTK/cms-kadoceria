<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use ZipArchive;
use App\Services\BukuBundleService;
use App\Services\ProcessPdfService;

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

    public function store(Request $request, ProcessPdfService $pdfService)
    {
        $request->validate([
            'judul_idn'      => 'required|string|max:255',
            'judul_sn'       => 'required|string|max:255',
            'penulis'        => 'required|string|max:100',
            'ilustrator'     => 'required|string|max:100',
            'deskripsi_idn'  => 'required|string',
            'deskripsi_sn'   => 'required|string',
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
                ->withErrors(['duplicate_pdf' => "File PDF \"{$uploadedFileName}\" sudah pernah diunggah. Gunakan nama file yang berbeda."]);
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

            $pdfService->process($buku, $pdfPath);

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
        return view('buku.edit', compact('buku'));
    }

    public function update(Request $request, Buku $buku)
    {

        $validated = $request->validate([
            'judul_idn'      => 'required|string|max:255',
            'judul_sn'       => 'required|string|max:255',
            'penulis'        => 'required|string|max:100',
            'ilustrator'     => 'required|string|max:100',
            'deskripsi_idn'  => 'required|string',
            'deskripsi_sn'   => 'required|string',
            'warna_primer'   => 'nullable|string|max:20',
            'warna_sekunder' => 'nullable|string|max:20',
        ]);

        $judulIdn = $request->judul_idn;
        $judulSn  = $request->judul_sn;

        $duplicateTitle = Buku::where('id_buku', '!=', $buku->id_buku)
            ->where(function ($query) use ($judulIdn, $judulSn) {
                $query->where(function ($q) use ($judulIdn) {
                    $q->whereRaw('LOWER(judul_idn) = ?', [strtolower($judulIdn)])
                      ->orWhereRaw('LOWER(judul_sn) = ?', [strtolower($judulIdn)]);
                })
                ->when($judulSn, function ($qOuter) use ($judulSn) {
                    $qOuter->orWhere(function ($q) use ($judulSn) {
                        $q->whereRaw('LOWER(judul_idn) = ?', [strtolower($judulSn)])
                          ->orWhereRaw('LOWER(judul_sn) = ?', [strtolower($judulSn)]);
                    });
                });
            })
            ->exists();

        if ($duplicateTitle) {
            return back()->withInput()
                ->withErrors(['duplicate_title' => 'Judul buku (Bahasa Indonesia/Sunda) sudah digunakan pada buku lain. Silakan gunakan judul yang berbeda.']);
        }

        $validated['warna_primer']   = $this->sanitizeRgb($validated['warna_primer']   ?? null, '99,102,241');
        $validated['warna_sekunder'] = $this->sanitizeRgb($validated['warna_sekunder'] ?? null, '168,85,247');

        $oldTitle = $buku->judul_idn;
        $buku->update($validated);

        if ($oldTitle !== $buku->judul_idn) {
            $oldBookDir = $this->slugify($oldTitle);
            $newBookDir = $this->slugify($buku->judul_idn);

            if ($oldBookDir !== $newBookDir) {
                $oldPath = 'buku/' . $oldBookDir;
                $newPath = 'buku/' . $newBookDir;
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->move($oldPath, $newPath);

                    // Update path_cover in database
                    if ($buku->path_cover) {
                        $buku->path_cover = str_replace($oldPath . '/', $newPath . '/', $buku->path_cover);
                    }
                    if ($buku->zip_bundle_path) {
                        $buku->zip_bundle_path = str_replace($oldPath . '/', $newPath . '/', $buku->zip_bundle_path);
                    }
                    $buku->save();

                    // Update path_gambar, narasi_indo, narasi_sunda for all pages
                    foreach ($buku->halaman as $halaman) {
                        if ($halaman->path_gambar) {
                            $halaman->path_gambar = str_replace($oldPath . '/', $newPath . '/', $halaman->path_gambar);
                        }
                        if ($halaman->narasi_indo) {
                            $halaman->narasi_indo = str_replace($oldPath . '/', $newPath . '/', $halaman->narasi_indo);
                        }
                        if ($halaman->narasi_sunda) {
                            $halaman->narasi_sunda = str_replace($oldPath . '/', $newPath . '/', $halaman->narasi_sunda);
                        }
                        $halaman->save();

                        // Update audio_indo and audio_sunda for all interactive areas
                        foreach ($halaman->areaInteraktif as $area) {
                            if ($area->audio_indo) {
                                $area->audio_indo = str_replace($oldPath . '/', $newPath . '/', $area->audio_indo);
                            }
                            if ($area->audio_sunda) {
                                $area->audio_sunda = str_replace($oldPath . '/', $newPath . '/', $area->audio_sunda);
                            }
                            $area->save();
                        }
                    }
                }
            }
        }

        $buku->syncStorageStructure();

        return back()->with('success', 'Informasi buku berhasil diperbarui');
    }

    public function updateStatus(Buku $buku, Request $request, BukuBundleService $bundleService)
    {
        $request->validate(['status_publikasi' => 'required|in:Draft,Terbit']);
        $newStatus = $request->status_publikasi;

        if ($newStatus === 'Terbit') {
            $errors = [];
            if (empty($buku->judul_idn)) {
                $errors[] = 'Judul buku harus diisi';
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
            $bundleService->generateAndPackageBundle($buku);
        }

        $statusLabel = $newStatus === 'Terbit' ? 'dipublikasikan' : 'disimpan sebagai draft';
        return back()->with('success', "Buku berhasil {$statusLabel}");
    }

    public function destroy(Buku $buku)
    {
        if ($buku->status_publikasi === 'Terbit') {
            return back()->withErrors(['error' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk menghapus buku.']);
        }

        try {
            $bookDir = $this->slugify($buku->judul_idn);
            $bookFolder = 'buku/' . $bookDir;

            foreach ($buku->halaman as $halaman) {
                foreach ($halaman->areaInteraktif as $area) {
                    foreach (['audio_indo', 'audio_sunda'] as $field) {
                        if ($area->$field && Storage::disk('public')->exists($area->$field)) {
                            Storage::disk('public')->delete($area->$field);
                        }
                    }
                }
                // Delete narration audio files if they exist
                foreach (['narasi_indo', 'narasi_sunda'] as $field) {
                    if ($halaman->$field && Storage::disk('public')->exists($halaman->$field)) {
                        Storage::disk('public')->delete($halaman->$field);
                    }
                }
                if ($halaman->path_gambar && Storage::disk('public')->exists($halaman->path_gambar)) {
                    Storage::disk('public')->delete($halaman->path_gambar);
                }
            }

            if ($buku->path_cover && Storage::disk('public')->exists($buku->path_cover)) {
                Storage::disk('public')->delete($buku->path_cover);
            }

            if (Storage::disk('public')->exists($bookFolder)) {
                Storage::disk('public')->deleteDirectory($bookFolder);
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

}