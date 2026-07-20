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

        if ($buku->halaman()->count() === 0) {
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
            $filename = 'halaman ' . ($newPageNumber - 1) . '.' . $ext;
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

        try {
            $deletedPageNumber = $halaman->nomor_halaman;

            // Hapus file audio dari area interaktif halaman ini
            foreach ($halaman->areaInteraktif as $area) {
                foreach (['audio_indo', 'audio_sunda'] as $field) {
                    if ($area->$field && Storage::disk('s3')->exists($area->$field)) {
                        Storage::disk('s3')->delete($area->$field);
                    }
                }
            }
            $halaman->areaInteraktif()->delete();

            // Hapus file narasi halaman ini
            foreach (['narasi_indo', 'narasi_sunda'] as $field) {
                if ($halaman->$field && Storage::disk('s3')->exists($halaman->$field)) {
                    Storage::disk('s3')->delete($halaman->$field);
                }
            }

            // Hapus file gambar halaman ini
            if ($halaman->path_gambar && Storage::disk('s3')->exists($halaman->path_gambar)) {
                Storage::disk('s3')->delete($halaman->path_gambar);
            }

            $halaman->delete();

            // Geser nomor halaman yang lebih besar
            $buku->halaman()
                ->where('nomor_halaman', '>', $deletedPageNumber)
                ->decrement('nomor_halaman');

            // Jika yang dihapus adalah cover (halaman 1), halaman 2 sekarang menjadi
            // halaman 1 (cover baru). Cover tidak boleh memiliki audio dan anotasi,
            // maka bersihkan semua data tersebut dari cover baru.
            if ($isCover) {
                $newCover = $buku->halaman()->where('nomor_halaman', 1)->first();
                if ($newCover) {
                    // Hapus file audio area interaktif cover baru
                    $newCover->load('areaInteraktif');
                    foreach ($newCover->areaInteraktif as $area) {
                        foreach (['audio_indo', 'audio_sunda'] as $field) {
                            if ($area->$field && Storage::disk('s3')->exists($area->$field)) {
                                Storage::disk('s3')->delete($area->$field);
                            }
                        }
                    }
                    $newCover->areaInteraktif()->delete();

                    // Hapus file narasi cover baru
                    foreach (['narasi_indo', 'narasi_sunda'] as $field) {
                        if ($newCover->$field && Storage::disk('s3')->exists($newCover->$field)) {
                            Storage::disk('s3')->delete($newCover->$field);
                        }
                    }

                    // Reset kolom audio & relasi audio latar pada cover baru
                    $newCover->update([
                        'narasi_indo'    => null,
                        'narasi_sunda'   => null,
                        'id_audio_latar' => null,
                    ]);
                }
            }

            $buku->syncStorageStructure();

            return back()->with('success', 'Halaman berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus halaman: ' . $e->getMessage()]);
        }
    }


    public function setBacksound(Request $request, Halaman $halaman)
    {
        if ($halaman->nomor_halaman === 1) {
            return back()->withErrors(['error' => 'Halaman cover tidak boleh memiliki audio latar.']);
        }

        $validated = $request->validate([
            'id_audio_latar' => 'required|exists:audio_latar,id_audio_latar',
        ]);

        $halaman->update(['id_audio_latar' => $validated['id_audio_latar']]);
        $halaman->buku->syncStorageStructure();

        return back()->with('success', 'Backsound halaman berhasil diatur');
    }

    public function removeBacksound(Halaman $halaman)
    {

        $halaman->update(['id_audio_latar' => null]);
        $halaman->buku->syncStorageStructure();

        return back()->with('success', 'Backsound halaman berhasil dihapus');
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