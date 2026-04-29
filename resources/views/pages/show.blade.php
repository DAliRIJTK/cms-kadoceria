@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ url('/books/' . $page->book_id) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Buku</a>
    
    <h1 class="text-3xl font-bold text-gray-800 mb-2">📄 Halaman {{ $page->page_number }}</h1>
    <p class="text-gray-600">Buku: <span class="font-semibold">{{ $page->book->title }}</span></p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 sticky top-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">📷 Preview Halaman</h2>
            <img 
                src="{{ asset('storage/' . $page->image_url) }}"
                alt="Halaman {{ $page->page_number }}"
                class="w-full rounded-lg border border-gray-300"
            >
        </div>
    </div>

        <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">ℹ️ Informasi</h3>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-500">Nomor Halaman</p>
                    <p class="font-semibold text-gray-800">{{ $page->page_number }}</p>
                </div>
                <div>
                    <p class="text-gray-500">ID Halaman</p>
                    <p class="font-semibold text-gray-800 font-mono">{{ $page->id }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Dibuat</p>
                    <p class="font-semibold text-gray-800">{{ $page->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>

                <div class="bg-orange-50 rounded-lg shadow-sm p-4 border border-orange-200">
            <h3 class="text-lg font-semibold text-orange-900 mb-2">📝 Anotasi</h3>
            <p class="text-3xl font-bold text-orange-600">{{ $page->boundingBoxes->count() }}</p>
            <p class="text-sm text-orange-700 mt-1">total anotasi pada halaman ini</p>
        </div>

                <div class="bg-green-50 rounded-lg shadow-sm p-4 border border-green-200">
            <h3 class="text-lg font-semibold text-green-900 mb-2">🔊 Audio</h3>
            <p class="text-3xl font-bold text-green-600">{{ $page->audios->count() }}</p>
            <p class="text-sm text-green-700 mt-1">total audio pada halaman ini</p>
        </div>

                <div class="space-y-2">
            <a href="{{ url('/pages/' . $page->id . '/edit') }}" class="w-full block text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                ✏️ Edit Halaman
            </a>
            <a href="{{ url('/pages/' . $page->id . '/audio') }}" class="w-full block text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium">
                🔊 Kelola Audio
            </a>
        </div>
    </div>
</div>

<div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">📝 Daftar Anotasi ({{ $page->boundingBoxes->count() }})</h2>
    </div>

    @if($page->boundingBoxes->isEmpty())
        <div class="p-8 text-center">
            <p class="text-gray-500">Belum ada anotasi pada halaman ini</p>
        </div>
    @else
        <div class="divide-y divide-gray-200">
            @foreach($page->boundingBoxes as $box)
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="font-semibold text-gray-900">Anotasi #{{ $loop->iteration }}</p>
                            <p class="text-sm text-gray-500">ID: {{ $box->id }}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                            Posisi: ({{ $box->x }}, {{ $box->y }})
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-sm mb-4">
                        <div>
                            <p class="text-gray-500">Ukuran</p>
                            <p class="font-semibold text-gray-800">{{ $box->width }} × {{ $box->height }}px</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Label</p>
                            <p class="font-semibold text-gray-800">{{ $box->label ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Audio Terkait</p>
                            <p class="font-semibold text-gray-800">{{ $box->audios->count() }}</p>
                        </div>
                    </div>

                                        @if($box->audios->isNotEmpty())
                        <div class="bg-green-50 rounded p-3 border border-green-200">
                            <p class="text-xs font-semibold text-green-900 mb-2">🔊 Audio pada Anotasi Ini:</p>
                            <div class="space-y-2">
                                @foreach($box->audios as $audio)
                                    <div class="flex items-center justify-between bg-white p-2 rounded border border-green-200">
                                        <div class="text-xs">
                                            <p class="font-semibold text-gray-800">{{ $audio->label ?? 'Audio' }}</p>
                                            <p class="text-gray-500">{{ basename($audio->file_url) }}</p>
                                        </div>
                                        <form method="POST" action="{{ url('/audio/' . $audio->id) }}" class="inline" onsubmit="return confirm('Hapus audio ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-700">🗑️</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

@endsection
