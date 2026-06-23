<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Page;
use App\Models\Audio;

class AudioController extends Controller
{
    public function management(Page $page)
    {
        $page->load(['audios']);
        $audioTypes = ['narration', 'backsound', 'object'];
        return view('pages.audio', compact('page', 'audioTypes'));
    }

    public function store(Request $request, Page $page)
    {
        // Reload page dengan relasi book
        $page = $page->load('book');
        
        $validated = $request->validate([
            'type' => 'required|in:narration,narration_sunda,backsound,narration_object,narration_sunda_object',
            'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a|max:10240',
            'box_id' => 'nullable|exists:bounding_boxes,id',
        ], [
            'audio_file.required' => 'File audio harus diunggah',
            'audio_file.mimes' => 'Format audio harus MP3, WAV, OGG, atau M4A',
            'audio_file.max' => 'Ukuran file audio maksimal 10MB',
            'type.required' => 'Jenis audio harus dipilih',
        ]);

        // Validasi: untuk narration & narration_sunda halaman level, tidak boleh ada box_id
        if (in_array($validated['type'], ['narration', 'narration_sunda', 'backsound'])) {
            // Cek duplikat audio halaman level
            $existingAudio = $page->audios()
                ->where('type', $validated['type'])
                ->whereNull('bounding_box_id')
                ->first();
            
            if ($existingAudio) {
                $typeLabels = [
                    'narration' => 'Narasi Indonesia',
                    'narration_sunda' => 'Narasi Sunda',
                    'backsound' => 'Backsound'
                ];
                return back()->withErrors([
                    'audio' => $typeLabels[$validated['type']] . ' halaman sudah ada. Hapus yang lama terlebih dahulu.'
                ]);
            }
        }

        // Validasi: untuk audio objek, harus ada box_id
        if (in_array($validated['type'], ['narration_object', 'narration_sunda_object'])) {
            if (!$validated['box_id']) {
                return back()->withErrors([
                    'audio' => 'Box ID harus ada untuk audio objek'
                ]);
            }

            // Cek duplikat audio objek untuk bounding box
            $existingAudio = $page->audios()
                ->where('bounding_box_id', $validated['box_id'])
                ->where('type', $validated['type'])
                ->first();
            
            if ($existingAudio) {
                $typeLabels = [
                    'narration_object' => 'Audio Objek Narasi Indonesia',
                    'narration_sunda_object' => 'Audio Objek Narasi Sunda'
                ];
                return back()->withErrors([
                    'audio' => $typeLabels[$validated['type']] . ' untuk area ini sudah ada. Hapus yang lama terlebih dahulu.'
                ]);
            }
        }

        try {
            // Debug: Log book info
            $book = $page->book;
            \Log::info('Audio upload - Book:', ['id' => $book?->id, 'title' => $book?->title]);
            
            // Generate book slug from title
            if (!$book || !$book->title) {
                return back()->withErrors([
                    'audio' => 'Book tidak ditemukan atau tidak memiliki title'
                ]);
            }
            
            $bookSlug = strtolower(
                str_replace(
                    [' ', ',', '.', '(', ')', '/'],
                    '_',
                    trim($book->title)
                )
            );
            $bookSlug = preg_replace('/_+/', '_', $bookSlug);
            $bookSlug = trim($bookSlug, '_');

            // Map audio type ke folder name
            $typeFolderMap = [
                'narration' => 'audio_narasi_indo',
                'narration_sunda' => 'audio_narasi_sunda',
                'backsound' => 'audio_backsound',
                'narration_object' => 'audio_narasi_indo',
                'narration_sunda_object' => 'audio_narasi_sunda'
            ];
            
            $audioFolder = $typeFolderMap[$validated['type']] ?? 'audio_other';
            $storagePath = "audios/{$bookSlug}/{$audioFolder}";
            
            \Log::info('Audio storage path:', ['path' => $storagePath, 'book_slug' => $bookSlug, 'type' => $validated['type']]);
            
            // Get original filename dan sanitize
            $originalName = $request->file('audio_file')->getClientOriginalName();
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            
            // Sanitize filename: remove special chars, keep alphanumeric, underscore, dash
            $sanitizedName = pathinfo($originalName, PATHINFO_FILENAME);
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sanitizedName);
            $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
            $filename = trim($sanitizedName, '_') . '.' . $extension;
            
            // Store dengan nama file original (tapi sudah sanitize)
            $path = $request->file('audio_file')->storeAs($storagePath, $filename, 'public');
            
            \Log::info('Audio stored:', ['file_path' => $path, 'original_name' => $originalName, 'sanitized_name' => $filename]);

            // Auto-generate label
            $typeLabels = [
                'narration' => 'page' . $page->page_number . '_narasi_indonesia',
                'narration_sunda' => 'page' . $page->page_number . '_narasi_sunda',
                'backsound' => 'page' . $page->page_number . '_backsound',
                'narration_object' => 'page' . $page->page_number . '_obj_narasi_indo',
                'narration_sunda_object' => 'page' . $page->page_number . '_obj_narasi_sunda'
            ];
            
            $label = $typeLabels[$validated['type']] ?? 'audio_' . time();

            Audio::create([
                'page_id' => $page->id,
                'bounding_box_id' => $validated['box_id'] ?? null,
                'type' => $validated['type'],
                'label' => $label,
                'file_url' => $path,
            ]);

            $successMessages = [
                'narration' => 'Narasi Indonesia halaman berhasil ditambahkan',
                'narration_sunda' => 'Narasi Sunda halaman berhasil ditambahkan',
                'backsound' => 'Backsound halaman berhasil ditambahkan',
                'narration_object' => 'Audio Objek Narasi Indonesia berhasil ditambahkan',
                'narration_sunda_object' => 'Audio Objek Narasi Sunda berhasil ditambahkan'
            ];
            
            return back()->with('success', $successMessages[$validated['type']] ?? 'Audio berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->withErrors([
                'audio' => 'Gagal menyimpan audio: ' . $e->getMessage()
            ]);
        }
    }

    public function delete(Audio $audio)
    {
        try {
            if ($audio->file_url && Storage::disk('public')->exists($audio->file_url)) {
                Storage::disk('public')->delete($audio->file_url);
            }

            $audioType = ucfirst($audio->type);
            $audio->delete();

            return back()->with('success', "$audioType berhasil dihapus");
        } catch (\Exception $e) {
            return back()->withErrors([
                'delete' => 'Gagal menghapus audio: ' . $e->getMessage()
            ]);
        }
    }

    public function storeNarration(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:pages,id',
            'audio' => 'required|file|mimes:mp3,wav,ogg',
        ]);

        $page = Page::find($validated['page_id']);
        $book = $page->book;
        $bookSlug = strtolower(
            str_replace(
                [' ', ',', '.', '(', ')', '/'],
                '_',
                trim($book->title)
            )
        );
        $bookSlug = preg_replace('/_+/', '_', $bookSlug);
        $bookSlug = trim($bookSlug, '_');
        
        // Get original filename dan sanitize
        $originalName = $request->file('audio')->getClientOriginalName();
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $sanitizedName = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sanitizedName);
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
        $filename = trim($sanitizedName, '_') . '.' . $extension;
        
        $path = $request->file('audio')->storeAs("audios/{$bookSlug}/audio_narasi_indo", $filename, 'public');

        Audio::create([
            'page_id' => $validated['page_id'],
            'type' => 'narration',
            'file_url' => $path,
        ]);

        return back()->with('success', 'Narration ditambahkan');
    }

    public function storeBacksound(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:pages,id',
            'audio' => 'required|file|mimes:mp3,wav,ogg',
        ]);

        $page = Page::find($validated['page_id']);
        $book = $page->book;
        $bookSlug = strtolower(
            str_replace(
                [' ', ',', '.', '(', ')', '/'],
                '_',
                trim($book->title)
            )
        );
        $bookSlug = preg_replace('/_+/', '_', $bookSlug);
        $bookSlug = trim($bookSlug, '_');
        
        // Get original filename dan sanitize
        $originalName = $request->file('audio')->getClientOriginalName();
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $sanitizedName = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sanitizedName);
        $sanitizedName = preg_replace('/_+/', '_', $sanitizedName);
        $filename = trim($sanitizedName, '_') . '.' . $extension;
        
        $path = $request->file('audio')->storeAs("audios/{$bookSlug}/audio_backsound", $filename, 'public');

        Audio::create([
            'page_id' => $validated['page_id'],
            'type' => 'backsound',
            'file_url' => $path,
        ]);

        return back()->with('success', 'Backsound ditambahkan');
    }

    public function update(Request $request, Audio $audio)
    {
        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('audios', 'public');
            $audio->update(['file_url' => $path]);
        }

        return back()->with('success', 'Audio diupdate');
    }
}
