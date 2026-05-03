@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Kelola Audio</h1>
    <p class="text-gray-500 mt-1">Kelola semua file audio dari seluruh halaman buku cerita dwibahasa</p>
</div>

<form method="GET" action="/audio-management" class="bg-white rounded-lg shadow-sm p-4 mb-8 border border-gray-200">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Cari Audio</label>
            <input 
                type="text" 
                name="keyword"
                value="{{ request('keyword') }}"
                placeholder="Cari berdasarkan label atau buku..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Audio</label>
            <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                <option value="">Semua Jenis</option>
                <option value="narration" {{ request('type') == 'narration' ? 'selected' : '' }}>🎤 Narasi</option>
                <option value="backsound" {{ request('type') == 'backsound' ? 'selected' : '' }}>🎵 Backsound</option>
                <option value="object" {{ request('type') == 'object' ? 'selected' : '' }}>🎯 Audio Objek</option>
            </select>
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
            <label class="block text-sm font-medium text-gray-700 mb-2">Urutkan</label>
            <select name="sort" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                <option value="date_newest" {{ request('sort') == 'date_newest' ? 'selected' : '' }}>Terbaru</option>
                <option value="date_oldest" {{ request('sort') == 'date_oldest' ? 'selected' : '' }}>Terlama</option>
                <option value="label_asc" {{ request('sort') == 'label_asc' ? 'selected' : '' }}>Label (A-Z)</option>
                <option value="label_desc" {{ request('sort') == 'label_desc' ? 'selected' : '' }}>Label (Z-A)</option>
                <option value="type_asc" {{ request('sort') == 'type_asc' ? 'selected' : '' }}>Jenis (A-Z)</option>
            </select>
        </div>
    </div>

    <div class="flex gap-2 mt-4">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
            Cari
        </button>
        <a href="/audio-management" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition-colors duration-200 font-medium">
            Reset
        </a>
    </div>
</form>

@if($audios->isEmpty())
    <div class="flex flex-col items-center justify-center mt-20 bg-white rounded-lg shadow-sm p-12 border border-gray-200">
        <div class="text-7xl mb-4 opacity-50">🔊</div>
        <p class="text-xl font-semibold text-gray-700 mb-2">Tidak ada audio</p>
        <p class="text-gray-500 mb-6">Mulai dengan membuat buku dan menambahkan audio ke halaman</p>
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Label Audio</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Jenis</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Halaman</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Buku</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Preview Audio</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @foreach($audios as $audio)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-900">{{ $audio->label }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $audio->id }}</p>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    @if($audio->type === 'narration')
                                        bg-purple-100 text-purple-800
                                    @elseif($audio->type === 'backsound')
                                        bg-blue-100 text-blue-800
                                    @else
                                        bg-green-100 text-green-800
                                    @endif
                                ">
                                    @if($audio->type === 'narration')
                                        🎤 Narasi
                                    @elseif($audio->type === 'backsound')
                                        🎵 Backsound
                                    @else
                                        🎯 Audio Objek
                                    @endif
                                </span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="font-semibold text-gray-900">Halaman {{ $audio->page->page_number }}</p>
                                <p class="text-xs text-gray-500">ID: {{ $audio->page->id }}</p>
                            </td>

                            <td class="px-6 py-4">
                                <a href="{{ url('/books/' . $audio->page->book_id) }}" class="text-blue-600 hover:text-blue-700 hover:underline font-medium">
                                    {{ $audio->page->book->title }}
                                </a>
                                <p class="text-xs text-gray-500">{{ $audio->page->book->author ?? '-' }}</p>
                            </td>

                            <td class="px-6 py-4">
                                <audio controls class="h-6 rounded bg-gray-100 border border-gray-300" style="width: 200px;">
                                    <source src="{{ asset('storage/' . $audio->file_url) }}" type="audio/mpeg">
                                    Browser Anda tidak mendukung audio player.
                                </audio>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-600">{{ $audio->created_at->format('d M Y') }}</p>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <a href="{{ url('/pages/' . $audio->page_id . '/audio') }}" 
                                       title="Edit audio"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors">
                                        ✏️
                                    </a>

                                    <form method="POST" action="{{ url('/audio/' . $audio->id) }}" class="inline" onsubmit="return confirm('Hapus audio ini? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            title="Hapus audio"
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

        @if($audios instanceof \Illuminate\Pagination\Paginator && $audios->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Menampilkan {{ $audios->firstItem() }} hingga {{ $audios->lastItem() }} dari {{ $audios->total() }} audio
                </div>
                <div class="flex gap-2">
                    {{ $audios->links('pagination::tailwind') }}
                </div>
            </div>
        @endif
    </div>
@endif

@endsection
