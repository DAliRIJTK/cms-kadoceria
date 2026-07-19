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
            $file = $request->file('audio_file');
            $uploadedHash = md5_file($file->getRealPath());
            
            // Inisialisasi S3 Client untuk mengambil metadata tanpa download
            $s3Client = Storage::disk('s3')->getClient();
            $bucket = config('filesystems.disks.s3.bucket');

            // Cek duplikasi menggunakan ETag (S3 Checksum/MD5)
            if ($validated['audio_type'] === 'indo') {
                if ($area->audio_sunda && Storage::disk('s3')->exists($area->audio_sunda)) {
                    $meta = $s3Client->headObject(['Bucket' => $bucket, 'Key' => $area->audio_sunda]);
                    $otherHash = trim($meta['ETag'], '"');
                    
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio Indonesia tidak boleh sama dengan file audio Sunda untuk area ini.';
                        if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $errMsg], 422);
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            } else {
                if ($area->audio_indo && Storage::disk('s3')->exists($area->audio_indo)) {
                    $meta = $s3Client->headObject(['Bucket' => $bucket, 'Key' => $area->audio_indo]);
                    $otherHash = trim($meta['ETag'], '"');
                    
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio Sunda tidak boleh sama dengan file audio Indonesia untuk area ini.';
                        if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $errMsg], 422);
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            }

            $buku = $area->halaman->buku;

            $field = 'audio_' . $validated['audio_type'];

            if ($area->$field && Storage::disk('s3')->exists($area->$field)) {
                Storage::disk('s3')->delete($area->$field);
            }

            $file = $request->file('audio_file');
            $ext = $file->getClientOriginalExtension() ?: 'mp3';

            $safeLabel = $buku->slugify($area->label ?? 'objek');
            $langSuffix = $validated['audio_type'] === 'indo' ? 'indonesia' : 'sunda';
            $finalPath = $buku->buildPageAssetPath($area->halaman, 'audio objek', $ext, $safeLabel . '_' . $langSuffix);
            
            Storage::disk('s3')->putFileAs(
                dirname($finalPath), 
                $file, 
                basename($finalPath), 
                [
                    'visibility' => 'public',
                    'ContentType' => $file->getMimeType()
                ]
            );

            $area->$field = $finalPath;
            $area->save();

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
            $file = $request->file('audio_file');
            $uploadedHash = md5_file($file->getRealPath());
            
            // Inisialisasi S3 Client
            $s3Client = Storage::disk('s3')->getClient();
            $bucket = config('filesystems.disks.s3.bucket');

            // Cek duplikasi menggunakan ETag
            if ($validated['narasi_type'] === 'indo') {
                if ($halaman->narasi_sunda && Storage::disk('s3')->exists($halaman->narasi_sunda)) {
                    $meta = $s3Client->headObject(['Bucket' => $bucket, 'Key' => $halaman->narasi_sunda]);
                    $otherHash = trim($meta['ETag'], '"');
                    
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio narasi Indonesia tidak boleh sama dengan file audio narasi Sunda untuk halaman ini.';
                        if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $errMsg], 422);
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            } else {
                if ($halaman->narasi_indo && Storage::disk('s3')->exists($halaman->narasi_indo)) {
                    $meta = $s3Client->headObject(['Bucket' => $bucket, 'Key' => $halaman->narasi_indo]);
                    $otherHash = trim($meta['ETag'], '"');
                    
                    if ($uploadedHash === $otherHash) {
                        $errMsg = 'File audio narasi Sunda tidak boleh sama dengan file audio narasi Indonesia untuk halaman ini.';
                        if ($request->wantsJson()) return response()->json(['success' => false, 'message' => $errMsg], 422);
                        return back()->withErrors(['audio' => $errMsg]);
                    }
                }
            }

            $buku = $halaman->buku;
            $field = 'narasi_' . $validated['narasi_type'];

            // Hapus file lama jika ada
            if ($halaman->$field && Storage::disk('s3')->exists($halaman->$field)) {
                Storage::disk('s3')->delete($halaman->$field);
            }

            $ext = $file->getClientOriginalExtension() ?: 'mp3';
            $dirName = $validated['narasi_type'] === 'indo' ? 'audio narasi indonesia' : 'audio narasi sunda';
            $finalPath = $buku->buildPageAssetPath($halaman, $dirName, $ext);

            Storage::disk('s3')->putFileAs(
                dirname($finalPath), 
                $file, 
                basename($finalPath), 
                [
                    'visibility' => 'public',
                    'ContentType' => $file->getMimeType()
                ]
            );

            $halaman->$field = $finalPath;
            $halaman->save();
            $message = $validated['narasi_type'] === 'indo' 
                ? 'Narasi Indonesia berhasil diperbarui' 
                : 'Narasi Sunda berhasil diperbarui';

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

            return back()->with('success', "{$label} berhasil dihapus");
        } catch (\Exception $e) {
            return back()->withErrors(['delete' => 'Gagal menghapus: ' . $e->getMessage()]);
        }
    }
}
