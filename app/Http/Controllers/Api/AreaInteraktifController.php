<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AreaInteraktif;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AreaInteraktifController extends Controller
{
    public function index($id_halaman)
    {
        $area = AreaInteraktif::where('id_halaman', $id_halaman)->get();

        return response()->json([
            'message' => 'Berhasil mengambil data area interaktif',
            'data' => $area
        ], 200);
    }

    public function store(Request $request, $id_halaman)
    {
        $halaman = Halaman::find($id_halaman);
        if (!$halaman) {
            return response()->json(['message' => 'Halaman tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'x' => 'required|integer',
            'y' => 'required|integer',
            'lebar_area' => 'required|integer',
            'panjang_area' => 'required|integer',
            'audio_indo' => 'nullable|mimes:mp3,wav,m4a|max:5120',
            'audio_sunda' => 'nullable|mimes:mp3,wav,m4a|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['x', 'y', 'lebar_area', 'panjang_area']);
        $data['id_halaman'] = $id_halaman;

        $audioDir = 'public/audio_halaman_' . $id_halaman;

        if ($request->hasFile('audio_indo')) {
            $pathIndo = $request->file('audio_indo')->store($audioDir);
            $data['audio_indo'] = str_replace('public/', '', $pathIndo); 
        }

        if ($request->hasFile('audio_sunda')) {
            $pathSunda = $request->file('audio_sunda')->store($audioDir);
            $data['audio_sunda'] = str_replace('public/', '', $pathSunda);
        }

        $area = AreaInteraktif::create($data);

        return response()->json([
            'message' => 'Area interaktif dan audio berhasil disimpan',
            'data' => $area
        ], 201);
    }

    public function update(Request $request, $id_area)
    {
        $area = AreaInteraktif::find($id_area);

        if (!$area) {
            return response()->json(['message' => 'Area interaktif tidak ditemukan'], 404);
        }

        $data = $request->only(['x', 'y', 'lebar_area', 'panjang_area']);
        $audioDir = 'public/audio_halaman_' . $area->id_halaman;

        if ($request->hasFile('audio_indo')) {
            if ($area->audio_indo && Storage::exists('public/' . $area->audio_indo)) {
                Storage::delete('public/' . $area->audio_indo);
            }
            $pathIndo = $request->file('audio_indo')->store($audioDir);
            $data['audio_indo'] = str_replace('public/', '', $pathIndo);
        }

        if ($request->hasFile('audio_sunda')) {
            if ($area->audio_sunda && Storage::exists('public/' . $area->audio_sunda)) {
                Storage::delete('public/' . $area->audio_sunda);
            }
            $pathSunda = $request->file('audio_sunda')->store($audioDir);
            $data['audio_sunda'] = str_replace('public/', '', $pathSunda);
        }

        $area->update($data);

        return response()->json([
            'message' => 'Area interaktif berhasil diperbarui',
            'data' => $area
        ], 200);
    }

    public function destroy($id_area)
    {
        $area = AreaInteraktif::find($id_area);

        if (!$area) {
            return response()->json(['message' => 'Area interaktif tidak ditemukan'], 404);
        }

        if ($area->audio_indo && Storage::exists('public/' . $area->audio_indo)) {
            Storage::delete('public/' . $area->audio_indo);
        }
        if ($area->audio_sunda && Storage::exists('public/' . $area->audio_sunda)) {
            Storage::delete('public/' . $area->audio_sunda);
        }

        $area->delete();

        return response()->json(['message' => 'Area interaktif dan file audionya berhasil dihapus'], 200);
    }
}
