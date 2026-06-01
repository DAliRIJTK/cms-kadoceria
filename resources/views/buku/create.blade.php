@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="/buku" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Daftar Buku</a>
    <h1 class="text-3xl font-bold text-gray-800">Buat Buku Cerita Baru</h1>
    <p class="text-gray-500 mt-2">Unggah dan kelola buku dwibahasa interaktif Anda</p>
</div>

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <span class="text-2xl">⚠️</span>
            <div class="flex-1">
                <h3 class="font-semibold text-red-800 mb-2">Gagal Menyimpan Buku</h3>
                <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <span class="text-2xl">✅</span>
            <div class="flex-1">
                <p class="text-green-800 font-medium">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

<form method="POST" action="{{ route('buku.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">📖 Informasi Buku</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Buku Indonesia <span class="text-red-500">*</span></label>
                <input type="text" name="judul_idn"
                    value="{{ old('judul_idn') }}"
                    placeholder="Masukkan judul buku dalam bahasa Indonesia..."
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('judul_idn') ? 'border-red-500' : '' }}">
                @error('judul_idn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Judul Buku Sunda</label>
                <input type="text" name="judul_sn"
                    value="{{ old('judul_sn') }}"
                    placeholder="Masukkan judul buku dalam bahasa Sunda..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('judul_sn') ? 'border-red-500' : '' }}">
                @error('judul_sn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Penulis</label>
                <input type="text" name="penulis"
                    value="{{ old('penulis') }}"
                    placeholder="Nama penulis..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('penulis') ? 'border-red-500' : '' }}">
                @error('penulis')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Ilustrator</label>
                <input type="text" name="ilustrator"
                    value="{{ old('ilustrator') }}"
                    placeholder="Nama ilustrator..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition {{ $errors->has('ilustrator') ? 'border-red-500' : '' }}">
                @error('ilustrator')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Sinopsis Buku Indonesia</label>
            <textarea name="deskripsi_idn"
                placeholder="Tulis sinopsis singkat tentang buku ini dalam bahasa Indonesia..."
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none {{ $errors->has('deskripsi_idn') ? 'border-red-500' : '' }}">{{ old('deskripsi_idn') }}</textarea>
            @error('deskripsi_idn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Sinopsis Buku Sunda</label>
            <textarea name="deskripsi_sn"
                placeholder="Tulis sinopsis singkat tentang buku ini dalam bahasa Sunda..."
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none {{ $errors->has('deskripsi_sn') ? 'border-red-500' : '' }}">{{ old('deskripsi_sn') }}</textarea>
            @error('deskripsi_sn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="bg-blue-50 rounded-lg shadow-sm p-6 border border-blue-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-blue-200">📄 Unggah File PDF</h2>
        
        <div class="mb-4 p-4 bg-blue-100 rounded border border-blue-300">
            <p class="text-sm text-blue-900">
                <strong>Catatan:</strong> Sistem akan secara otomatis mengkonversi halaman PDF menjadi gambar yang dapat dikelola.
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">File PDF <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="file" name="pdf_file" accept=".pdf" required
                    id="pdf_file"
                    class="w-full px-4 py-3 border-2 border-dashed border-blue-300 rounded-lg focus:outline-none focus:border-blue-500 transition {{ $errors->has('pdf_file') ? 'border-red-500' : '' }} cursor-pointer">
            </div>
            <p class="text-xs text-gray-600 mt-2">📌 Ukuran maksimal: 50MB | Format: PDF</p>
            @error('pdf_file')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex gap-3 justify-end">
        <a href="/buku" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium">
            Batal
        </a>
        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
            ✅ Buat Buku
        </button>
    </div>

</form>

@endsection
