<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buku;
use Illuminate\Support\Facades\Validator;

class BukuController extends Controller
{
    public function index(Request $request)
    {
        $query = Buku::with('pengelola');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul_idn', 'LIKE', "%{$search}%")
                  ->orWhere('judul_sn', 'LIKE', "%{$search}%")
                  ->orWhere('penulis', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('status_publikasi')) {
            $query->where('status_publikasi', $request->status_publikasi);
        }

        $buku = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => 'Berhasil mengambil data buku',
            'data' => $buku
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul_idn' => 'required|string|max:255',
            'judul_sn' => 'required|string|max:255',
            'penulis' => 'required|string|max:100',
            'penerbit' => 'required|string|max:100',
            'tahun_terbit' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $buku = Buku::create([
            'id_pengelola' => $request->user()->id_pengelola, 
            'judul_idn' => $request->judul_idn,
            'judul_sn' => $request->judul_sn,
            'penulis' => $request->penulis,
            'penerbit' => $request->penerbit,
            'tahun_terbit' => $request->tahun_terbit,
            'ilustrator' => $request->ilustrator,
            'warna_primer' => $request->warna_primer,
            'warna_sekunder' => $request->warna_sekunder,
            'status_publikasi' => 'Draft'
        ]);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan',
            'data' => $buku
        ], 201);
    }

    public function show($id)
    {
        $buku = Buku::with(['pengelola', 'halaman'])->find($id);

        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        return response()->json([
            'message' => 'Detail Buku',
            'data' => $buku
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $buku = Buku::find($id);

        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        $buku->update($request->all());

        return response()->json([
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

        return response()->json(['message' => 'Buku berhasil dihapus'], 200);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status_publikasi' => 'required|in:Draft,Published'
        ]);

        $buku = Buku::find($id);
        
        if (!$buku) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

        $buku->update(['status_publikasi' => $request->status_publikasi]);

        return response()->json([
            'message' => 'Status publikasi berhasil diubah menjadi ' . $request->status_publikasi,
            'data' => $buku
        ], 200);
    }
}
