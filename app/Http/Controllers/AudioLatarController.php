<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AudioLatar;

class AudioLatarController extends Controller
{
    public function index()
    {
        $audioLatar = AudioLatar::all();
        return view('audio-latar.index', compact('audioLatar'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_audio' => 'required|string|max:100',
            'path_file' => 'required|file|mimes:mp3,wav,ogg,m4a|max:10240',
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
