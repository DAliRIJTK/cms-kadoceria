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
            $query->where('id_buku', $request->id_buku);
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

        $halaman = $query->paginate(15);
        $allBuku = Buku::all();

        return view('halaman.management', compact('halaman', 'allBuku'));
    }


    public function edit(Halaman $halaman)
    {
        if ($halaman->buku->status_publikasi === 'Terbit') {
            return redirect()->route('halaman.show', $halaman->id_halaman)
                ->withErrors(['publication' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
        }

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

    public function show(Halaman $halaman)
    {
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

        if ($buku->status_publikasi === 'Terbit') {
            return back()->withErrors(['error' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
        }

        $lastPage = Halaman::where('id_buku', $validated['id_buku'])->max('nomor_halaman');
        $newPageNumber = ($lastPage ?? 0) + 1;

        $ext = $request->file('path_gambar')->getClientOriginalExtension();
        if ($newPageNumber === 1) {
            $filename = 'cover.' . $ext;
        } else {
            $filename = 'halaman ' . ($newPageNumber - 1) . '.' . $ext;
        }

        $bookDir = $buku->slugify($buku->judul_idn);
        $path = $request->file('path_gambar')->storeAs('buku/' . $bookDir . '/halaman', $filename, 'public');

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
        if ($halaman->buku->status_publikasi === 'Terbit') {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'], 422);
            }
            return back()->withErrors(['error' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
        }

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
            \Log::error('HalamanController update error: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            throw $e;
        }
    }


    public function destroy(Halaman $halaman)
    {
        $buku = $halaman->buku;

        if ($buku->status_publikasi === 'Terbit') {
            return back()->withErrors(['delete' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
        }
        $currentPageCount = $buku->halaman()->count();
        if ($currentPageCount - 1 <= 10) {
            return back()->withErrors(['delete' => 'Penghapusan halaman tidak diperbolehkan jika sisa halaman kurang dari atau sama dengan 10.']);
        }

        try {
            $deletedPageNumber = $halaman->nomor_halaman;

            foreach ($halaman->areaInteraktif as $area) {
                foreach (['audio_indo', 'audio_sunda'] as $field) {
                    if ($area->$field && Storage::disk('public')->exists($area->$field)) {
                        Storage::disk('public')->delete($area->$field);
                    }
                }
            }
            $halaman->areaInteraktif()->delete();

            if ($halaman->path_gambar && Storage::disk('public')->exists($halaman->path_gambar)) {
                Storage::disk('public')->delete($halaman->path_gambar);
            }

            $halaman->delete();

            $buku->halaman()
                ->where('nomor_halaman', '>', $deletedPageNumber)
                ->decrement('nomor_halaman');

            $buku->syncStorageStructure();

            return back()->with('success', 'Halaman berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus halaman: ' . $e->getMessage()]);
        }
    }

    public function reorder(Request $request)
    {
        if ($request->filled('halaman') && count($request->halaman) > 0) {
            $firstHalaman = Halaman::find($request->halaman[0]);
            if ($firstHalaman && $firstHalaman->buku->status_publikasi === 'Terbit') {
                return response()->json(['success' => false, 'message' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'], 422);
            }
        }

        foreach ($request->halaman as $index => $id) {
            Halaman::where('id_halaman', $id)->update(['nomor_halaman' => $index + 1]);
        }
        if (isset($firstHalaman)) {
            $firstHalaman->buku->syncStorageStructure();
        }
        return response()->json(['success' => true]);
    }

    public function setBacksound(Request $request, Halaman $halaman)
    {
        if ($halaman->buku->status_publikasi === 'Terbit') {
            return back()->withErrors(['error' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
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
        if ($halaman->buku->status_publikasi === 'Terbit') {
            return back()->withErrors(['error' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.']);
        }

        $halaman->update(['id_audio_latar' => null]);
        $halaman->buku->syncStorageStructure();

        return back()->with('success', 'Backsound halaman berhasil dihapus');
    }

    public function flipbook(Buku $buku)
    {
        $buku->load(['halaman' => function ($q) {
            $q->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman');
        }]);

        return view('halaman.flipbook', compact('buku'));
    }
}