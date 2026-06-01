@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ route('halaman.management') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Manajemen Halaman</a>
    <h1 class="text-3xl font-bold text-gray-800">Halaman {{ $halaman->nomor_halaman }}</h1>
    <p class="text-gray-500 mt-1">{{ $halaman->buku->judul_idn }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
            @if($halaman->path_gambar && file_exists(storage_path('app/public/' . $halaman->path_gambar)))
                <img src="{{ asset('storage/' . $halaman->path_gambar) }}" alt="Halaman {{ $halaman->nomor_halaman }}" class="w-full">
            @else
                <div class="w-full aspect-square bg-gray-200 flex items-center justify-center">
                    <span class="text-2xl font-bold text-gray-600">{{ $halaman->nomor_halaman }}</span>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">Narasi & Audio</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-semibold text-gray-700 mb-2 block">Narasi Indonesia</label>
                    @if($halaman->narasi_indo)
                        <div class="flex items-center gap-3 bg-gray-100 p-3 rounded-lg">
                            <span class="text-sm text-gray-700">Audio tersimpan</span>
                            <audio controls class="flex-1 h-8" src="{{ asset('storage/' . $halaman->narasi_indo) }}"></audio>
                        </div>
                    @else
                        <p class="text-sm text-gray-600 italic">Belum ada narasi Indonesia</p>
                    @endif
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700 mb-2 block">Narasi Sunda</label>
                    @if($halaman->narasi_sunda)
                        <div class="flex items-center gap-3 bg-gray-100 p-3 rounded-lg">
                            <span class="text-sm text-gray-700">Audio tersimpan</span>
                            <audio controls class="flex-1 h-8" src="{{ asset('storage/' . $halaman->narasi_sunda) }}"></audio>
                        </div>
                    @else
                        <p class="text-sm text-gray-600 italic">Belum ada narasi Sunda</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">Area Interaktif</h2>
            
            @if($halaman->areaInteraktif()->count() > 0)
                <div class="space-y-3">
                    @foreach($halaman->areaInteraktif as $area)
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Area {{ $loop->iteration }}</p>
                                    <p class="text-xs text-gray-600">Posisi: X={{ $area->x }}, Y={{ $area->y }} | Ukuran: {{ $area->lebar_area }}x{{ $area->panjang_area }}</p>
                                </div>
                                <form action="{{ route('halaman.deleteAreaInteraktif', ['area' => $area->id_area]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus area ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">Hapus</button>
                                </form>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs">
                                @if($area->audio_indo)
                                    <div>
                                        <p class="font-medium text-gray-700 mb-1">Audio Indo</p>
                                        <audio controls class="w-full h-6" src="{{ asset('storage/' . $area->audio_indo) }}"></audio>
                                    </div>
                                @endif
                                @if($area->audio_sunda)
                                    <div>
                                        <p class="font-medium text-gray-700 mb-1">Audio Sunda</p>
                                        <audio controls class="w-full h-6" src="{{ asset('storage/' . $area->audio_sunda) }}"></audio>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-600 italic">Belum ada area interaktif</p>
            @endif
        </div>
    </div>

    <div>
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">Aksi</h3>
            
            <div class="space-y-2">
                <a href="{{ route('halaman.edit', $halaman->id_halaman) }}" class="block w-full px-4 py-2 text-center bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium text-sm">
                    Edit
                </a>
                <form action="{{ route('halaman.destroy', $halaman->id_halaman) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus halaman ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 text-center bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-medium text-sm">
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <h3 class="font-semibold text-gray-800 mb-4">Info</h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-600 font-medium">Buku</p>
                    <p class="text-gray-800">{{ $halaman->buku->judul_idn }}</p>
                </div>

                <div>
                    <p class="text-gray-600 font-medium">Nomor Halaman</p>
                    <p class="text-gray-800">{{ $halaman->nomor_halaman }}</p>
                </div>

                <div>
                    <p class="text-gray-600 font-medium">Dibuat</p>
                    <p class="text-gray-800">{{ $halaman->created_at->locale('id_ID')->format('d F Y H:i') }}</p>
                </div>

                <div>
                    <p class="text-gray-600 font-medium">Diupdate</p>
                    <p class="text-gray-800">{{ $halaman->updated_at->locale('id_ID')->format('d F Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
