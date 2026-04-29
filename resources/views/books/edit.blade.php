@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ url('/books/' . $book->id) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali</a>
    <h1 class="text-3xl font-bold text-gray-800">✏️ Edit Informasi Buku</h1>
    <p class="text-gray-600 mt-2">Perbarui detail buku cerita dwibahasa Anda</p>
</div>

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <span class="text-2xl">⚠️</span>
            <div class="flex-1">
                <h3 class="font-semibold text-red-800 mb-2">Gagal Menyimpan</h3>
                <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm p-8 border border-gray-200">
    <form method="POST" action="{{ url('/books/' . $book->id) }}" class="space-y-6">
        @csrf
        @method('PATCH')

                <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Buku <span class="text-red-500">*</span></label>
            <input 
                type="text" 
                name="title"
                value="{{ old('title', $book->title) }}"
                placeholder="Masukkan judul buku..."
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('title') ? 'border-red-500' : '' }}"
            >
            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Penulis</label>
                <input 
                    type="text" 
                    name="author"
                    value="{{ old('author', $book->author) }}"
                    placeholder="Nama penulis..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('author') ? 'border-red-500' : '' }}"
                >
                @error('author')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

                        <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Penerbit</label>
                <input 
                    type="text" 
                    name="publisher"
                    value="{{ old('publisher', $book->publisher) }}"
                    placeholder="Nama penerbit..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('publisher') ? 'border-red-500' : '' }}"
                >
                @error('publisher')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

                <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi Buku</label>
            <textarea 
                name="description"
                placeholder="Tulis deskripsi ringkas tentang buku ini..."
                rows="6"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none {{ $errors->has('description') ? 'border-red-500' : '' }}"
            >{{ old('description', $book->description) }}</textarea>
            <p class="text-xs text-gray-500 mt-2">Tujuan, tema, dan informasi relevan lainnya</p>
            @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 space-y-4">
            <h3 class="font-semibold text-gray-800">📊 Informasi Saat Ini</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">ID Buku</p>
                    <p class="font-mono font-semibold text-gray-800">{{ $book->id }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Total Halaman</p>
                    <p class="font-semibold text-gray-800">{{ $book->pages()->count() }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Status</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $book->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        {{ ucfirst($book->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-500">Dibuat</p>
                    <p class="font-semibold text-gray-800">{{ $book->created_at->format('d M Y') }}</p>
                </div>
            </div>
        </div>

                <div class="flex gap-3 justify-end pt-6 border-t border-gray-200">
            <a href="{{ url('/books/' . $book->id) }}" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                Batal
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
                ✅ Simpan Perubahan
            </button>
        </div>
    </form>
</div>

@endsection
