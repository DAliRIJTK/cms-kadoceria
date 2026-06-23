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
        $buku = $area->halaman->buku;

        if ($buku->status_publikasi === 'Terbit') {
            return back()->withErrors([
                'audio' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'
            ]);
        }

        $validated = $request->validate([
            'audio_type' => 'required|in:indo,sunda',
            'audio_file' => 'required|file|mimes:mp3,m4a|max:1024',
        ], [
            'audio_file.max'   => 'Ukuran file audio maksimal 1MB.',
            'audio_file.mimes' => 'Format audio harus MP3 atau M4A.',
        ]);

        try {
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
        $buku = $halaman->buku;

        if ($buku->status_publikasi === 'Terbit') {
            return back()->withErrors([
                'audio' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'
            ]);
        }

        $validated = $request->validate([
            'narasi_type' => 'required|in:indo,sunda',
            'audio_file'  => 'required|file|mimes:mp3,m4a|max:1024',
        ], [
            'audio_file.max'   => 'Ukuran file audio maksimal 1MB.',
            'audio_file.mimes' => 'Format audio harus MP3 atau M4A.',
        ]);

        try {
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
        $buku = $halaman->buku;

        if ($buku->status_publikasi === 'Terbit') {
            return back()->withErrors([
                'delete' => 'Buku telah dipublikasikan. Silakan ubah status buku menjadi Draft terlebih dahulu untuk melakukan penyuntingan.'
            ]);
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
}
