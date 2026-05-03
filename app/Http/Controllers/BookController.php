<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::query();

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'title_asc':
                    $query->orderBy('title', 'asc');
                    break;
                case 'title_desc':
                    $query->orderBy('title', 'desc');
                    break;
                case 'date_newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'date_oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'status_draft':
                    $query->where('status', 'draft')->orderBy('created_at', 'desc');
                    break;
                case 'status_published':
                    $query->where('status', 'published')->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'asc');
            }
        } else {
            $query->orderBy('created_at', 'asc');
        }

        $books = $query->paginate(8);
        return view('books.index', compact('books'));
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(Request $request)
    {
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

        $newStatus = $request->status;

        if ($newStatus === 'published') {
            $validationErrors = [];

            if (!$book->title || empty($book->title)) {
                $validationErrors[] = 'Judul buku harus diisi';
            }

            if ($book->pages()->count() === 0) {
                $validationErrors[] = 'Buku harus memiliki minimal 1 halaman';
            } else {
                $pagesWithoutNarration = $book->pages()
                    ->whereDoesntHave('audios', function ($q) {
                        $q->where('type', 'narration');
                    })
                    ->get();

                if ($pagesWithoutNarration->count() > 0) {
                    $pageNumbers = $pagesWithoutNarration->pluck('page_number')->implode(', ');
                    $validationErrors[] = "Audio narasi wajib ada di halaman: $pageNumbers";
                }
            }

            if (!empty($validationErrors)) {
                return back()->withErrors([
                    'publication' => 'Buku belum dapat dipublikasikan. ' . implode(' | ', $validationErrors)
                ]);
            }
        }

        if ($book->status === 'published' && $newStatus === 'draft') {
            $confirmed = $request->has('confirm_unpublish') && $request->confirm_unpublish === 'yes';
            if (!$confirmed) {
                return back()->withErrors([
                    'publication' => 'Konfirmasi pembatalan publikasi: Apakah Anda yakin ingin menarik buku ini dari peredaran?'
                ]);
            }
        }

        $book->update([
            'status' => $newStatus
        ]);

        $statusLabel = $newStatus === 'published' ? 'dipublikasikan' : 'disimpan sebagai draft';
        return back()->with('success', "Buku berhasil {$statusLabel}");
    }

    public function destroy(Book $book)
    {
        try {
            foreach ($book->pages as $page) {
                foreach ($page->audios as $audio) {
                    if ($audio->file_url && Storage::disk('public')->exists($audio->file_url)) {
                        Storage::disk('public')->delete($audio->file_url);
                    }
                }

                foreach ($page->boundingBoxes as $box) {
                    if ($box->audios) {
                        foreach ($box->audios as $audio) {
                            if ($audio->file_url && Storage::disk('public')->exists($audio->file_url)) {
                                Storage::disk('public')->delete($audio->file_url);
                            }
                        }
                    }
                }

                if ($page->image_url && Storage::disk('public')->exists($page->image_url)) {
                    Storage::disk('public')->delete($page->image_url);
                }
            }

            if ($book->cover_image && Storage::disk('public')->exists($book->cover_image)) {
                Storage::disk('public')->delete($book->cover_image);
            }

            if ($book->pdf_file && Storage::disk('public')->exists($book->pdf_file)) {
                Storage::disk('public')->delete($book->pdf_file);
            }

            $book->delete();

            return redirect('/books')->with('success', 'Buku beserta semua aset multimedia berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors([
                'delete' => 'Gagal menghapus buku: ' . $e->getMessage()
            ]);
        }
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

    public function apiBooks()
    {
        $books = Book::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'publisher' => $book->publisher,
                    'description' => $book->description,
                    'cover_image' => $book->cover_image ? asset('storage/' . $book->cover_image) : null,
                    'pages_count' => $book->pages()->count(),
                    'created_at' => $book->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $books,
            'total' => $books->count(),
        ]);
    }

    public function apiBookDetail($id)
    {
        $book = Book::with([
            'pages' => function ($query) {
                $query->orderBy('page_number', 'asc');
            },
            'pages.audios',
            'pages.boundingBoxes.audios'
        ])->find($id);

        if (!$book || $book->status !== 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Buku tidak ditemukan atau belum dipublikasikan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'publisher' => $book->publisher,
                'description' => $book->description,
                'cover_image' => $book->cover_image ? asset('storage/' . $book->cover_image) : null,
                'pages' => $book->pages->map(function ($page) {
                    return [
                        'id' => $page->id,
                        'page_number' => $page->page_number,
                        'image_url' => asset('storage/' . $page->image_url),
                        'narration' => $page->audios->where('type', 'narration')->map(function ($audio) {
                            return [
                                'id' => $audio->id,
                                'label' => $audio->label,
                                'file_url' => asset('storage/' . $audio->file_url),
                            ];
                        })->first(),
                        'backsound' => $page->audios->where('type', 'backsound')->map(function ($audio) {
                            return [
                                'id' => $audio->id,
                                'label' => $audio->label,
                                'file_url' => asset('storage/' . $audio->file_url),
                            ];
                        })->first(),
                        'interactive_areas' => $page->boundingBoxes->map(function ($box) {
                            return [
                                'id' => $box->id,
                                'label' => $box->label,
                                'x' => $box->x,
                                'y' => $box->y,
                                'width' => $box->width,
                                'height' => $box->height,
                                'audio' => $box->audios->first() ? [
                                    'id' => $box->audios->first()->id,
                                    'label' => $box->audios->first()->label,
                                    'file_url' => asset('storage/' . $box->audios->first()->file_url),
                                ] : null,
                            ];
                        }),
                    ];
                }),
            ]
        ]);
    }

    private function validateBookForPublication(Book $book): array
    {
        $errors = [];

        if (!$book->title || empty($book->title)) {
            $errors[] = 'Judul buku harus diisi';
        }

        if ($book->pages()->count() === 0) {
            $errors[] = 'Buku harus memiliki minimal 1 halaman';
        } else {
            $pagesWithoutNarration = $book->pages()
                ->whereDoesntHave('audios', function ($q) {
                    $q->where('type', 'narration');
                })
                ->get();

            if ($pagesWithoutNarration->count() > 0) {
                $pageNumbers = $pagesWithoutNarration->pluck('page_number')->implode(', ');
                $errors[] = "Audio narasi wajib ada di halaman: $pageNumbers";
            }
        }

        return $errors;
    }
}