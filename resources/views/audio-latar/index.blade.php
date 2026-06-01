@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Audio Latar</h1>
    <p class="text-gray-500 mt-2">Kelola semua audio latar buku Anda</p>
</div>

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

<div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">Tambah Audio Latar Baru</h2>
    
    <form action="{{ route('audio-latar.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Audio <span class="text-red-500">*</span></label>
                <input type="text" name="nama_audio"
                    placeholder="Misalnya: Background musik halaman 1"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">File Audio <span class="text-red-500">*</span></label>
                <input type="file" name="path_file" accept="audio/*" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
            </div>

            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                Tambah
            </button>
        </div>

        <p class="text-xs text-gray-600">Format: MP3, WAV, OGG, M4A (Maksimal 10MB)</p>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($audioLatar as $audio)
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $audio->nama_audio }}</h3>
                    <p class="text-xs text-gray-600 mt-1">ID: {{ $audio->id_audio_latar }}</p>
                </div>
                <form action="{{ route('audio-latar.delete', $audio->id_audio_latar) }}" method="POST" onsubmit="return confirm('Hapus audio ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">Hapus</button>
                </form>
            </div>

            <audio controls class="w-full mb-3" src="{{ asset('storage/' . $audio->path_file) }}"></audio>

            <div class="text-xs text-gray-600 space-y-1">
                <p><strong>Dibuat:</strong> {{ $audio->created_at->locale('id_ID')->format('d F Y H:i') }}</p>
                <p><strong>Diupdate:</strong> {{ $audio->updated_at->locale('id_ID')->format('d F Y H:i') }}</p>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-gray-500">Belum ada audio latar</p>
        </div>
    @endforelse
</div>

@endsection
