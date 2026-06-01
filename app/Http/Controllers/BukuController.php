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
    public function index(Request $request)
    {
        $query = Buku::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul_idn', 'like', "%{$search}%")
                  ->orWhere('judul_sn', 'like', "%{$search}%")
                  ->orWhere('penulis', 'like', "%{$search}%")
                  ->orWhere('ilustrator', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status_publikasi', $request->status);
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'title_asc':
                    $query->orderBy('judul_idn', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('judul_idn', 'desc');
                    break;
                case 'date_newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'date_oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
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
        $validated = $request->validate([
            'judul_idn' => 'required|string|max:255',
            'judul_sn' => 'nullable|string|max:255',
            'penulis' => 'nullable|string|max:100',
            'ilustrator' => 'nullable|string|max:100',
            'deskripsi_idn' => 'nullable|string',
            'deskripsi_sn' => 'nullable|string',
            'pdf_file' => 'required|file|mimes:pdf|max:204800',
        ]);

        DB::beginTransaction();

        try {
            // 1. SIMPAN PDF
            $pdfPath = $request->file('pdf_file')->store('buku/pdf', 'public');

            // 2. CREATE BUKU
            $buku = Buku::create([
                'id_pengelola' => Auth::id(),
                'judul_idn' => $validated['judul_idn'],
                'judul_sn' => $validated['judul_sn'] ?? null,
                'penulis' => $validated['penulis'] ?? null,
                'ilustrator' => $validated['ilustrator'] ?? null,
                'deskripsi_idn' => $validated['deskripsi_idn'] ?? null,
                'deskripsi_sn' => $validated['deskripsi_sn'] ?? null,
                'path_cover' => null,
                'status_publikasi' => 'Draft',
            ]);

            $fullPdfPath = storage_path('app/public/' . $pdfPath);

            // 3. CONVERT PDF TO IMAGES
            $imagick = new \Imagick();
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256);
            $imagick->setResolution(120, 120);
            $imagick->readImage($fullPdfPath);

            foreach ($imagick as $index => $page) {
                $page->setImageFormat('webp');
                $page->setImageCompressionQuality(80);

                $fileName = 'buku/halaman/' . uniqid() . '_halaman_' . ($index + 1) . '.webp';
                $fullPath = storage_path('app/public/' . $fileName);

                $directory = dirname($fullPath);
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }

                $result = $page->writeImage($fullPath);

                if (!$result || !file_exists($fullPath)) {
                    throw new \Exception('Gagal menyimpan gambar halaman ke-' . ($index + 1));
                }

                Halaman::create([
                    'id_buku' => $buku->id_buku,
                    'nomor_halaman' => $index + 1,
                    'path_gambar' => $fileName,
                ]);

                if ($index === 0) {
                    $buku->path_cover = $fileName;
                    $buku->save();
                }
            }

            $imagick->clear();
            $imagick->destroy();

            // Delete temporary PDF
            Storage::disk('public')->delete($pdfPath);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }

            return back()->withErrors([
                'error' => 'Gagal memproses PDF: ' . $e->getMessage()
            ]);
        }

        return redirect('/buku')->with('success', 'Buku berhasil ditambahkan & diproses!');
    }

    public function show(Buku $buku)
    {
        $buku->load('halaman');
        return view('buku.show', compact('buku'));
    }

    public function edit(Buku $buku)
    {
        return view('buku.edit', compact('buku'));
    }

    public function update(Request $request, Buku $buku)
    {
        $validated = $request->validate([
            'judul_idn' => 'required|string|max:255',
            'judul_sn' => 'nullable|string|max:255',
            'penulis' => 'nullable|string|max:100',
            'ilustrator' => 'nullable|string|max:100',
            'deskripsi_idn' => 'nullable|string',
            'deskripsi_sn' => 'nullable|string',
        ]);

        $buku->update($validated);

        return back()->with('success', 'Informasi buku berhasil diperbarui');
    }

    public function updateStatus(Buku $buku, Request $request)
    {
        $request->validate([
            'status_publikasi' => 'required|in:Draft,Terbit'
        ]);

        $newStatus = $request->status_publikasi;

        if ($newStatus === 'Terbit') {
            $validationErrors = [];

            if (!$buku->judul_idn || empty($buku->judul_idn)) {
                $validationErrors[] = 'Judul buku harus diisi';
            }

            if ($buku->halaman()->count() === 0) {
                $validationErrors[] = 'Buku harus memiliki minimal 1 halaman';
            }

            if (!empty($validationErrors)) {
                return back()->withErrors([
                    'publication' => 'Buku belum dapat dipublikasikan. ' . implode(' | ', $validationErrors)
                ]);
            }
        }

        if ($buku->status_publikasi === 'Terbit' && $newStatus === 'Draft') {
            $confirmed = $request->has('confirm_unpublish') && $request->confirm_unpublish === 'yes';
            if (!$confirmed) {
                return back()->withErrors([
                    'publication' => 'Konfirmasi pembatalan publikasi: Apakah Anda yakin ingin menarik buku ini dari peredaran?'
                ]);
            }
        }

        $buku->update([
            'status_publikasi' => $newStatus
        ]);

        // Generate metadata.json untuk Android API saat dipublikasikan
        if ($newStatus === 'Terbit') {
            $this->generateMetadataJson($buku);
        }

        $statusLabel = $newStatus === 'Terbit' ? 'dipublikasikan' : 'disimpan sebagai draft';
        return back()->with('success', "Buku berhasil {$statusLabel}");
    }

    private function generateMetadataJson(Buku $buku)
    {
        try {
            $halaman = $buku->halaman()->orderBy('nomor_halaman')->get();
            
            $metadataArray = [
                'id' => (string) $buku->id_buku,
                'judul_idn' => $buku->judul_idn,
                'judul_sn' => $buku->judul_sn,
                'penulis' => $buku->penulis,
                'ilustrator' => $buku->ilustrator,
                'deskripsi_idn' => $buku->deskripsi_idn,
                'deskripsi_sn' => $buku->deskripsi_sn,
                'cover' => $buku->path_cover ? asset('storage/' . $buku->path_cover) : null,
                'status_publikasi' => $buku->status_publikasi,
                'tanggal_publikasi' => $buku->updated_at->toIso8601String(),
                'total_halaman' => $halaman->count(),
                'halaman' => $halaman->map(function ($page) {
                    return [
                        'id' => (string) $page->id_halaman,
                        'nomor' => $page->nomor_halaman,
                        'gambar' => asset('storage/' . $page->path_gambar),
                        'narasi_indo' => $page->narasi_indo ? asset('storage/' . $page->narasi_indo) : null,
                        'narasi_sunda' => $page->narasi_sunda ? asset('storage/' . $page->narasi_sunda) : null,
                        'area_interaktif' => $page->areaInteraktif->map(function ($area) {
                            return [
                                'id' => (string) $area->id_area,
                                'x' => $area->x,
                                'y' => $area->y,
                                'lebar' => $area->lebar_area,
                                'tinggi' => $area->panjang_area,
                                'audio_indo' => $area->audio_indo ? asset('storage/' . $area->audio_indo) : null,
                                'audio_sunda' => $area->audio_sunda ? asset('storage/' . $area->audio_sunda) : null,
                            ];
                        })->toArray(),
                    ];
                })->toArray(),
            ];

            $jsonPath = 'buku/metadata/' . $buku->id_buku . '/metadata.json';
            Storage::disk('public')->put($jsonPath, json_encode($metadataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        } catch (\Exception $e) {
            \Log::error('Error generating metadata.json for book ' . $buku->id_buku . ': ' . $e->getMessage());
        }
    }

    public function destroy(Buku $buku)
    {
        try {
            foreach ($buku->halaman as $halaman) {
                foreach ($halaman->areaInteraktif as $area) {
                    if ($area->audio_indo && Storage::disk('public')->exists($area->audio_indo)) {
                        Storage::disk('public')->delete($area->audio_indo);
                    }
                    if ($area->audio_sunda && Storage::disk('public')->exists($area->audio_sunda)) {
                        Storage::disk('public')->delete($area->audio_sunda);
                    }
                }

                if ($halaman->path_gambar && Storage::disk('public')->exists($halaman->path_gambar)) {
                    Storage::disk('public')->delete($halaman->path_gambar);
                }
            }

            if ($buku->path_cover && Storage::disk('public')->exists($buku->path_cover)) {
                Storage::disk('public')->delete($buku->path_cover);
            }

            $buku->delete();

            return redirect('/buku')->with('success', 'Buku berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors([
                'delete' => 'Gagal menghapus buku: ' . $e->getMessage()
            ]);
        }
    }

    public function dashboard(Request $request)
    {
        $query = Buku::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul_idn', 'like', "%{$search}%")
                  ->orWhere('judul_sn', 'like', "%{$search}%")
                  ->orWhere('penulis', 'like', "%{$search}%")
                  ->orWhere('ilustrator', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('status_publikasi', $request->status);
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'title_asc':
                    $query->orderBy('judul_idn', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('judul_idn', 'desc');
                    break;
                case 'date_newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'date_oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $buku = $query->paginate(8);

        // Statistics
        $totalBooks = Buku::count();
        $publishedBooks = Buku::where('status_publikasi', 'Terbit')->count();
        $draftBooks = Buku::where('status_publikasi', 'Draft')->count();
        $totalPages = Halaman::count();

        return view('dashboard', compact('buku', 'totalBooks', 'publishedBooks', 'draftBooks', 'totalPages'));
    }

    public function apiBooks()
    {
        $buku = Buku::where('status_publikasi', 'Terbit')
            ->with('halaman')
            ->get();

        return response()->json([
            'buku' => $buku
        ]);
    }

    public function apiBookDetail($id)
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json(['error' => 'Buku tidak ditemukan'], 404);
        }

        $buku->load('halaman.areaInteraktif');

        return response()->json([
            'buku' => $buku
        ]);
    }
}
