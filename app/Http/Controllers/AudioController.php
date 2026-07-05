<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Halaman;
use App\Models\AreaInteraktif;

class AudioController extends Controller
{
    public function storeAreaAudio(Request $request, AreaInteraktif $area)
    {

        $validated = $request->validate([
            'audio_type' => 'required|in:indo,sunda',
            'audio_file' => 'required|file|mimes:mp3,m4a,mpga,mp4,x-m4a,wav|extensions:mp3,m4a|min:1|max:1024',
        ], [
            'audio_file.max'   => 'Ukuran file audio maksimal 1MB.',
            'audio_file.min'   => 'File audio tidak boleh kosong.',
            'audio_file.mimes' => 'Format audio harus MP3 atau M4A.',
            'audio_file.extensions' => 'Ekstensi file audio harus .mp3 atau .m4a.',
        ]);

        try {
            $uploadedHash = md5_file($request->file('audio_file')->getRealPath());

            // Check duplicate against opposite language
            if ($validated['audio_type'] === 'indo') {
                if ($area->audio_sunda && Storage::disk('public')->exists($area->audio_sunda)) {
                    $otherHash = md5_file(storage_path('app/public/' . $area->audio_sunda));
                    if ($uploadedHash === $otherHash) {
                        return back()->withErrors([
                            'audio' => 'File audio Indonesia tidak boleh sama dengan file audio Sunda untuk area ini.'
                        ]);
                    }
                }
            } else {
                if ($area->audio_indo && Storage::disk('public')->exists($area->audio_indo)) {
                    $otherHash = md5_file(storage_path('app/public/' . $area->audio_indo));
                    if ($uploadedHash === $otherHash) {
                        return back()->withErrors([
                            'audio' => 'File audio Sunda tidak boleh sama dengan file audio Indonesia untuk area ini.'
                        ]);
                    }
                }
            }

            $field = 'audio_' . $validated['audio_type'];

            if ($area->$field && Storage::disk('public')->exists($area->$field)) {
                Storage::disk('public')->delete($area->$field);
            }

            $path       = $request->file('audio_file')->store('buku/audio', 'public');
            $area->$field = $path;
            $area->save();

            $area->halaman->buku->syncStorageStructure();

            $lang = $validated['audio_type'] === 'indo' ? 'Indonesia' : 'Sunda';
            return back()->with('success', "Audio {$lang} area berhasil diunggah");
        } catch (\Exception $e) {
            return back()->withErrors(['audio' => 'Gagal menyimpan audio: ' . $e->getMessage()]);
        }
    }

    public function storeNarasi(Request $request, Halaman $halaman)
    {

        $validated = $request->validate([
            'narasi_type' => 'required|in:indo,sunda',
            'audio_file'  => 'required|file|mimes:mp3,m4a,mpga,mp4,x-m4a,wav|extensions:mp3,m4a|min:1|max:1024',
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
                if ($halaman->narasi_sunda && Storage::disk('public')->exists($halaman->narasi_sunda)) {
                    $otherHash = md5_file(storage_path('app/public/' . $halaman->narasi_sunda));
                    if ($uploadedHash === $otherHash) {
                        return back()->withErrors([
                            'audio' => 'File audio narasi Indonesia tidak boleh sama dengan file audio narasi Sunda untuk halaman ini.'
                        ]);
                    }
                }
            } else {
                if ($halaman->narasi_indo && Storage::disk('public')->exists($halaman->narasi_indo)) {
                    $otherHash = md5_file(storage_path('app/public/' . $halaman->narasi_indo));
                    if ($uploadedHash === $otherHash) {
                        return back()->withErrors([
                            'audio' => 'File audio narasi Sunda tidak boleh sama dengan file audio narasi Indonesia untuk halaman ini.'
                        ]);
                    }
                }
            }

            $path = $request->file('audio_file')->store('buku/narasi', 'public');

            switch ($validated['narasi_type']) {
                case 'indo':
                    if ($halaman->narasi_indo && Storage::disk('public')->exists($halaman->narasi_indo)) {
                        Storage::disk('public')->delete($halaman->narasi_indo);
                    }
                    $halaman->narasi_indo = $path;
                    $message = 'Narasi Indonesia berhasil diperbarui';
                    break;

                case 'sunda':
                    if ($halaman->narasi_sunda && Storage::disk('public')->exists($halaman->narasi_sunda)) {
                        Storage::disk('public')->delete($halaman->narasi_sunda);
                    }
                    $halaman->narasi_sunda = $path;
                    $message = 'Narasi Sunda berhasil diperbarui';
                    break;
            }

            $halaman->save();
            $halaman->buku->syncStorageStructure();
            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['audio' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
    }

    public function deleteNarasi(Halaman $halaman, Request $request)
    {

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

            if ($halaman->$field && Storage::disk('public')->exists($halaman->$field)) {
                Storage::disk('public')->delete($halaman->$field);
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

            if ($area->$field && Storage::disk('public')->exists($area->$field)) {
                Storage::disk('public')->delete($area->$field);
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
