<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AudioLatar;
use App\Models\Halaman;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AudioLatarController extends Controller
{
    public function index()
    {
        $audioLatar = AudioLatar::orderBy('nama_audio', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar master audio latar',
            'data' => $audioLatar
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_audio' => 'required|string|max:255|unique:audio_latar,nama_audio',
            'file_audio' => 'required|file|mimes:m4a,wav,mp3,mpga|max:5120', // Maksimal 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->hasFile('file_audio')) {
                $pathFile = $request->file('file_audio')->store('master_backsound', 'public');
                
                $audioLatar = AudioLatar::create([
                    'nama_audio' => $request->nama_audio,
                    'path_file'  => $pathFile,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Audio latar baru berhasil ditambahkan ke library',
                    'data' => $audioLatar
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Berkas file audio tidak ditemukan'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan audio latar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id_audio_latar)
    {
        $audioLatar = AudioLatar::find($id_audio_latar);

        if (!$audioLatar) {
            return response()->json([
                'status' => 'error',
                'message' => 'Audio latar tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_audio' => 'required|string|max:255|unique:audio_latar,nama_audio,' . $id_audio_latar . ',id_audio_latar',
            'file_audio' => 'nullable|file|mimes:m4a,wav,mp3,mpga|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $dataUpdate = [
                'nama_audio' => $request->nama_audio
            ];

            if ($request->hasFile('file_audio')) {
                if ($audioLatar->path_file && Storage::disk('public')->exists($audioLatar->path_file)) {
                    Storage::disk('public')->delete($audioLatar->path_file);
                }
                $dataUpdate['path_file'] = $request->file('file_audio')->store('master_backsound', 'public');
            }

            $audioLatar->update($dataUpdate);

            return response()->json([
                'status' => 'success',
                'message' => 'Audio latar berhasil diperbarui',
                'data' => $audioLatar
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui audio latar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id_audio_latar)
    {
        $audioLatar = AudioLatar::find($id_audio_latar);

        if (!$audioLatar) {
            return response()->json([
                'status' => 'error',
                'message' => 'Audio latar tidak ditemukan'
            ], 404);
        }

        try {
            $sedangDigunakan = Halaman::where('id_audio_latar', $id_audio_latar)->exists();
            if ($sedangDigunakan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menghapus. Audio latar ini tidak boleh dihapus karena sedang digunakan aktif oleh beberapa halaman buku cerita.'
                ], 422);
            }

            if ($audioLatar->path_file && Storage::disk('public')->exists($audioLatar->path_file)) {
                Storage::disk('public')->delete($audioLatar->path_file);
            }

            $audioLatar->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Audio latar berhasil dihapus dari master library secara permanen'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus audio latar: ' . $e->getMessage()
            ], 500);
        }
    }
}