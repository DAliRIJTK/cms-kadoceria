<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AreaInteraktif;

class BoundingBoxController extends Controller
{
    public function store(Request $request)
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

        $halaman = \App\Models\Halaman::findOrFail($validated['id_halaman']);
        $buku = $halaman->buku;

        if ($buku->status_publikasi === 'Terbit') {
            return response()->json([
                'success' => false,
                'message' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'
            ], 422);
        }

        if (($validated['x_pct'] + $validated['w_pct'] > 100) || ($validated['y_pct'] + $validated['h_pct'] > 100)) {
            return response()->json([
                'success' => false,
                'message' => 'Area interaktif tidak boleh melebihi ukuran halaman buku.'
            ], 422);
        }

        $existingAreas = AreaInteraktif::where('id_halaman', $halaman->id_halaman)->get();
        foreach ($existingAreas as $area) {
            if ($validated['x_pct'] < $area->x_pct + $area->w_pct &&
                $validated['x_pct'] + $validated['w_pct'] > $area->x_pct &&
                $validated['y_pct'] < $area->y_pct + $area->h_pct &&
                $validated['y_pct'] + $validated['h_pct'] > $area->y_pct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Area interaktif tidak boleh menimpa area interaktif lainnya pada halaman yang sama.'
                ], 422);
            }
        }

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
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server saat menyimpan area.'], 500);
        }
    }

    public function update(Request $request, AreaInteraktif $area)
    {
        $validated = $request->validate([
            'x'            => 'required|integer',
            'y'            => 'required|integer',
            'lebar_area'   => 'required|integer|min:1',
            'panjang_area' => 'required|integer|min:1',
            'x_pct'        => 'nullable|numeric|min:0|max:100',
            'y_pct'        => 'nullable|numeric|min:0|max:100',
            'w_pct'        => 'nullable|numeric|min:0|max:100',
            'h_pct'        => 'nullable|numeric|min:0|max:100',
        ]);

        $buku = $area->halaman->buku;

        if ($buku->status_publikasi === 'Terbit') {
            return response()->json([
                'success' => false,
                'message' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'
            ], 422);
        }

        $x_pct = $request->input('x_pct', $area->x_pct);
        $y_pct = $request->input('y_pct', $area->y_pct);
        $w_pct = $request->input('w_pct', $area->w_pct);
        $h_pct = $request->input('h_pct', $area->h_pct);

        if (($x_pct + $w_pct > 100) || ($y_pct + $h_pct > 100)) {
            return response()->json([
                'success' => false,
                'message' => 'Area interaktif tidak boleh melebihi ukuran halaman buku.'
            ], 422);
        }

        $existingAreas = AreaInteraktif::where('id_halaman', $area->id_halaman)
            ->where('id_area', '!=', $area->id_area)
            ->get();
        foreach ($existingAreas as $other) {
            if ($x_pct < $other->x_pct + $other->w_pct &&
                $x_pct + $w_pct > $other->x_pct &&
                $y_pct < $other->y_pct + $other->h_pct &&
                $y_pct + $h_pct > $other->y_pct) {
                return response()->json([
                    'success' => false,
                    'message' => 'Area interaktif tidak boleh menimpa area interaktif lainnya pada halaman yang sama.'
                ], 422);
            }
        }

        try {
            $area->update([
                'x'            => $validated['x'],
                'y'            => $validated['y'],
                'lebar_area'   => $validated['lebar_area'],
                'panjang_area' => $validated['panjang_area'],
                'x_pct'        => $x_pct,
                'y_pct'        => $y_pct,
                'w_pct'        => $w_pct,
                'h_pct'        => $h_pct,
            ]);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            \Log::error('updateAreaInteraktif error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server saat memperbarui area.'], 500);
        }
    }

    public function destroy(AreaInteraktif $area)
    {
        $buku = $area->halaman->buku;

        if ($buku->status_publikasi === 'Terbit') {
            return response()->json([
                'success' => false,
                'message' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'
            ], 422);
        }

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
            \Log::error('deleteAreaInteraktif error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server saat menghapus area.'], 500);
        }
    }
}
