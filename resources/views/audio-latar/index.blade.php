@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ url()->previous() }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali</a>
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

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <span class="text-2xl">⚠️</span>
            <div class="flex-1">
                <ul class="list-disc pl-5 text-red-800 font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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
                <div class="w-full px-4 py-2 border border-gray-300 rounded-lg focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent flex items-center justify-between gap-2 bg-white">
                    <label for="audio_file"
                        class="flex-shrink-0 px-3 py-1 bg-white border border-gray-300 rounded-md text-xs font-medium cursor-pointer hover:bg-gray-50 transition-colors shadow-sm">
                        Pilih File
                        <input type="file" id="audio_file" name="path_file" accept=".mp3,.m4a" required class="hidden">
                    </label>
                    <span id="audio-file-name"
                          class="flex-1 text-sm text-gray-400 truncate">
                        Belum ada file dipilih
                    </span>
                </div>
            </div>

            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                Tambah
            </button>
        </div>

        <p class="text-xs text-gray-600">Format: MP3, M4A (Maksimal 1MB)</p>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    @forelse($audioLatar as $audio)
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $audio->nama_audio }}</h3>
                        <p class="text-xs text-gray-600 mt-1">ID: {{ $audio->id_audio_latar }}</p>
                    </div>
                    @if($audio->halaman_count > 0)
                        <button type="button" 
                                class="text-gray-400 cursor-not-allowed font-medium text-sm" 
                                title="Tidak dapat dihapus karena sedang digunakan">
                            Hapus
                        </button>
                    @else
                        <form action="{{ route('audio-latar.delete', $audio->id_audio_latar) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-sm">Hapus</button>
                        </form>
                    @endif
                </div>

                <audio controls class="w-full mb-3" src="{{ asset('storage/' . $audio->path_file) }}"></audio>

                <div class="text-xs text-gray-600 space-y-1">
                    <p><strong>Dibuat:</strong> {{ $audio->created_at->locale('id_ID')->format('d F Y') }}</p>
                    <p><strong>Diupdate:</strong> {{ $audio->updated_at->locale('id_ID')->format('d F Y') }}</p>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-700 mb-2">Status Penggunaan:</p>

                @if($audio->halaman_count > 0)
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-2.5 space-y-1">
                        <span class="text-[11px] font-semibold text-blue-700 block">
                            Digunakan di Buku:
                        </span>

                        <ul class="list-disc list-inside text-[11px] text-gray-600 space-y-0.5">
                            @foreach($audio->halaman->pluck('buku')->filter()->unique('id_buku') as $buku)
                                <li>
                                    <a href="{{ route('buku.edit', $buku) }}" class="text-blue-600 hover:underline">
                                        {{ $buku->judul_idn ?? 'Buku tanpa judul' }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <span class="inline-block text-[11px] font-semibold bg-gray-100 text-gray-500 px-2.5 py-1 rounded-full">
                        Tidak digunakan
                    </span>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-12 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-gray-500">Belum ada audio latar</p>
        </div>
    @endforelse
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('audio_file');
    const fileNameSpan = document.getElementById('audio-file-name');

    if (fileInput && fileNameSpan) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                fileNameSpan.textContent = this.files[0].name;
                fileNameSpan.classList.replace('text-gray-400', 'text-gray-700');
            } else {
                fileNameSpan.textContent = 'Belum ada file dipilih';
                fileNameSpan.classList.replace('text-gray-700', 'text-gray-400');
            }
        });
    }
});
</script>
@endpush

@endsection
