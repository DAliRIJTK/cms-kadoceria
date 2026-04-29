<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        $pages = $query->paginate(15);
        $allBooks = Book::all();

        return view('pages.management', compact('pages', 'allBooks'));
    }

    public function edit(Page $page)
    {
        $page->load(['boundingBoxes.audio', 'audios']);
        return view('pages.edit', compact('page'));
    }

    public function show(Page $page)
    {
        $page->load(['book', 'boundingBoxes.audios']);
        return view('pages.show', compact('page'));
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
        ]);

        $path = $request->file('audio_file')->store('audios', 'public');

        Audio::create([
            'page_id' => $page->id,
            'bounding_box_id' => $validated['bounding_box_id'] ?? null,
            'type' => $validated['type'],
            'label' => $validated['label'],
            'file_url' => $path,
        ]);

        return back()->with('success', 'Audio berhasil ditambahkan');
    }

    public function deleteAudio(Audio $audio)
    {
        $audio->delete();
        return back()->with('success', 'Audio berhasil dihapus');
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
        $page->delete();

        return back()->with('success', 'Halaman dihapus');
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
        $box->delete();

        return response()->json(['success' => true]);
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
