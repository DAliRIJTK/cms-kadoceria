<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Halaman;
use App\Models\AreaInteraktif;
use App\Models\AudioLatar;
use App\Models\Buku;

class HalamanController extends Controller
{

    public function management(Request $request)
    {
        $query = Halaman::with('buku')->orderBy('id_buku', 'asc')->orderBy('nomor_halaman', 'asc');

        if ($request->filled('search')) {
            $query->whereHas('buku', function ($q) {
                $q->where('judul_idn', 'like', '%' . request('search') . '%')
                  ->orWhere('judul_sn',  'like', '%' . request('search') . '%');
            });
        }

        if ($request->filled('id_buku') && $request->id_buku !== '') {
            $buku = Buku::find($request->id_buku);
            $query->where('id_buku', $request->id_buku);
        }

        if ($buku->is_processing) {
            return redirect()->route('buku.show', $buku)
                ->withErrors(['error' => 'Buku masih dalam proses konversi PDF. Fitur edit belum tersedia.']);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->whereHas('buku', function ($q) {
                $q->where('status_publikasi', request('status'));
            });
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'halaman_asc':  $query->orderBy('nomor_halaman', 'asc');  break;
                case 'halaman_desc': $query->orderBy('nomor_halaman', 'desc'); break;
                case 'date_newest':  $query->orderBy('created_at', 'desc');    break;
                case 'date_oldest':  $query->orderBy('created_at', 'asc');     break;
                case 'buku_asc':     $query->orderBy('id_buku', 'asc');        break;
                default:             $query->orderBy('id_buku', 'asc')->orderBy('nomor_halaman', 'asc');
            }
        }

        $halaman = $query->get();
        $allBuku = Buku::all();

        return view('halaman.management', compact('halaman', 'allBuku'));
    }


    public function edit(Buku $buku, $nomor_halaman)
    {
        if ((int)$nomor_halaman === 1) {
            return redirect()->route('halaman.management', ['id_buku' => $buku->id_buku])
                ->withErrors(['error' => 'Halaman cover tidak dapat disunting.']);
        }

        $halaman = Halaman::where('id_buku', $buku->id_buku)
            ->where('nomor_halaman', $nomor_halaman)
            ->firstOrFail();

        $halaman->load(['areaInteraktif', 'buku', 'audioLatar']);
        $allAudioLatar = AudioLatar::orderBy('nama_audio')->get();

        $prevHalaman = Halaman::where('id_buku', $halaman->id_buku)
            ->where('nomor_halaman', '<', $halaman->nomor_halaman)
            ->orderBy('nomor_halaman', 'desc')
            ->first();

        $nextHalaman = Halaman::where('id_buku', $halaman->id_buku)
            ->where('nomor_halaman', '>', $halaman->nomor_halaman)
            ->orderBy('nomor_halaman', 'asc')
            ->first();

        return view('halaman.edit', compact('halaman', 'allAudioLatar', 'prevHalaman', 'nextHalaman'));
    }

    public function show(Buku $buku, $nomor_halaman)
    {
        $halaman = Halaman::where('id_buku', $buku->id_buku)
            ->where('nomor_halaman', $nomor_halaman)
            ->firstOrFail();

        $halaman->load(['buku', 'areaInteraktif', 'audioLatar']);
        return view('halaman.show', compact('halaman'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_buku'     => 'required|exists:buku,id_buku',
            'path_gambar' => 'required|image',
        ]);

        $buku = Buku::findOrFail($validated['id_buku']);

        $lastPage = Halaman::where('id_buku', $validated['id_buku'])->max('nomor_halaman');
        $newPageNumber = ($lastPage ?? 0) + 1;

        $ext = $request->file('path_gambar')->getClientOriginalExtension();
        if ($newPageNumber === 1) {
            $filename = 'cover.' . $ext;
        } else {
            $filename = 'halaman' . ($newPageNumber - 1) . '.' . $ext;
        }

        $bookDir = $buku->slugify($buku->judul_idn);
        $path = $request->file('path_gambar')->storeAs('buku/' . $bookDir . '/halaman', $filename, 's3');

        Halaman::create([
            'id_buku'       => $validated['id_buku'],
            'nomor_halaman' => $newPageNumber,
            'path_gambar'   => $path,
        ]);

        $buku->syncStorageStructure();

        return back()->with('success', 'Halaman berhasil ditambahkan');
    }

    public function update(Request $request, Halaman $halaman)
    {
        try {
            if ($request->has('nomor_halaman')) {
                $validated     = $request->validate(['nomor_halaman' => 'nullable|integer|min:1']);
                $oldPageNumber = $halaman->nomor_halaman;
                $newPageNumber = $validated['nomor_halaman'];

                if ($oldPageNumber !== $newPageNumber) {
                    $maxPageNumber = $halaman->buku->halaman()->max('nomor_halaman');
                    $newPageNumber = min($newPageNumber, $maxPageNumber);

                    if ($oldPageNumber < $newPageNumber) {
                        $halaman->buku->halaman()
                            ->whereBetween('nomor_halaman', [$oldPageNumber + 1, $newPageNumber])
                            ->decrement('nomor_halaman');
                    } else {
                        $halaman->buku->halaman()
                            ->whereBetween('nomor_halaman', [$newPageNumber, $oldPageNumber - 1])
                            ->increment('nomor_halaman');
                    }

                    $halaman->update(['nomor_halaman' => $newPageNumber]);
                    $halaman->buku->syncStorageStructure();
                }
            }

            return back()->with('success', 'Halaman berhasil diperbarui');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            throw $e;
        }
    }


    public function destroy(Halaman $halaman)
    {
        $buku = $halaman->buku;
        $isCover = $halaman->nomor_halaman === 1;
        $currentPageCount = $buku->halaman()->count();

        if ($currentPageCount - 1 < 10) {
            return back()->withErrors(['delete' => 'Penghapusan halaman tidak diperbolehkan jika sisa halaman kurang dari 10.']);
        }

        $s3PathsToDelete = [];
        foreach ($halaman->areaInteraktif as $area) {
            if ($area->audio_indo) $s3PathsToDelete[] = $area->audio_indo;
            if ($area->audio_sunda) $s3PathsToDelete[] = $area->audio_sunda;
        }
        foreach (['narasi_indo', 'narasi_sunda', 'path_gambar'] as $field) {
            if ($halaman->$field) $s3PathsToDelete[] = $halaman->$field;
        }

        $deletedPageNumber = $halaman->nomor_halaman;

        DB::beginTransaction();
        
        try {
            // Hapus child (Area Interaktif) lalu Hapus Halaman
            $halaman->areaInteraktif()->delete();
            $halaman->delete();

            // Geser nomor halaman yang lebih besar (merapatkan barisan)
            $buku->halaman()
                ->where('nomor_halaman', '>', $deletedPageNumber)
                ->decrement('nomor_halaman');

            // Logika Khusus Cover: Bersihkan audio pada Halaman 2 yang naik jadi Halaman 1
            if ($isCover) {
                $newCover = $buku->halaman()->where('nomor_halaman', 1)->first();
                if ($newCover) {
                    $newCover->load('areaInteraktif');
                    // Masukkan file audio cover baru ke daftar hapus S3
                    foreach ($newCover->areaInteraktif as $area) {
                        if ($area->audio_indo) $s3PathsToDelete[] = $area->audio_indo;
                        if ($area->audio_sunda) $s3PathsToDelete[] = $area->audio_sunda;
                    }
                    foreach (['narasi_indo', 'narasi_sunda'] as $field) {
                        if ($newCover->$field) $s3PathsToDelete[] = $newCover->$field;
                    }

                    $newCover->areaInteraktif()->delete();
                    $newCover->update([
                        'narasi_indo'    => null,
                        'narasi_sunda'   => null,
                        'id_audio_latar' => null,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['delete' => 'Gagal memproses perubahan di database: ' . $e->getMessage()]);
        }

        foreach ($s3PathsToDelete as $path) {
            try {
                if (Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Orphaned S3 File (Hapus Halaman): " . $path);
            }
        }

        try {
            $buku->syncStorageStructure();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal sync storage setelah hapus halaman: " . $e->getMessage());
        }
        return back()->with('success', 'Halaman berhasil dihapus');
    }

    public function bulkDestroy(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'selected_pages' => 'required|array|min:1',
            'selected_pages.*' => 'exists:halaman,id_halaman'
        ]);

        $ids = $request->selected_pages;
        $pagesToDelete = Halaman::with('areaInteraktif', 'buku')->whereIn('id_halaman', $ids)->get();

        if ($pagesToDelete->isEmpty()) {
            return back()->withErrors(['delete' => 'Tidak ada halaman valid yang dipilih.']);
        }

        $buku = $pagesToDelete->first()->buku;

        // Validasi: Cover tidak boleh ikut dihapus
        if ($pagesToDelete->contains('nomor_halaman', 1)) {
            return back()->withErrors(['delete' => 'Halaman cover tidak dapat dihapus melalui fitur ini.']);
        }

        // Validasi: Sisa halaman buku tidak boleh kurang dari 10 (termasuk cover)
        $currentTotal = $buku->halaman()->count();
        if (($currentTotal - $pagesToDelete->count()) - 1 < 10) {
            return back()->withErrors(['delete' => 'Penghapusan dibatalkan. Sisa halaman tidak boleh kurang dari 10.']);
        }

        $s3PathsToDelete = [];
        foreach ($pagesToDelete as $halaman) {
            foreach ($halaman->areaInteraktif as $area) {
                if ($area->audio_indo) $s3PathsToDelete[] = $area->audio_indo;
                if ($area->audio_sunda) $s3PathsToDelete[] = $area->audio_sunda;
            }
            foreach (['narasi_indo', 'narasi_sunda', 'path_gambar'] as $field) {
                if ($halaman->$field) $s3PathsToDelete[] = $halaman->$field;
            }
        }

        // 2. ATOMISITAS DATABASE
        DB::beginTransaction();
        try {
            // Eloquent bulk delete (whereIn->delete) tidak mentrigger cascade secara otomatis
            // Jadi kita hapus manual record anaknya (Area Interaktif) terlebih dahulu
            \App\Models\AreaInteraktif::whereIn('id_halaman', $ids)->delete();

            // Hapus record Database sekaligus
            \App\Models\Halaman::whereIn('id_halaman', $ids)->delete();

            // Re-order nomor halaman yang tersisa
            $remainingPages = $buku->halaman()->where('nomor_halaman', '>', 1)->orderBy('nomor_halaman', 'asc')->get();
            $newNomor = 2; // Mulai dari 2 karena cover = 1
            foreach ($remainingPages as $page) {
                if ($page->nomor_halaman !== $newNomor) {
                    // Jangan gunakan update(), bisa lambat untuk bulk. Gunakan update pada query.
                    // Tapi karena logic berurutan per halaman, ini bisa diterima.
                    $page->update(['nomor_halaman' => $newNomor]);
                }
                $newNomor++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['delete' => 'Gagal menghapus halaman secara massal: ' . $e->getMessage()]);
        }

        // 3. EKSEKUSI HAPUS S3 (Toleransi Error)
        foreach ($s3PathsToDelete as $path) {
            try {
                if (Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Orphaned S3 File (Hapus Massal): " . $path);
            }
        }

        // 4. Update folder (sync S3)
        try {
            $buku->syncStorageStructure();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal sync storage setelah hapus massal: " . $e->getMessage());
        }

        return back()->with('success', 'Halaman yang dipilih berhasil dihapus');
    }

    public function flipbook(Buku $buku)
    {
        try {
            $buku->load(['halaman' => function ($q) {
                $q->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman');
            }]);

            // Validate that physical assets exist for each page
            foreach ($buku->halaman as $page) {
                if (empty($page->path_gambar) || !Storage::disk('s3')->exists($page->path_gambar)) {
                    throw new \Exception("Aset multimedia tidak dapat dimuat, periksa kelengkapan file.");
                }

                // If narration audio is set in DB but missing in storage
                if (!empty($page->narasi_indo) && !Storage::disk('s3')->exists($page->narasi_indo)) {
                    throw new \Exception("Aset multimedia tidak dapat dimuat, periksa kelengkapan file.");
                }
                if (!empty($page->narasi_sunda) && !Storage::disk('s3')->exists($page->narasi_sunda)) {
                    throw new \Exception("Aset multimedia tidak dapat dimuat, periksa kelengkapan file.");
                }

                // If background audio is set in DB but missing in storage
                if ($page->audioLatar && !Storage::disk('s3')->exists($page->audioLatar->path_file)) {
                    throw new \Exception("Aset multimedia tidak dapat dimuat, periksa kelengkapan file.");
                }

                // If area interactive audios are set in DB but missing in storage
                foreach ($page->areaInteraktif as $area) {
                    if (!empty($area->audio_indo) && !Storage::disk('s3')->exists($area->audio_indo)) {
                        throw new \Exception("Aset multimedia tidak dapat dimuat, periksa kelengkapan file.");
                    }
                    if (!empty($area->audio_sunda) && !Storage::disk('s3')->exists($area->audio_sunda)) {
                        throw new \Exception("Aset multimedia tidak dapat dimuat, periksa kelengkapan file.");
                    }
                }
            }

            return view('halaman.flipbook', compact('buku'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->withErrors(['error' => $e->getMessage()]);
        }
    }
}