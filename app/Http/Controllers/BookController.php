<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::orderBy('created_at', 'asc')->get();
        return view('books.index', compact('books'));
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(Request $request)
    {
        // 1. VALIDASI
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'pdf_file' => 'required|file|mimes:pdf|max:204800',
        ]);

        DB::beginTransaction();

        try {
            // 2. SIMPAN PDF
            $pdfPath = $request->file('pdf_file')->store('books/pdf', 'public');

            // 3. CREATE BOOK
            $book = Book::create([
                'title' => $validated['title'],
                'author' => $validated['author'] ?? null,
                'publisher' => $validated['publisher'] ?? null,
                'description' => $validated['description'] ?? null,
                'pdf_file' => $pdfPath,
                'cover_image' => null,
                'status' => 'draft',
            ]);

            $fullPdfPath = storage_path('app/public/' . $pdfPath);

            // 4. INIT IMAGICK
            $imagick = new \Imagick();
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256);
            $imagick->setResolution(120, 120);
            $imagick->readImage($fullPdfPath);

            foreach ($imagick as $index => $page) {

                $page->setImageFormat('webp');
                $page->setImageCompressionQuality(80);

                $fileName = 'books/pages/' . uniqid() . '_page_' . ($index + 1) . '.webp';
                $fullPath = storage_path('app/public/' . $fileName);

                $directory = dirname($fullPath);
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }

                $result = $page->writeImage($fullPath);

                if (!$result || !file_exists($fullPath)) {
                    throw new \Exception('Gagal menyimpan gambar halaman ke-' . ($index + 1));
                }

                Page::create([
                    'book_id' => $book->id,
                    'page_number' => $index + 1,
                    'image_url' => $fileName,
                ]);

                if ($index === 0) {
                    $book->cover_image = $fileName;
                    $book->save();
                }
            }

            $imagick->clear();
            $imagick->destroy();

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            if (isset($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }

            return back()->withErrors([
                'error' => 'Gagal memproses PDF: ' . $e->getMessage()
            ]);
        }

        return redirect('/books')->with('success', 'Buku berhasil ditambahkan & diproses!');
    }

    public function show(Book $book)
    {
        $book->load('pages');

        return view('books.show', compact('book'));
    }

    public function edit(Book $book)
    {
        return view('books.edit', compact('book'));
    }

    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $book->update($validated);

        return back()->with('success', 'Informasi buku berhasil diperbarui');
    }

    public function updateStatus(Book $book, Request $request)
    {
        $request->validate([
            'status' => 'required|in:draft,published'
        ]);

        $book->update([
            'status' => $request->status
        ]);

        return back()->with('success', 'Status diperbarui');
    }

    public function search(Request $request)
    {
        $query = Book::query();

        if ($request->keyword) {
            $query->where('title', 'ILIKE', '%' . $request->keyword . '%')
                ->orWhere('author', 'ILIKE', '%' . $request->keyword . '%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $books = $query->orderBy('created_at', 'asc')->get();

        return view('books.index', compact('books'));
    }
}