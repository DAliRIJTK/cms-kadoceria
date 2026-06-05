<?php

namespace App\Http\Controllers; 

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Halaman;
use App\Models\AudioLatar;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessPdfJob;  

class HalamanController extends Controller
{
    public function index($id_buku)
    {
        $buku = Buku::withCount('halaman')->findOrFail($id_buku);
        
        $halaman = Halaman::with(['audioLatar', 'areaInteraktif'])
                          ->where('id_buku', $id_buku)
                          ->orderBy('nomor_halaman', 'asc')
                          ->get();

        $audioLatar = AudioLatar::orderBy('nama_audio')->get();

        return view('halaman.index', compact('buku', 'halaman', 'audioLatar'));
    }

    public function edit($id_halaman)
    {
        $halaman = Halaman::with(['buku', 'audioLatar', 'areaInteraktif'])
                          ->findOrFail($id_halaman);

        if ($halaman->buku->status_publikasi === 'Published') {
            return redirect()->route('buku.halaman.index', $halaman->id_buku)
                             ->with('error', 'Tidak dapat mengedit buku yang sudah dipublikasikan.');
        }

        $audioLatarList = AudioLatar::orderBy('nama_audio')->get();

        return view('halaman.edit', compact('halaman', 'audioLatarList'));
    }

    public function update(Request $request, $id_halaman)
    {
        $halaman = Halaman::with('buku')->findOrFail($id_halaman);

        if ($halaman->buku->status_publikasi === 'Published') {
            return back()->with('error', 'Buku sudah dipublikasikan.');
        }

        $request->validate([
            'narasi_indo'    => 'nullable|file|mimes:m4a,mp3,wav,mpga|max:2048',
            'narasi_sunda'   => 'nullable|file|mimes:m4a,mp3,wav,mpga|max:2048',
            'id_audio_latar' => 'nullable|exists:audio_latar,id_audio_latar',
        ]);

        try {
            $data = [];

            if ($request->hasFile('narasi_indo')) {
                if ($halaman->narasi_indo) Storage::disk('public')->delete($halaman->narasi_indo);
                $data['narasi_indo'] = $request->file('narasi_indo')
                    ->store('buku_' . $halaman->id_buku . '/audio_narasi_indo', 'public');
            }

            if ($request->hasFile('narasi_sunda')) {
                if ($halaman->narasi_sunda) Storage::disk('public')->delete($halaman->narasi_sunda);
                $data['narasi_sunda'] = $request->file('narasi_sunda')
                    ->store('buku_' . $halaman->id_buku . '/audio_narasi_sunda', 'public');
            }

            if ($request->has('id_audio_latar')) {
                $data['id_audio_latar'] = $request->id_audio_latar; 
            }

            $halaman->update($data);

            return redirect()
                ->route('halaman.edit', $id_halaman)
                ->with('success', 'Multimedia halaman berhasil diperbarui.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengupdate multimedia: ' . $e->getMessage());
        }
    }

    public function destroy($id_halaman)
    {
        $halaman = Halaman::with('buku')->findOrFail($id_halaman);
        $id_buku = $halaman->id_buku;

        if ($halaman->buku->status_publikasi === 'Published') {
            return back()->with('error', 'Tidak dapat menghapus halaman dari buku yang dipublikasikan.');
        }

        $totalHalaman = Halaman::where('id_buku', $id_buku)->count();
        if ($totalHalaman <= 2) {
            return back()->with('error', 'Minimal harus tersisa 2 halaman.');
        }

        if ($halaman->path_gambar) Storage::disk('public')->delete($halaman->path_gambar);
        if ($halaman->narasi_indo) Storage::disk('public')->delete($halaman->narasi_indo);
        if ($halaman->narasi_sunda) Storage::disk('public')->delete($halaman->narasi_sunda);

        $halaman->delete();

        $sisaHalaman = Halaman::where('id_buku', $id_buku)
                              ->orderBy('nomor_halaman')
                              ->get();

        foreach ($sisaHalaman as $index => $h) {
            $h->nomor_halaman = $index + 1;
            $h->save();
        }

        return redirect()
            ->route('buku.halaman.index', $id_buku)
            ->with('success', 'Halaman berhasil dihapus dan nomor diurutkan ulang.');
    }

    public function reorder(Request $request, $id_buku)
    {
        $buku = Buku::findOrFail($id_buku);

        if ($buku->status_publikasi === 'Published') {
            return response()->json(['error' => 'Tidak diizinkan'], 422);
        }

        $request->validate([
            'urutan_id' => 'required|array',
            'urutan_id.*' => 'integer|exists:halaman,id_halaman'
        ]);

        foreach ($request->urutan_id as $index => $id) {
            Halaman::where('id_halaman', $id)
                   ->where('id_buku', $id_buku)
                   ->update(['nomor_halaman' => $index + 1]);
        }

        return response()->json(['message' => 'Urutan halaman berhasil diperbarui']);
    }
}