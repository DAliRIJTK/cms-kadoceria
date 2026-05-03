@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Kelola Halaman</h1>
    <p class="text-gray-500 mt-1">Kelola semua halaman dari seluruh buku cerita dwibahasa</p>
</div>

<form method="GET" action="/pages-management" class="bg-white rounded-lg shadow-sm p-4 mb-8 border border-gray-200">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Cari Halaman</label>
            <input 
                type="text" 
                name="keyword"
                value="{{ request('keyword') }}"
                placeholder="Cari berdasarkan judul buku..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buku</label>
            <select name="book_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                <option value="">Semua Buku</option>
                @foreach($allBooks as $book)
                    <option value="{{ $book->id }}" {{ request('book_id') == $book->id ? 'selected' : '' }}>
                        {{ $book->title }}
                    </option>
                @endforeach
            </select>
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
                <option value="">Default (Buku & Halaman)</option>
                <option value="page_asc" {{ request('sort') == 'page_asc' ? 'selected' : '' }}>Halaman Asc</option>
                <option value="page_desc" {{ request('sort') == 'page_desc' ? 'selected' : '' }}>Halaman Desc</option>
                <option value="date_newest" {{ request('sort') == 'date_newest' ? 'selected' : '' }}>Terbaru</option>
                <option value="date_oldest" {{ request('sort') == 'date_oldest' ? 'selected' : '' }}>Terlama</option>
                <option value="book_asc" {{ request('sort') == 'book_asc' ? 'selected' : '' }}>Buku (A-Z)</option>
            </select>
        </div>
    </div>

    <div class="flex gap-2 mt-4">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
            Cari
        </button>
        <a href="/pages-management" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
            Reset
        </a>
    </div>
</form>

@if($pages->isEmpty())
        <div class="flex flex-col items-center justify-center mt-20 bg-white rounded-lg shadow-sm p-12 border border-gray-200">
        <div class="text-7xl mb-4 opacity-50">📭</div>
        <p class="text-xl font-semibold text-gray-700 mb-2">Tidak ada halaman</p>
        <p class="text-gray-500 mb-6">Mulai dengan membuat buku baru atau menambahkan halaman ke buku yang ada</p>
        <a href="/books/create" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
            + Buat Buku Baru
        </a>
    </div>
@else
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Halaman</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Buku</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Preview</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Anotasi</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Audio</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>

                                <tbody class="divide-y divide-gray-200">
                    @foreach($pages as $page)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-900">Halaman {{ $page->page_number }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $page->id }}</p>
                            </td>

                                                        <td class="px-6 py-4">
                                <a href="{{ url('/books/' . $page->book_id) }}" class="text-blue-600 hover:text-blue-700 hover:underline font-medium">
                                    {{ $page->book->title }}
                                </a>
                                <p class="text-xs text-gray-500">{{ $page->book->author ?? '-' }}</p>
                            </td>

                                                        <td class="px-6 py-4 whitespace-nowrap">
                                <img 
                                    src="{{ asset('storage/' . $page->image_url) }}"
                                    alt="Halaman {{ $page->page_number }}"
                                    class="h-16 w-12 object-cover rounded border border-gray-200"
                                >
                            </td>

                                                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">
                                    {{ $page->bounding_boxes_count ?? 0 }}
                                </span>
                            </td>

                                                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    {{ $page->audio_count ?? 0 }}
                                </span>
                            </td>

                                                        <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $page->book->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($page->book->status ?? 'draft') }}
                                </span>
                            </td>

                                                        <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-600">{{ $page->created_at->format('d M Y') }}</p>
                            </td>

                                                        <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                                                        <a href="{{ url('/pages/' . $page->id . '/edit') }}" 
                                       title="Edit halaman"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors">
                                        ✏️
                                    </a>

                                                                        <a href="{{ url('/pages/' . $page->id) }}" 
                                       title="Kelola anotasi"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-orange-50 text-orange-600 hover:bg-orange-100 transition-colors">
                                        📝
                                    </a>

                                                                        <a href="{{ url('/pages/' . $page->id . '/audio') }}" 
                                       title="Kelola audio"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-100 transition-colors">
                                        🔊
                                    </a>

                                                                        <form method="POST" action="{{ url('/pages/' . $page->id) }}" class="inline" onsubmit="return confirm('Hapus halaman ini? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            title="Hapus halaman"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

                @if($pages instanceof \Illuminate\Pagination\Paginator && $pages->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Menampilkan {{ $pages->firstItem() }} hingga {{ $pages->lastItem() }} dari {{ $pages->total() }} halaman
                </div>
                <div class="flex gap-2">
                    {{ $pages->links('pagination::tailwind') }}
                </div>
            </div>
        @endif
    </div>
@endif

@endsection
