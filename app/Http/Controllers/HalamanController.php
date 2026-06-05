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
    // ── Halaman Management (list) ────────────────────────────────────────────

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

    // ── Edit / Show ──────────────────────────────────────────────────────────

    public function edit(Halaman $halaman)
    {
        $halaman->load(['areaInteraktif', 'buku', 'audioLatar']);
        $allAudioLatar = AudioLatar::orderBy('nama_audio')->get();
        return view('halaman.edit', compact('halaman', 'allAudioLatar'));
    }

    public function show(Halaman $halaman)
    {
        $halaman->load(['buku', 'areaInteraktif', 'audioLatar']);
        return view('halaman.show', compact('halaman'));
    }

    // ── Store (create new halaman) ───────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_buku'     => 'required|exists:buku,id_buku',
            'path_gambar' => 'required|image',
        ]);

        $path     = $request->file('path_gambar')->store('buku/halaman', 'public');
        $lastPage = Halaman::where('id_buku', $validated['id_buku'])->max('nomor_halaman');

        Halaman::create([
            'id_buku'       => $validated['id_buku'],
            'nomor_halaman' => ($lastPage ?? 0) + 1,
            'path_gambar'   => $path,
        ]);

        return back()->with('success', 'Halaman berhasil ditambahkan');
    }

    // ── Update ───────────────────────────────────────────────────────────────

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

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(Halaman $halaman)
    {
        try {
            $buku              = $halaman->buku;
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

            return back()->with('success', 'Halaman berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus halaman: ' . $e->getMessage()]);
        }
    }

    // ── Reorder ──────────────────────────────────────────────────────────────

    public function reorder(Request $request)
    {
        foreach ($request->halaman as $index => $id) {
            Halaman::where('id_halaman', $id)->update(['nomor_halaman' => $index + 1]);
        }
        return response()->json(['success' => true]);
    }

    // ── Backsound: set (atur AudioLatar) ─────────────────────────────────────

    public function setBacksound(Request $request, Halaman $halaman)
    {
        $validated = $request->validate([
            'id_audio_latar' => 'required|exists:audio_latar,id_audio_latar',
        ]);

        $halaman->update(['id_audio_latar' => $validated['id_audio_latar']]);

        return back()->with('success', 'Backsound halaman berhasil diatur');
    }

    // ── Backsound: remove (lepas relasi, set null) ────────────────────────────

    public function removeBacksound(Halaman $halaman)
    {
        $halaman->update(['id_audio_latar' => null]);

        return back()->with('success', 'Backsound halaman berhasil dihapus');
    }

    // ── Area Interaktif: store ───────────────────────────────────────────────

    public function storeAreaInteraktif(Request $request)
    {
        $validated = $request->validate([
            'id_halaman'   => 'required|exists:halaman,id_halaman',
            'label'        => 'nullable|string|max:255',
            'x_pct'        => 'required|numeric|min:0|max:100',
            'y_pct'        => 'required|numeric|min:0|max:100',
            'w_pct'        => 'required|numeric|min:0|max:100',
            'h_pct'        => 'required|numeric|min:0|max:100',
            'x'            => 'nullable|integer',
            'y'            => 'nullable|integer',
            'lebar_area'   => 'nullable|integer|min:1',
            'panjang_area' => 'nullable|integer|min:1',
        ]);

        try {
            $area = AreaInteraktif::create([
                'id_halaman'   => $validated['id_halaman'],
                'label'        => $validated['label'] ?? null,
                'x_pct'        => $validated['x_pct'],
                'y_pct'        => $validated['y_pct'],
                'w_pct'        => $validated['w_pct'],
                'h_pct'        => $validated['h_pct'],
                'x'            => $validated['x'] ?? 0,
                'y'            => $validated['y'] ?? 0,
                'lebar_area'   => $validated['lebar_area'] ?? 0,
                'panjang_area' => $validated['panjang_area'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'area'    => $area,
                'message' => 'Area interaktif berhasil disimpan',
            ]);
        } catch (\Throwable $e) {
            \Log::error('storeAreaInteraktif error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Area Interaktif: update coords ──────────────────────────────────────

    public function updateAreaInteraktif(Request $request, AreaInteraktif $area)
    {
        $validated = $request->validate([
            'x'            => 'required|integer',
            'y'            => 'required|integer',
            'lebar_area'   => 'required|integer|min:1',
            'panjang_area' => 'required|integer|min:1',
        ]);

        $area->update($validated);
        return response()->json(['success' => true]);
    }

    // ── Area Interaktif: delete ──────────────────────────────────────────────

    public function deleteAreaInteraktif(AreaInteraktif $area)
    {
        try {
            foreach (['audio_indo', 'audio_sunda'] as $field) {
                if ($area->$field && Storage::disk('public')->exists($area->$field)) {
                    Storage::disk('public')->delete($area->$field);
                }
            }
            $area->delete();

            return response()->json([
                'success' => true,
                'message' => 'Area interaktif berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Area Interaktif: upload audio ────────────────────────────────────────

    public function storeAreaAudio(Request $request, AreaInteraktif $area)
    {
        $validated = $request->validate([
            'audio_type' => 'required|in:indo,sunda',
            'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a|max:10240',
        ]);

        try {
            $field = 'audio_' . $validated['audio_type'];

            if ($area->$field && Storage::disk('public')->exists($area->$field)) {
                Storage::disk('public')->delete($area->$field);
            }

            $path       = $request->file('audio_file')->store('buku/audio', 'public');
            $area->$field = $path;
            $area->save();

            $lang = $validated['audio_type'] === 'indo' ? 'Indonesia' : 'Sunda';
            return back()->with('success', "Audio {$lang} area berhasil diunggah");
        } catch (\Exception $e) {
            return back()->withErrors(['audio' => 'Gagal menyimpan audio: ' . $e->getMessage()]);
        }
    }

    // ── Narasi Halaman: store ────────────────────────────────────────────────

    public function storeNarasi(Request $request, Halaman $halaman)
    {
        $validated = $request->validate([
            'narasi_type' => 'required|in:indo,sunda',
            'audio_file'  => 'required|file|mimes:mp3,wav,ogg,m4a|max:10240',
        ]);

        try {
            $path = $request->file('audio_file')->store('buku/narasi', 'public');

            switch ($validated['narasi_type']) {
                case 'indo':
                    if ($halaman->narasi_indo && Storage::disk('public')->exists($halaman->narasi_indo)) {
                        Storage::disk('public')->delete($halaman->narasi_indo);
                    }
                    $halaman->narasi_indo = $path;
                    $message = 'Narasi Indonesia berhasil diunggah';
                    break;

                case 'sunda':
                    if ($halaman->narasi_sunda && Storage::disk('public')->exists($halaman->narasi_sunda)) {
                        Storage::disk('public')->delete($halaman->narasi_sunda);
                    }
                    $halaman->narasi_sunda = $path;
                    $message = 'Narasi Sunda berhasil diunggah';
                    break;
            }

            $halaman->save();
            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['audio' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    // ── Narasi Halaman: delete ───────────────────────────────────────────────

    public function deleteNarasi(Halaman $halaman, Request $request)
    {
        try {
            $type = $request->input('narasi_type');

            $fieldMap = [
                'indo'  => ['field' => 'narasi_indo',  'label' => 'Narasi Indonesia'],
                'sunda' => ['field' => 'narasi_sunda', 'label' => 'Narasi Sunda'],
            ];

            if (!isset($fieldMap[$type])) {
                return back()->withErrors(['audio' => 'Tipe audio tidak valid']);
            }

            $field = $fieldMap[$type]['field'];
            $label = $fieldMap[$type]['label'];

            if ($halaman->$field && Storage::disk('public')->exists($halaman->$field)) {
                Storage::disk('public')->delete($halaman->$field);
            }
            $halaman->$field = null;
            $halaman->save();

            return back()->with('success', "{$label} berhasil dihapus");
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }

    public function flipbook(Buku $buku)
    {
        $buku->load(['halaman' => function ($q) {
            $q->with(['areaInteraktif', 'audioLatar'])->orderBy('nomor_halaman');
        }]);

        return view('halaman.flipbook', compact('buku'));
    }
}