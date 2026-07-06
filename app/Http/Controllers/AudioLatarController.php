<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AudioLatar;

class AudioLatarController extends Controller
{
    public function index()
    {
        $audioLatar = AudioLatar::withCount('halaman')->with('halaman.buku')->get();
        return view('audio-latar.index', compact('audioLatar'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_audio' => 'required|string|max:100',
            'path_file' => 'required|file|mimes:mp3,m4a,mp4,x-m4a|extensions:mp3,m4a|max:1024',
        ], [
            'path_file.max'   => 'Ukuran file audio latar maksimal 1MB.',
            'path_file.mimes' => 'Format audio harus MP3 atau M4A.',
            'path_file.extensions' => 'Ekstensi file audio harus .mp3 atau .m4a.',
        ]);

        $path = $request->file('path_file')->store('buku/audio-latar', 'public');

        AudioLatar::create([
            'nama_audio' => $validated['nama_audio'],
            'path_file' => $path,
        ]);

        return back()->with('success', 'Audio latar berhasil ditambahkan');
    }

    public function delete(AudioLatar $audioLatar)
    {
        if ($audioLatar->halaman()->exists()) {
            return back()->withErrors([
                'delete' => 'Gagal menghapus: Audio latar "' . $audioLatar->nama_audio . '" sedang digunakan oleh ' . $audioLatar->halaman()->count() . ' halaman.'
            ]);
        }

        try {
            if ($audioLatar->path_file && \Illuminate\Support\Facades\Storage::disk('public')->exists($audioLatar->path_file)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($audioLatar->path_file);
            }

            $audioLatar->delete();

            return back()->with('success', 'Audio latar berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors([
                'delete' => 'Gagal menghapus audio latar: ' . $e->getMessage()
            ]);
        }
    }
}
