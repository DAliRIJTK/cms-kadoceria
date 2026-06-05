<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AreaInteraktif;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AreaInteraktifController extends Controller
{
    /**
     * Mengambil semua area interaktif pada satu halaman
     */
    public function index($id_halaman)
    {
        $halaman = Halaman::find($id_halaman);
        if (!$halaman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Halaman tidak ditemukan'
            ], 404);
        }

        $area = AreaInteraktif::where('id_halaman', $id_halaman)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil data area interaktif',
            'data' => $area
        ], 200);
    }

    /**
     * Menyimpan Area Interaktif Baru
     */
    public function store(Request $request, $id_halaman)
    {
        $halaman = Halaman::with('buku')->find($id_halaman);
        if (!$halaman) {
            return response()->json([
                'status' => 'error',
                'message' => 'Halaman tidak ditemukan'
            ], 404);
        }

        if ($halaman->buku->status_publikasi === 'Published') {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambah data. Buku terkait telah dipublikasikan, silakan tarik kembali ke Draft.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'x'            => 'required|integer|min:0',
            'y'            => 'required|integer|min:0',
            'lebar_area'   => 'required|integer|min:1',
            'panjang_area' => 'required|integer|min:1',
            'audio_indo'   => 'required|file|mimes:m4a,wav,mp3,mpga|max:2048', 
            'audio_sunda'  => 'required|file|mimes:m4a,wav,mp3,mpga|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validasi Resolusi Kanvas Koordinat
        $maxW = $halaman->lebar_halaman ?? 2000; 
        $maxH = $halaman->panjang_halaman ?? 2000;

        if (($request->x + $request->lebar_area) > $maxW || ($request->y + $request->panjang_area) > $maxH) {
            return response()->json([
                'status' => 'error',
                'message' => "Gagal menyimpan. Posisi atau ukuran area interaktif melebihi batas resolusi gambar halaman (Maksimal Lebar: {$maxW}px, Tinggi: {$maxH}px)."
            ], 422);
        }

        try {
            $data = $request->only(['x', 'y', 'lebar_area', 'panjang_area']);
            $data['id_halaman'] = $id_halaman;

            $audioDir = 'buku_' . $halaman->id_buku . '/audio_interaktif';

            if ($request->hasFile('audio_indo')) {
                $data['audio_indo'] = $request->file('audio_indo')->store($audioDir, 'public');
            }

            if ($request->hasFile('audio_sunda')) {
                $data['audio_sunda'] = $request->file('audio_sunda')->store($audioDir, 'public');
            }

            $area = AreaInteraktif::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Area interaktif baru dan kelengkapan audio berhasil disimpan',
                'data' => $area
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pembuatan area interaktif: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui Data Area Interaktif
     */
    public function update(Request $request, $id_area)
    {
        $area = AreaInteraktif::with('halaman.buku')->find($id_area);

        if (!$area) {
            return response()->json([
                'status' => 'error',
                'message' => 'Area interaktif tidak ditemukan'
            ], 404);
        }

        if ($area->halaman->buku->status_publikasi === 'Published') {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data. Buku terkait telah dipublikasikan, silakan tarik kembali ke Draft.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'x'            => 'nullable|integer|min:0',
            'y'            => 'nullable|integer|min:0',
            'lebar_area'   => 'nullable|integer|min:1',
            'panjang_area' => 'nullable|integer|min:1',
            'audio_indo'   => 'nullable|file|mimes:m4a,wav,mp3,mpga|max:2048',
            'audio_sunda'  => 'nullable|file|mimes:m4a,wav,mp3,mpga|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $finalX = $request->has('x') ? $request->x : $area->x;
        $finalY = $request->has('y') ? $request->y : $area->y;
        $finalW = $request->has('lebar_area') ? $request->lebar_area : $area->lebar_area;
        $finalH = $request->has('panjang_area') ? $request->panjang_area : $area->panjang_area;

        $maxW = $area->halaman->lebar_halaman ?? 2000;
        $maxH = $area->halaman->panjang_halaman ?? 2000;

        if (($finalX + $finalW) > $maxW || ($finalY + $finalH) > $maxH) {
            return response()->json([
                'status' => 'error',
                'message' => "Gagal memperbarui. Kombinasi posisi baru melampaui batas dimensi gambar halaman (Maksimal Lebar: {$maxW}px, Tinggi: {$maxH}px)."
            ], 422);
        }

        try {
            $dataUpdate = $request->only(['x', 'y', 'lebar_area', 'panjang_area']);
            $audioDir = 'buku_' . $area->halaman->id_buku . '/audio_interaktif';

            if ($request->hasFile('audio_indo')) {
                if ($area->audio_indo && Storage::disk('public')->exists($area->audio_indo)) {
                    Storage::disk('public')->delete($area->audio_indo);
                }
                $dataUpdate['audio_indo'] = $request->file('audio_indo')->store($audioDir, 'public');
            }

            if ($request->hasFile('audio_sunda')) {
                if ($area->audio_sunda && Storage::disk('public')->exists($area->audio_sunda)) {
                    Storage::disk('public')->delete($area->audio_sunda);
                }
                $dataUpdate['audio_sunda'] = $request->file('audio_sunda')->store($audioDir, 'public');
            }

            $area->update($dataUpdate);

            return response()->json([
                'status' => 'success',
                'message' => 'Area interaktif berhasil diperbarui',
                'data' => $area
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui area interaktif: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id_area)
    {
        $area = AreaInteraktif::with('halaman.buku')->find($id_area);

        if (!$area) {
            return response()->json([
                'status' => 'error',
                'message' => 'Area interaktif tidak ditemukan'
            ], 404);
        }

        if ($area->halaman->buku->status_publikasi === 'Published') {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data. Buku terkait telah dipublikasikan, silakan tarik kembali ke Draft.'
            ], 422);
        }

        try {
            if ($area->audio_indo && Storage::disk('public')->exists($area->audio_indo)) {
                Storage::disk('public')->delete($area->audio_indo);
            }

            if ($area->audio_sunda && Storage::disk('public')->exists($area->audio_sunda)) {
                Storage::disk('public')->delete($area->audio_sunda);
            }

            $area->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Area interaktif beserta berkas audio di dalamnya berhasil dihapus permanen'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus area interaktif: ' . $e->getMessage()
            ], 500);
        }
    }
}