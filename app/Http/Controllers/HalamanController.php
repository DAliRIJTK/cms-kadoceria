<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Halaman;
use App\Models\AudioLatar;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToImage\Pdf;

class HalamanController extends Controller
{
    public function index($id_buku)
    {
        $buku = Buku::findOrFail($id_buku);
        
        $halaman = Halaman::with('audioLatar')
                          ->where('id_buku', $id_buku)
                          ->orderBy('nomor_halaman', 'asc')
                          ->get();

        $audioLatar = AudioLatar::all(); 

        return view('buku.kelola_halaman', compact('buku', 'halaman', 'audioLatar'));
    }

    public function processPdfFromStorage($id_buku)
    {
        $buku = Buku::findOrFail($id_buku);

        if ($buku->status_publikasi === 'Published') {
            return redirect()->route('dashboard')->with('error', 'Buku telah dipublikasikan. Ubah status menjadi Draft terlebih dahulu.');
        }

        $sudahAdaHalaman = Halaman::where('id_buku', $id_buku)->exists();
        if ($sudahAdaHalaman) {
            return redirect()->route('buku.halaman.index', $id_buku)->with('info', 'Buku ini sudah memiliki halaman hasil konversi.');
        }

        $relativePdfPath = "books/{$buku->nama_folder}/mentahan_{$buku->nama_folder}.pdf";
        $absolutePdfPath = Storage::disk('public')->path($relativePdfPath);

        if (!file_exists($absolutePdfPath)) {
            return redirect()->route('dashboard')->with('error', 'Berkas mentahan PDF tidak ditemukan di sistem penyimpanan.');
        }

        try {
            $pdf = new Pdf($absolutePdfPath);
            $jumlahHalaman = $pdf->pageCount();
            $storageDir = 'buku_' . $id_buku;

            if (!Storage::disk('public')->exists($storageDir)) {
                Storage::disk('public')->makeDirectory($storageDir);
            }

            for ($i = 1; $i <= $jumlahHalaman; $i++) {
                $namaFileGambar = 'halaman_' . $i . '.webp';
                $relativeImagePath = $storageDir . '/' . $namaFileGambar;
                $absoluteImagePath = Storage::disk('public')->path($relativeImagePath);

                $pdf->selectPage($i)
                    ->format(\Spatie\PdfToImage\Enums\OutputFormat::Webp)
                    ->save($absoluteImagePath);

                list($width, $height) = getimagesize($absoluteImagePath);

                Halaman::create([
                    'id_buku'         => $id_buku,
                    'nomor_halaman'   => $i,
                    'path_gambar'     => $relativeImagePath,
                    'panjang_halaman' => $height,
                    'lebar_halaman'   => $width,
                ]);
            }

            return redirect()->route('buku.halaman.index', $id_buku)
                             ->with('success', 'PDF Berhasil dikonversi menjadi ' . $jumlahHalaman . ' halaman gambar digital!');

        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Gagal mengekstrak halaman PDF otomatis: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id_halaman)
    {
        $halaman = Halaman::with('buku')->findOrFail($id_halaman);

        if ($halaman->buku->status_publikasi === 'Published') {
            return redirect()->back()->with('error', 'Gagal mengubah data. Buku terkait telah dipublikasikan.');
        }

        $request->validate([
            'narasi_indo'    => 'nullable|file|mimes:m4a,wav,mp3,mpga|max:2048', // batas dinaikkan ke 2MB agar lebih aman
            'narasi_sunda'   => 'nullable|file|mimes:m4a,wav,mp3,mpga|max:2048',
            'id_audio_latar' => 'nullable|integer|exists:audio_latar,id_audio_latar',
        ]);

        try {
            $dataUpdate = [];

            if ($request->hasFile('narasi_indo')) {
                if ($halaman->narasi_indo) {
                    Storage::disk('public')->delete($halaman->narasi_indo);
                }
                $dataUpdate['narasi_indo'] = $request->file('narasi_indo')->store('buku_' . $halaman->id_buku . '/audio_narasi_indonesia', 'public');
            }

            if ($request->hasFile('narasi_sunda')) {
                if ($halaman->narasi_sunda) {
                    Storage::disk('public')->delete($halaman->narasi_sunda);
                }
                $dataUpdate['narasi_sunda'] = $request->file('narasi_sunda')->store('buku_' . $halaman->id_buku . '/audio_narasi_sunda', 'public');
            }

            $dataUpdate['id_audio_latar'] = $request->filled('id_audio_latar') ? $request->id_audio_latar : null;

            $halaman->update($dataUpdate);

            return redirect()->back()->with('success', 'Multimedia halaman ' . $halaman->nomor_halaman . ' berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunggah berkas audio: ' . $e->getMessage());
        }
    }

    public function destroy($id_halaman)
    {
        $halaman = Halaman::with('buku')->findOrFail($id_halaman);
        $id_buku = $halaman->id_buku;

        if ($halaman->buku->status_publikasi === 'Published') {
            return redirect()->back()->with('error', 'Gagal menghapus. Buku terkait telah dipublikasikan.');
        }

        $totalHalamanSaatIni = Halaman::where('id_buku', $id_buku)->count();
        if ($totalHalamanSaatIni <= 2) {
            return redirect()->back()->with('error', 'Penghapusan dibatalkan. Sisa halaman tidak boleh kurang dari 2 halaman.');
        }

        if ($halaman->path_gambar) Storage::disk('public')->delete($halaman->path_gambar);
        if ($halaman->narasi_indo) Storage::disk('public')->delete($halaman->narasi_indo);
        if ($halaman->narasi_sunda) Storage::disk('public')->delete($halaman->narasi_sunda);

        $halaman->delete();

        $sisaHalaman = Halaman::where('id_buku', $id_buku)->orderBy('nomor_halaman', 'asc')->get();
        foreach ($sisaHalaman as $index => $hal) {
            $hal->nomor_halaman = $index + 1;
            $hal->save();
        }

        return redirect()->back()->with('success', 'Halaman berhasil dihapus dan urutan nomor disesuaikan ulang.');
    }

    public function reorder(Request $request, $id_buku)
    {
        $buku = Buku::find($id_buku);
        if (!$buku || $buku->status_publikasi === 'Published') {
            return response()->json(['status' => 'error', 'message' => 'Aksi ditolak.'], 422);
        }

        $request->validate([
            'urutan_id' => 'required|array',
            'urutan_id.*' => 'integer|exists:halaman,id_halaman'
        ]);

        foreach ($request->urutan_id as $index => $id_halaman) {
            Halaman::where('id_halaman', $id_halaman)
                   ->where('id_buku', $id_buku)
                   ->update(['nomor_halaman' => $index + 1]);
        }

        return response()->json(['status' => 'success', 'message' => 'Susunan posisi halaman berhasil diperbarui.']);
    }
}