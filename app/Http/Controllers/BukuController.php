<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buku;
use App\Models\Halaman;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Spatie\PdfToImage\Pdf;

class BukuController extends Controller
{

    public function index()
    {
        $buku = Buku::withCount('halaman')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return view('dashboard', compact('buku'));
    }

    public function create()
    {
        return view('buku.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul_idn'     => 'required|string|max:255',
            'judul_sn'      => 'required|string|max:255',
            'penulis'       => 'required|string|max:100',
            'ilustrator'    => 'nullable|string|max:100',
            'deskripsi_idn' => 'nullable|string',
            'deskripsi_sn'  => 'nullable|string',
            'file_buku'     => 'required|file|mimes:pdf|max:51200',
        ]);

        return DB::transaction(function () use ($request) {
            $file = $request->file('file_buku');
            $folderName = Str::slug($request->judul_idn, '_');

            $pdfPath = $file->storeAs("books/{$folderName}", "mentahan_{$folderName}.pdf", 'public');

        $buku = Buku::create([
            'id_pengelola'     => auth()->id(),
            'judul_idn'        => $request->judul_idn,
            'judul_sn'         => $request->judul_sn,
            'penulis'          => $request->penulis,
            'ilustrator'       => $request->ilustrator,
            'deskripsi_idn'    => $request->deskripsi_idn,
            'deskripsi_sn'     => $request->deskripsi_sn,
            'nama_folder'      => $folderName,
            'status_publikasi' => 'Draft',
        ]);

        $buku = Buku::findOrFail($request->id_buku);

        if ($buku->status_publikasi === 'Published') {
            return redirect()->route('dashboard')->with('error', 'Buku telah dipublikasikan. Ubah status menjadi Draft terlebih dahulu.');
        }

        $sudahAdaHalaman = Halaman::where('id_buku', $buku->id_buku)->exists();
        if ($sudahAdaHalaman) {
            return redirect()->route('buku.halaman.index', $buku->id_buku)->with('info', 'Buku ini sudah memiliki halaman hasil konversi.');
        }

        $relativePdfPath = "books/{$buku->nama_folder}/mentahan_{$buku->nama_folder}.pdf";
        $absolutePdfPath = Storage::disk('public')->path($relativePdfPath);

        if (!file_exists($absolutePdfPath)) {
            return redirect()->route('dashboard')->with('error', 'Berkas mentahan PDF tidak ditemukan di sistem penyimpanan.');
        }

        try {
            $pdf = new Pdf($absolutePdfPath);
            $jumlahHalaman = $pdf->pageCount();
            $storageDir = 'buku_' . $buku->id_buku;

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
                    'id_buku'         => $buku->id_buku,
                    'nomor_halaman'   => $i,
                    'path_gambar'     => $relativeImagePath,
                    'panjang_halaman' => $height,
                    'lebar_halaman'   => $width,
                ]);
            }

            return redirect()->route('buku.halaman.index', $buku->id_buku)
                             ->with('success', 'PDF Berhasil dikonversi menjadi ' . $jumlahHalaman . ' halaman gambar digital!');

        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Gagal mengekstrak halaman PDF otomatis: ' . $e->getMessage());
        }

        });
    }

    public function show($id_buku)
    {
        $buku = Buku::withCount(['halaman', 'halaman as halaman_lengkap' => function($query) {
            $query->whereNotNull('narasi_indo')
                  ->whereNotNull('narasi_sunda')
                  ->whereNotNull('id_audio_latar');
        }])->findOrFail($id_buku);

        return view('buku.show', compact('buku'));
    }

    public function updateStatus(Request $request, $id_buku)
    {
        $request->validate([
            'status_publikasi' => 'required|in:Draft,Published'
        ]);

        $buku = Buku::findOrFail($id_buku);

        if ($request->status_publikasi === 'Published') {
            $adaHalamanBelumLengkap = Halaman::where('id_buku', $id_buku)
                ->where(function($query) {
                    $query->whereNull('narasi_indo')
                          ->orWhereNull('narasi_sunda')
                          ->orWhereNull('id_audio_latar');
                })->exists();

            if ($adaHalamanBelumLengkap) {
                return back()->with('error', 'Gagal mempublikasikan. Pastikan semua halaman sudah dilengkapi file narasi dan audio latarnya.');
            }
        }

        $buku->update(['status_publikasi' => $request->status_publikasi]);

        return back()->with('success', 'Status publikasi berhasil diubah menjadi ' . $request->status_publikasi);
    }

    public function preview($id_buku)
    {
        $buku = Buku::with([
            'halaman' => fn($q) => $q->orderBy('nomor_halaman'),
            'halaman.audioLatar',
            'halaman.areaInteraktif'
        ])->findOrFail($id_buku);

        return view('buku.preview', compact('buku'));
    }

    public function destroy($id_buku)
    {
        $buku = Buku::findOrFail($id_buku);

        try {

            if ($buku->nama_folder) {
                Storage::disk('public')->deleteDirectory("books/{$buku->nama_folder}");
            }

            Storage::disk('public')->deleteDirectory("buku_{$buku->id_buku}");

            $buku->delete();

            return redirect()->route('dashboard')->with('success', 'Buku beserta seluruh aset file di dalamnya berhasil dihapus bersih.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus buku: ' . $e->getMessage());
        }
    }
}