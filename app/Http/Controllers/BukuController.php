<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buku;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BukuController extends Controller
{
    public function index(Request $request)
    {
        $buku = Buku::orderBy('created_at', 'desc')->get();
        return view('dashboard', compact('buku'));
    }

    public function create()
    {
        return view('buku.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul_idn'       => 'required|string|max:255',
            'judul_sn'        => 'required|string|max:255',
            'penulis'         => 'required|string|max:100',
            'ilustrator'      => 'nullable|string|max:100',
            'deskripsi_idn'   => 'nullable|string',
            'deskripsi_sn'    => 'nullable|string',
            'warna_primer'    => 'nullable|string|max:7',
            'warna_sekunder'  => 'nullable|string|max:7',
            'file_buku'       => 'required|file|mimes:pdf|max:51200', 
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if ($request->hasFile('file_buku')) {
                $file = $request->file('file_buku');
                $folderName = Str::slug($request->judul_idn, '_');
                
                $pdfPath = $file->storeAs("books/{$folderName}", "mentahan_{$folderName}.pdf", 'public');
            }

            $buku = Buku::create([
                'id_pengelola'     => $request->user()->id_pengelola ?? 1,
                'judul_idn'        => $request->judul_idn,
                'judul_sn'         => $request->judul_sn,
                'nama_folder'      => $folderName ?? null,
                'penulis'          => $request->penulis,
                'ilustrator'       => $request->ilustrator,
                'deskripsi_idn'    => $request->deskripsi_idn,
                'deskripsi_sn'     => $request->deskripsi_sn,
                'warna_primer'     => $request->warna_primer,
                'warna_sekunder'   => $request->warna_sekunder,
                'status_publikasi' => 'Draft'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Buku berhasil ditambahkan dan proses konversi halaman dimulai.',
                'data' => $buku
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses berkas buku cerita digital: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail Informasi Buku',
            'data' => $buku
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        if ($buku->status_publikasi === 'Published') {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data. Buku yang telah dipublikasi harus ditarik (diubah ke Draft) terlebih dahulu.'
            ], 422);
        }

        $buku->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Informasi buku berhasil diperbarui',
            'data' => $buku
        ], 200);
    }

    public function destroy($id)
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        $buku->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Buku berhasil dihapus dari sistem'
        ], 200);
    }

    public function preview($id)
    {

        $buku = Buku::with([
            'halaman' => function($query) {
                $query->orderBy('nomor_halaman', 'asc');
            },
            'halaman.audioLatar', 
            'halaman.areaInteraktif'
        ])->find($id);

        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data pratinjau flipbook interaktif berhasil dimuat',
            'data' => $buku
        ], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_publikasi' => 'required|in:Draft,Published'
        ]);

        $buku = Buku::with('halaman')->find($id);
        
        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        if ($request->status_publikasi === 'Published') {
            
            if ($buku->halaman->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mempublikasikan buku. Setiap buku minimal harus memiliki 1 halaman sebagai cover buku.'
                ], 422);
            }

            foreach ($buku->halaman as $hal) {
                if (empty($hal->narasi_indo) || empty($hal->narasi_sunda) || is_null($hal->id_audio_latar)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Gagal mempublikasikan buku. Halaman nomor {$hal->nomor_halaman} belum memenuhi standar minimum syarat publikasi (Wajib memiliki audio narasi dua bahasa dan audio latar)."
                    ], 422);
                }
            }
        }

        $buku->update(['status_publikasi' => $request->status_publikasi]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status publikasi berhasil diubah menjadi ' . $request->status_publikasi,
            'data' => $buku
        ], 200);
    }
}