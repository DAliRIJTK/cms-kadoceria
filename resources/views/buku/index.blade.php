@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Daftar Buku Cerita</h1>
            <p class="text-gray-500 mt-1">Kelola koleksi buku dwibahasa Anda</p>
        </div>
        <a href="{{ route('buku.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg shadow-md transition-colors duration-200 font-medium flex items-center gap-2">
            <span class="text-xl">+</span> Tambah Buku
        </a>
    </div>

    <form method="GET" action="{{ route('buku.index') }}" class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Cari Buku</label>
                <input 
                    type="text" 
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Ketik judul atau penulis buku..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">Semua Status</option>
                    <option value="Draft" @if(request('status') === 'Draft') selected @endif>Draft</option>
                    <option value="Terbit" @if(request('status') === 'Terbit') selected @endif>Terbit</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Urutkan</label>
                <select name="sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    <option value="">Default</option>
                    <option value="title_asc" @if(request('sort') === 'title_asc') selected @endif>Judul (A-Z)</option>
                    <option value="title_desc" @if(request('sort') === 'title_desc') selected @endif>Judul (Z-A)</option>
                    <option value="date_newest" @if(request('sort') === 'date_newest') selected @endif>Terbaru</option>
                    <option value="date_oldest" @if(request('sort') === 'date_oldest') selected @endif>Terlama</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 font-medium text-center">
                    Cari
                </button>
                <a href="{{ route('buku.index') }}" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg transition-colors duration-200 font-medium text-center">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

@if($buku->isEmpty())
    <div class="flex flex-col items-center justify-center mt-20 bg-white rounded-lg shadow-sm p-12">
        <div class="text-7xl mb-4 opacity-50">📖</div>
        <p class="text-xl font-semibold text-gray-700 mb-2">Belum ada buku cerita</p>
        <p class="text-gray-500 mb-6">Mulai dengan menambahkan buku cerita pertama Anda</p>
        <a href="{{ route('buku.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
            + Buat Buku Baru
        </a>
    </div>
@else

    <div class="mb-4">
        <p class="text-sm text-gray-600">
            Menampilkan {{ $buku->firstItem() }} hingga {{ $buku->lastItem() }} dari {{ $buku->total() }} buku
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        @foreach($buku as $book)
            <a href="{{ route('buku.show', $book->id_buku) }}" class="group">
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">

                    <div class="relative overflow-hidden bg-gray-200 h-64">
                        @if($book->path_cover && file_exists(storage_path('app/public/' . $book->path_cover)))
                            <img 
                                src="{{ asset('storage/' . $book->path_cover) }}"
                                alt="{{ $book->judul_idn }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            >
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-blue-50">
                                <svg class="w-24 h-24 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747m0-13c5.5 0 10 5.998 10 13s-4.5 10.747-10 10.747m0-13V6.253m0 13V20.5"></path>
                                </svg>
                            </div>
                        @endif

                        <div class="absolute top-3 right-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold @if($book->status_publikasi === 'Terbit') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">
                                {{ $book->status_publikasi ?? 'Draft' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 line-clamp-2 group-hover:text-blue-600 transition-colors mb-2">
                            {{ $book->judul_idn }}
                        </h3>

                        <div class="space-y-2 text-xs text-gray-500">
                            @if($book->penulis)
                                <p class="flex items-center gap-1">
                                    <span>👤</span>
                                    {{ $book->penulis }}
                                </p>
                            @else
                                <p class="flex items-center gap-1">
                                    <span>👤</span>
                                    -
                                </p>
                            @endif
                            <p class="flex items-center gap-1">
                                <span>📅</span>
                                {{ $book->created_at->locale('id_ID')->format('d M Y') }}
                            </p>
                            <p class="flex items-center gap-1">
                                <span>📄</span>
                                {{ $book->halaman()->count() ?? 0 }} halaman
                            </p>
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

    @if($buku->hasPages())
        <div class="flex justify-center">
            {{ $buku->links('pagination::tailwind') }}
        </div>
    @endif

@endif

@endsection
