<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Page;
use App\Models\BoundingBox;
use App\Models\Audio;
use App\Models\Book;

class PageController extends Controller
{
    public function management(Request $request)
    {
        $query = Page::with('book')->orderBy('book_id', 'asc')->orderBy('page_number', 'asc');

        // Filter by keyword (search in book title)
        if ($request->filled('keyword')) {
            $query->whereHas('book', function ($q) {
                $q->where('title', 'like', '%' . request('keyword') . '%');
            });
        }

        // Filter by book
        if ($request->filled('book_id') && $request->book_id !== '') {
            $query->where('book_id', $request->book_id);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== '') {
            $query->whereHas('book', function ($q) {
                $q->where('status', request('status'));
            });
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'page_asc':
                    $query->orderBy('page_number', 'asc');
                    break;
                case 'page_desc':
                    $query->orderBy('page_number', 'desc');
                    break;
                case 'date_newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'date_oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'book_asc':
                    $query->with('book')->orderBy('book_id', 'asc');
                    break;
                default:
                    $query->orderBy('book_id', 'asc')->orderBy('page_number', 'asc');
            }
        } else {
            $query->orderBy('book_id', 'asc')->orderBy('page_number', 'asc');
        }

        $pages = $query->paginate(8);
        $allBooks = Book::all();

        return view('pages.management', compact('pages', 'allBooks'));
    }

    public function audioManagementList(Request $request)
    {
        $query = Audio::with('page.book')->orderBy('created_at', 'desc');

        // Filter by keyword (search in page/book title or audio label)
        if ($request->filled('keyword')) {
            $query->where(function ($q) {
                $q->where('label', 'like', '%' . request('keyword') . '%')
                    ->orWhereHas('page', function ($q) {
                        $q->whereHas('book', function ($q) {
                            $q->where('title', 'like', '%' . request('keyword') . '%');
                        });
                    });
            });
        }

        // Filter by audio type
        if ($request->filled('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        // Filter by book
        if ($request->filled('book_id') && $request->book_id !== '') {
            $query->whereHas('page', function ($q) {
                $q->where('book_id', $request->book_id);
            });
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'label_asc':
                    $query->orderBy('label', 'asc');
                    break;
                case 'label_desc':
                    $query->orderBy('label', 'desc');
                    break;
                case 'date_newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'date_oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'type_asc':
                    $query->orderBy('type', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        }

        $audios = $query->paginate(8);
        $allBooks = Book::all();

        return view('pages.audio-management', compact('audios', 'allBooks'));
    }

    public function edit(Page $page)
    {
        $page->load(['boundingBoxes.audios', 'audios']);
        return view('pages.edit', compact('page'));
    }

    public function show(Page $page)
    {
        $page->load(['book', 'boundingBoxes.audios']);
        return view('pages.show', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        try {
        // Handle page reordering
        if ($request->has('page_number') && !$request->has('annotations')) {
            $validated = $request->validate([
                'page_number' => 'nullable|integer|min:1'
            ]);

            if ($request->has('page_number')) {
                $oldPageNumber = $page->page_number;
                $newPageNumber = $validated['page_number'];

                if ($oldPageNumber !== $newPageNumber) {
                    $maxPageNumber = $page->book->pages()->max('page_number');

                    if ($newPageNumber > $maxPageNumber) {
                        $newPageNumber = $maxPageNumber;
                    }

                    if ($oldPageNumber < $newPageNumber) {
                        $page->book->pages()
                            ->whereBetween('page_number', [$oldPageNumber + 1, $newPageNumber])
                            ->decrement('page_number');
                    } elseif ($oldPageNumber > $newPageNumber) {
                        $page->book->pages()
                            ->whereBetween('page_number', [$newPageNumber, $oldPageNumber - 1])
                            ->increment('page_number');
                    }

                    $page->update(['page_number' => $newPageNumber]);
                }
            }

            return back()->with('success', 'Halaman berhasil diperbarui');
        }

        // Handle annotations and audio bulk save
        if ($request->has('annotations') || $request->has('audio')) {
            try {
                $annotationsCount = 0;
                $audioCount = 0;
                
                $annotations = $request->input('annotations', []);
                if (is_array($annotations) && !empty($annotations)) {
                    foreach ($annotations as $annData) {
                        if (is_array($annData) && isset($annData['label'])) {
                            BoundingBox::create([
                                'page_id' => $page->id,
                                'label' => (string)($annData['label'] ?? ''),
                                'x' => (float)($annData['x'] ?? 0),
                                'y' => (float)($annData['y'] ?? 0),
                                'width' => (float)($annData['width'] ?? 0),
                                'height' => (float)($annData['height'] ?? 0),
                            ]);
                            $annotationsCount++;
                        }
                    }
                }

                $audioItems = $request->input('audio', []);
                if (is_array($audioItems) && !empty($audioItems)) {
                    foreach ($audioItems as $index => $audioData) {
                        try {
                            $fileKey = "audio.{$index}.file";
                            if ($request->hasFile($fileKey)) {
                                $file = $request->file($fileKey);
                                if ($file && $file->isValid()) {
                                    $path = $file->store('audios', 'public');
                                    
                                    Audio::create([
                                        'page_id' => $page->id,
                                        'type' => 'object',
                                        'label' => (string)($audioData['label'] ?? 'Audio Objek'),
                                        'file_url' => $path,
                                    ]);
                                    $audioCount++;
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Failed to save audio: " . $e->getMessage());
                        }
                    }
                }

                $message = '';
                if ($annotationsCount > 0) {
                    $message .= "$annotationsCount anotasi ";
                }
                if ($audioCount > 0) {
                    $message .= ($annotationsCount > 0 ? "dan " : "") . "$audioCount audio ";
                }
                $message .= "berhasil disimpan";

                return response()->json([
                    'success' => true,
                    'message' => $message ?: 'Tidak ada data untuk disimpan'
                ]);
            } catch (\Throwable $e) {
                \Log::error('PageController update error: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan: ' . $e->getMessage()
                ], 500);
            }
        }

        return back()->with('success', 'Halaman berhasil diperbarui');
        } catch (\Throwable $e) {
            // If request expects JSON, return JSON error
            if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
                \Log::error('PageController update error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    public function audioManagement(Page $page)
    {
        $page->load(['audios']);
        $audioTypes = ['narration', 'backsound', 'object'];
        return view('pages.audio', compact('page', 'audioTypes'));
    }

    public function storeAudio(Request $request, Page $page)
    {
        $validated = $request->validate([
            'type' => 'required|in:narration,backsound,object',
            'label' => 'required|string|max:255',
            'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a|max:10240',
            'bounding_box_id' => 'nullable|exists:bounding_boxes,id',
        ], [
            'audio_file.required' => 'File audio harus diunggah',
            'audio_file.mimes' => 'Format audio harus MP3, WAV, OGG, atau M4A',
            'audio_file.max' => 'Ukuran file audio maksimal 10MB',
            'type.required' => 'Jenis audio harus dipilih',
            'label.required' => 'Label audio harus diisi',
            'bounding_box_id.exists' => 'Anotasi yang dipilih tidak valid',
        ]);

        if ($validated['type'] === 'narration') {
            $existingNarration = $page->audios()->where('type', 'narration')->first();
            if ($existingNarration) {
                return back()->withErrors([
                    'audio' => 'Halaman ini sudah memiliki audio narasi. Hapus audio yang lama sebelum menambah yang baru.'
                ]);
            }
        }

        if ($validated['type'] === 'backsound') {
            $hasNarration = $page->audios()->where('type', 'narration')->exists();
            if (!$hasNarration) {
                return back()->withErrors([
                    'audio' => 'Audio narasi harus diisi terlebih dahulu sebelum menambah audio backsound'
                ]);
            }
        }

        if ($validated['type'] === 'object') {
            if (!$validated['bounding_box_id']) {
                return back()->withErrors([
                    'audio' => 'Anda harus memilih anotasi terlebih dahulu untuk audio objek'
                ]);
            }

            $existingObjectAudio = Audio::where('bounding_box_id', $validated['bounding_box_id'])->first();
            if ($existingObjectAudio) {
                return back()->withErrors([
                    'audio' => 'Anotasi ini sudah memiliki audio. Hapus audio yang lama sebelum menambah yang baru.'
                ]);
            }
        }

        try {
            $path = $request->file('audio_file')->store('audios', 'public');

            Audio::create([
                'page_id' => $page->id,
                'bounding_box_id' => $validated['bounding_box_id'] ?? null,
                'type' => $validated['type'],
                'label' => $validated['label'],
                'file_url' => $path,
            ]);

            return back()->with('success', 'Audio berhasil ditambahkan');
        } catch (\Exception $e) {
            return back()->withErrors([
                'audio' => 'Gagal menyimpan audio: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteAudio(Audio $audio)
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'image' => 'required|image'
        ]);

        $path = $request->file('image')->store('books/pages', 'public');

        $lastPage = Page::where('book_id', $validated['book_id'])
            ->max('page_number');

        Page::create([
            'book_id' => $validated['book_id'],
            'page_number' => $lastPage + 1,
            'image_url' => $path,
        ]);

        return back()->with('success', 'Halaman berhasil ditambahkan');
    }

    public function destroy(Page $page)
    {
        try {
            $book = $page->book;
            $deletedPageNumber = $page->page_number;
            $imageUrl = $page->image_url;

            // Delete audio files from storage
            $audios = $page->audios;
            foreach ($audios as $audio) {
                if ($audio->file_url && Storage::disk('public')->exists($audio->file_url)) {
                    Storage::disk('public')->delete($audio->file_url);
                }
            }
            $page->audios()->delete();

            // Delete bounding boxes and their related audio files
            $boundingBoxes = $page->boundingBoxes;
            foreach ($boundingBoxes as $box) {
                $boxAudios = $box->audios;
                foreach ($boxAudios as $audio) {
                    if ($audio->file_url && Storage::disk('public')->exists($audio->file_url)) {
                        Storage::disk('public')->delete($audio->file_url);
                    }
                }
            }
            $page->boundingBoxes()->delete();

            if ($imageUrl && Storage::disk('public')->exists($imageUrl)) {
                Storage::disk('public')->delete($imageUrl);
            }

            $page->delete();

            $book->pages()
                ->where('page_number', '>', $deletedPageNumber)
                ->decrement('page_number');

            return back()->with('success', 'Halaman berhasil dihapus dan urutan halaman otomatis penyesuaian');
        } catch (\Exception $e) {
            return back()->withErrors([
                'delete' => 'Gagal menghapus halaman: ' . $e->getMessage()
            ]);
        }
    }

    public function reorder(Request $request)
    {
        foreach ($request->pages as $index => $id) {
            Page::where('id', $id)->update([
                'page_number' => $index + 1
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function storeBoundingBox(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:pages,id',
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg',
        ]);

        $box = BoundingBox::create([
            'page_id' => $validated['page_id'],
            'x' => $validated['x'],
            'y' => $validated['y'],
            'width' => $validated['width'],
            'height' => $validated['height'],
        ]);

        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('audios', 'public');

            Audio::create([
                'bounding_box_id' => $box->id,
                'page_id' => $validated['page_id'],
                'type' => 'object',
                'file_url' => $path,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updateBoundingBox(Request $request, BoundingBox $box)
    {
        $validated = $request->validate([
            'x' => 'required|numeric',
            'y' => 'required|numeric',
            'width' => 'required|numeric|min:1',
            'height' => 'required|numeric|min:1',
        ]);

        $box->update($validated);

        return response()->json(['success' => true]);
    }

    public function deleteBoundingBox(BoundingBox $box)
    {
        try {
            $audios = $box->audios;
            foreach ($audios as $audio) {
                if ($audio->file_url && Storage::disk('public')->exists($audio->file_url)) {
                    Storage::disk('public')->delete($audio->file_url);
                }
                $audio->delete();
            }

            $box->delete();

            return response()->json([
                'success' => true,
                'message' => 'Anotasi dan audio terkait berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus anotasi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeNarration(Request $request)
    {
        $validated = $request->validate([
            'page_id' => 'required|exists:pages,id',
            'audio' => 'required|file|mimes:mp3,wav,ogg',
        ]);

        $path = $request->file('audio')->store('audios', 'public');

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

        $path = $request->file('audio')->store('audios', 'public');

        Audio::create([
            'page_id' => $validated['page_id'],
            'type' => 'backsound',
            'file_url' => $path,
        ]);

        return back()->with('success', 'Backsound ditambahkan');
    }

    public function updateAudio(Request $request, Audio $audio)
    {
        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('audios', 'public');
            $audio->update(['file_url' => $path]);
        }

        return back()->with('success', 'Audio diupdate');
    }
}
