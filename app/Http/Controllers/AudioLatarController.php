<?php

namespace App\Http\Controllers;

use App\Models\AudioLatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AudioLatarController extends Controller
{
    public function index(Request $request)
    {
        $audioLatar = AudioLatar::withCount('halaman')->with('halaman.buku')->get();
        $ref = $request->query('ref');
        return view('audio-latar.index', compact('audioLatar', 'ref'));
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
            'path_file.min'   => 'File audio latar tidak boleh kosong.',
        ]);

        try {
            $file = $request->file('path_file');
            $disk = 's3';
            config(['filesystems.disks.s3.throw' => true]);

            $path = Storage::disk($disk)->putFile('buku/audio-latar', $file, [
                'visibility' => 'public',
                'ContentType' => $file->getMimeType()
            ]);
            
            if (!is_string($path) || trim($path) === '') {
                throw new \RuntimeException('S3 mengembalikan path upload yang kosong. Periksa konfigurasi bucket, kredensial AWS, dan izin akses.');
            }

            $storage = Storage::disk($disk);
            $url = null;
            try {
                $url = $storage->url($path);
            } catch (Throwable $e) {
                $url = null;
            }

            \Log::info('audio_latar_upload', [
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
                'url' => $url,
            ]);

            AudioLatar::create([
                'nama_audio' => $validated['nama_audio'],
                'path_file' => $path,
            ]);

            $ref = $request->input('ref');
            $redirectUrl = route('audio-latar.index') . ($ref ? '?ref=' . urlencode($ref) : '');
            return redirect($redirectUrl)->with('success', 'Audio latar berhasil ditambahkan');
        } catch (Throwable $e) {
            \Log::error('audio_latar_upload_failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['path_file' => 'Gagal mengunggah audio latar: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function delete(Request $request, AudioLatar $audioLatar)
    {
        $ref = $request->input('ref');
        $redirectUrl = route('audio-latar.index') . ($ref ? '?ref=' . urlencode($ref) : '');

        if ($audioLatar->halaman()->exists()) {
            return redirect($redirectUrl)->withErrors([
                'delete' => 'Gagal menghapus: Audio latar "' . $audioLatar->nama_audio . '" sedang digunakan oleh ' . $audioLatar->halaman()->count() . ' halaman.'
            ]);
        }

        try {
            if ($audioLatar->path_file) {
                try {
                    Storage::disk('s3')->delete($audioLatar->path_file);
                } catch (Throwable $e) {
                    \Log::warning('audio_latar_delete_storage_failed', [
                        'path' => $audioLatar->path_file,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $audioLatar->delete();

            return redirect($redirectUrl)->with('success', 'Audio latar berhasil dihapus');
        } catch (\Exception $e) {
            return redirect($redirectUrl)->withErrors([
                'delete' => 'Gagal menghapus audio latar: ' . $e->getMessage()
            ]);
        }
    }
}
