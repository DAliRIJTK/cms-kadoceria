@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Daftar Buku Cerita</h1>
            <p class="text-gray-500 mt-1">Kelola koleksi buku dwibahasa Anda</p>
        </div>
        <a href="/books/create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-md transition-colors duration-200 font-medium flex items-center gap-2">
            <span class="text-xl">+</span> Tambah Buku
        </a>
    </div>

    <form method="GET" action="/books" class="bg-white rounded-lg shadow-sm p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cari Buku</label>
                <input 
                    type="text" 
                    name="keyword"
                    value="{{ request('keyword') }}"
                    placeholder="Ketik judul atau penulis buku..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Urutkan</label>
                <select name="sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">Default</option>
                    <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>Judul (A-Z)</option>
                    <option value="title_desc" {{ request('sort') == 'title_desc' ? 'selected' : '' }}>Judul (Z-A)</option>
                    <option value="date_newest" {{ request('sort') == 'date_newest' ? 'selected' : '' }}>Terbaru</option>
                    <option value="date_oldest" {{ request('sort') == 'date_oldest' ? 'selected' : '' }}>Terlama</option>
                    <option value="status_draft" {{ request('sort') == 'status_draft' ? 'selected' : '' }}>Draft Terlebih Dahulu</option>
                    <option value="status_published" {{ request('sort') == 'status_published' ? 'selected' : '' }}>Published Terlebih Dahulu</option>
                </select>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
                Cari
            </button>
            <a href="/books" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
                Reset
            </a>
        </div>
    </form>
</div>

@if($books->isEmpty())
        <div class="flex flex-col items-center justify-center mt-20 bg-white rounded-lg shadow-sm p-12">
        <div class="text-7xl mb-4 opacity-50">📖</div>
        <p class="text-xl font-semibold text-gray-700 mb-2">Belum ada buku cerita</p>
        <p class="text-gray-500 mb-6">Mulai dengan menambahkan buku cerita pertama Anda</p>
        <a href="/books/create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
            + Buat Buku Baru
        </a>
    </div>
@else

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Menampilkan {{ $books->firstItem() }} hingga {{ $books->lastItem() }} dari {{ $books->total() }} buku
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        @foreach($books as $book)
            <a href="{{ url('/books/' . $book->id) }}" class="group">
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">

                                        <div class="relative overflow-hidden bg-gray-200 h-64">
                        <img 
                            src="{{ $book->cover_image ? asset('storage/' . $book->cover_image) : 'https://via.placeholder.com/400x500?text=No+Cover' }}"
                            alt="{{ $book->title }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        >
                                                <div class="absolute top-3 right-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $book->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($book->status ?? 'draft') }}
                            </span>
                        </div>
                    </div>

                                        <div class="p-4">
                                                <h3 class="font-semibold text-gray-800 line-clamp-2 group-hover:text-blue-600 transition-colors mb-2">
                            {{ $book->title }}
                        </h3>

                                                <div class="space-y-2 text-xs text-gray-500">
                            <p class="flex items-center gap-1">
                                <span>👤</span>
                                {{ $book->author ?? '-' }}
                            </p>
                            <p class="flex items-center gap-1">
                                <span>📅</span>
                                {{ $book->created_at->format('d M Y') }}
                            </p>
                            @if($book->pages_count ?? false)
                                <p class="flex items-center gap-1">
                                    <span>📄</span>
                                    {{ $book->pages_count ?? 0 }} halaman
                                </p>
                            @endif
                        </div>

                                                <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-xs text-blue-600 font-medium group-hover:text-blue-700">
                                Klik untuk membuka →
                            </p>
                        </div>
                    </div>

                </div>
            </a>
        @endforeach
    </div>

    @if($books->hasPages())
        <div class="flex justify-center">
            {{ $books->links('pagination::tailwind') }}
        </div>
    @endif

@endif

@endsection