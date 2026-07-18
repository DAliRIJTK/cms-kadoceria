<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Halaman;
use App\Models\AreaInteraktif;
use Illuminate\Support\Facades\Log;

class AudioController extends Controller
{
    public function storeAreaAudio(Request $request, AreaInteraktif $area)
    {

        $validated = $request->validate([
            'audio_type' => 'required|in:indo,sunda',
            'audio_file' => 'required|file|mimes:mp3,m4a,mp4,x-m4a|extensions:mp3,m4a|max:1024',
        ], [
            'audio_file.max'   => 'Ukuran file audio maksimal 1MB.',
            'audio_file.min'   => 'File audio tidak boleh kosong.',
            'audio_file.mimes' => 'Format audio harus MP3 atau M4A.',
            'audio_file.extensions' => 'Ekstensi file audio harus .mp3 atau .m4a.',
        ]);

        try {
            $uploadedHash = md5_file($request->file('audio_file')->getRealPath());

            Log::info('audio_area_upload_received', [
                'area_id' => $area->id_area,
                'audio_type' => $request->input('audio_type'),
                'before_audio_indo' => $area->audio_indo,
                'before_audio_sunda' => $area->audio_sunda,
            ]);

            // Check duplicate against opposite language
            if ($validated['audio_type'] === 'indo') {
                if ($area->audio_sunda && Storage::disk('s3')->exists($area->audio_sunda)) {
                    $otherHash = md5((string) Storage::disk('s3')->get($area->audio_sunda));
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio Indonesia tidak boleh sama dengan file audio Sunda untuk area ini.';
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $errMsg], 422);
                        }
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            } else {
                if ($area->audio_indo && Storage::disk('s3')->exists($area->audio_indo)) {
                    $otherHash = md5((string) Storage::disk('s3')->get($area->audio_indo));
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio Sunda tidak boleh sama dengan file audio Indonesia untuk area ini.';
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $errMsg], 422);
                        }
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            }

            $field = 'audio_' . $validated['audio_type'];

            Log::info('audio_area_upload_will_delete', [
                'area_id' => $area->id_area,
                'audio_type' => $validated['audio_type'],
                'field' => $field,
                'target_path' => $area->$field,
            ]);

            if ($area->$field && Storage::disk('s3')->exists($area->$field)) {
                Storage::disk('s3')->delete($area->$field);
            }

            $file = $request->file('audio_file');
            $path = Storage::disk('s3')->putFile('buku/audio', $file, [
                'visibility' => 'public',
                'ContentType' => $file->getMimeType()
            ]);

            $area->$field = $path;
            $area->save();

            $area->halaman->buku->syncStorageStructure();

            $lang = $validated['audio_type'] === 'indo' ? 'Indonesia' : 'Sunda';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Audio {$lang} area berhasil diunggah",
                    'url' => Storage::disk('s3')->url($path)
                ]);
            }
            return back()->with('success', "Audio {$lang} area berhasil diunggah");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['audio' => 'Gagal menyimpan audio: ' . $e->getMessage()]);
        }
    }

    public function storeNarasi(Request $request, Halaman $halaman)
    {
        if ($halaman->nomor_halaman === 1) {
            $errMsg = 'Halaman cover tidak boleh memiliki audio narasi.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errMsg], 422);
            }
            return back()->withErrors(['audio' => $errMsg]);
        }

        $validated = $request->validate([
            'narasi_type' => 'required|in:indo,sunda',
            'audio_file' => 'required|file|mimes:mp3,m4a,wav,mp4,x-m4a|extensions:mp3,m4a|max:1024',
        ], [
            'audio_file.max'   => 'Ukuran file audio maksimal 1MB.',
            'audio_file.min'   => 'File audio tidak boleh kosong.',
            'audio_file.mimes' => 'Format audio harus MP3 atau M4A.',
            'audio_file.extensions' => 'Ekstensi file audio harus .mp3 atau .m4a.',
        ]);

        try {
            $uploadedHash = md5_file($request->file('audio_file')->getRealPath());

            // Check duplicate against opposite language
            if ($validated['narasi_type'] === 'indo') {
                if ($halaman->narasi_sunda && Storage::disk('s3')->exists($halaman->narasi_sunda)) {
                    $otherHash = md5((string) Storage::disk('s3')->get($halaman->narasi_sunda));
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio narasi Indonesia tidak boleh sama dengan file audio narasi Sunda untuk halaman ini.';
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $errMsg], 422);
                        }
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            } else {
                if ($halaman->narasi_indo && Storage::disk('s3')->exists($halaman->narasi_indo)) {
                    $otherHash = md5((string) Storage::disk('s3')->get($halaman->narasi_indo));
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio narasi Sunda tidak boleh sama dengan file audio narasi Indonesia untuk halaman ini.';
                        if ($request->wantsJson()) {
                            return response()->json(['success' => false, 'message' => $errMsg], 422);
                        }
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            }

            $file = $request->file('audio_file');
            $path = Storage::disk('s3')->putFile('buku/narasi', $file, [
                'visibility' => 'public',
                'ContentType' => $file->getMimeType()
            ]);

            switch ($validated['narasi_type']) {
                case 'indo':
                    if ($halaman->narasi_indo && Storage::disk('s3')->exists($halaman->narasi_indo)) {
                        Storage::disk('s3')->delete($halaman->narasi_indo);
                    }
                    $halaman->narasi_indo = $path;
                    $message = 'Narasi Indonesia berhasil diperbarui';
                    break;

                case 'sunda':
                    if ($halaman->narasi_sunda && Storage::disk('s3')->exists($halaman->narasi_sunda)) {
                        Storage::disk('s3')->delete($halaman->narasi_sunda);
                    }
                    $halaman->narasi_sunda = $path;
                    $message = 'Narasi Sunda berhasil diperbarui';
                    break;
            }

            $halaman->save();
            $halaman->buku->syncStorageStructure();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'url' => Storage::disk('s3')->url($path)
                ]);
            }
            return back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['audio' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function deleteNarasi(Halaman $halaman, Request $request)
    {
        if ($halaman->nomor_halaman === 1) {
            return back()->withErrors(['delete' => 'Halaman cover tidak memiliki audio narasi untuk dihapus.']);
        }

        try {
            $type = $request->input('narasi_type');

            $fieldMap = [
                'indo'  => ['field' => 'narasi_indo',  'label' => 'Narasi Indonesia'],
                'sunda' => ['field' => 'narasi_sunda', 'label' => 'Narasi Sunda'],
            ];

            if (!isset($fieldMap[$type])) {
                return back()->withErrors(['audio' => 'Tipe audio tidak valid']);
            }

            $field = $fieldMap[$type]['field'];
            $label = $fieldMap[$type]['label'];

            if ($halaman->$field && Storage::disk('s3')->exists($halaman->$field)) {
                Storage::disk('s3')->delete($halaman->$field);
            }
            $halaman->$field = null;
            $halaman->save();

            return back()->with('success', "{$label} berhasil dihapus");
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }

    public function deleteAreaAudio(AreaInteraktif $area, Request $request)
    {

        try {
            $type = $request->input('audio_type');

            $fieldMap = [
                'indo'  => ['field' => 'audio_indo',  'label' => 'Audio Objek Indonesia'],
                'sunda' => ['field' => 'audio_sunda', 'label' => 'Audio Objek Sunda'],
            ];

            if (!isset($fieldMap[$type])) {
                return back()->withErrors(['audio' => 'Tipe audio tidak valid']);
            }

            $field = $fieldMap[$type]['field'];
            $label = $fieldMap[$type]['label'];

            if ($area->$field && Storage::disk('s3')->exists($area->$field)) {
                Storage::disk('s3')->delete($area->$field);
            }
            $area->$field = null;
            $area->save();

            $area->halaman->buku->syncStorageStructure();

            return back()->with('success', "{$label} berhasil dihapus");
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }
}
