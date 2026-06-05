@extends('layouts.dashboard')

@section('content')

<div class="mb-6">
    <a href="{{ route('buku.index') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
        ← Kembali ke Daftar Buku
    </a>
</div>

{{-- Header: Judul + Status --}}
<div class="flex justify-between items-start mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $buku->judul_idn }}</h1>
        @if($buku->judul_sn)
            <p class="text-gray-500 mt-1">{{ $buku->judul_sn }}</p>
        @endif
    </div>
    <div>
        @if($buku->status_publikasi === 'Terbit')
            <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-lg font-semibold text-sm border border-green-200">
                ✅ Terbit
            </span>
        @else
            <span class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-50 text-yellow-800 rounded-lg font-semibold text-sm border border-yellow-200">
                📋 Draft
            </span>
        @endif
    </div>
</div>

{{-- Error/Success Alerts --}}
@if($errors->has('publication'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        {{ $errors->first('publication') }}
    </div>
@endif

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
        {{ session('success') }}
    </div>
@endif

{{-- Info Card + Cover --}}
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

            {{-- Metadata row --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 pb-6 border-b border-gray-100">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Ilustrator</p>
                    <p class="text-gray-900 font-semibold">{{ $buku->ilustrator ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Penulis</p>
                    <p class="text-gray-900 font-semibold">{{ $buku->penulis ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Halaman</p>
                    <p class="text-gray-900 font-semibold">{{ $buku->halaman()->count() }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Dibuat Pada</p>
                    <p class="text-gray-900 font-semibold">{{ $buku->created_at->locale('id_ID')->format('d M Y') }}</p>
                </div>
            </div>

            {{-- Sinopsis --}}
            @if($buku->deskripsi_idn)
                <div class="mb-4 pb-4 border-b border-gray-100">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Sinopsis Bahasa Indonesia</p>
                    <p class="text-gray-700">{{ $buku->deskripsi_idn }}</p>
                </div>
            @endif

            @if($buku->deskripsi_sn)
                <div class="mb-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Sinopsis Bahasa Sunda</p>
                    <p class="text-gray-700">{{ $buku->deskripsi_sn }}</p>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-3 pt-2">
                <a href="{{ route('buku.edit', $buku->id_buku) }}"
                   class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm transition-colors">
                    Edit Informasi
                </a>

                <a href="{{ route('halaman.management', ['id_buku' => $buku->id_buku]) }}"
                   class="px-5 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold text-sm transition-colors">
                    Kelola Halaman
                </a>

                <form action="{{ route('buku.destroy', $buku->id_buku) }}" method="POST" class="inline"
                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini? Tindakan ini tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors">
                        Hapus Buku
                    </button>
                </form>

                @if($buku->status_publikasi === 'Draft')
                    {{-- Tombol Publikasikan --}}
                    <form action="{{ route('buku.updateStatus', $buku->id_buku) }}" method="POST" class="inline"
                          onsubmit="return confirm('Publikasikan buku ini? Buku akan dapat diunduh oleh pengguna aplikasi.');">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status_publikasi" value="Terbit">
                        <button type="submit"
                                class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm transition-colors">
                            🚀 Publikasikan
                        </button>
                    </form>
                @else
                    {{-- Tombol Kembalikan ke Draft --}}
                    <button
                        onclick="document.getElementById('modal-unpublish').classList.remove('hidden')"
                        class="px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold text-sm transition-colors">
                        📋 Kembalikan ke Draft
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Cover --}}
    <div class="lg:col-span-1">
        @if($buku->path_cover && file_exists(storage_path('app/public/' . $buku->path_cover)))
            <img src="{{ asset('storage/' . $buku->path_cover) }}"
                 alt="{{ $buku->judul_idn }}"
                 class="w-full rounded-xl shadow-md border border-gray-200 object-cover">
        @else
            <div class="w-full aspect-[3/4] bg-gray-100 rounded-xl border border-gray-200 flex items-center justify-center">
                <span class="text-gray-400 text-sm">Tidak ada cover</span>
            </div>
        @endif

        {{-- Info ZIP bundle jika sudah terbit --}}
        @if($buku->status_publikasi === 'Terbit' && !empty($buku->zip_bundle_path))
            @php
                $zipAbs  = storage_path('app/public/' . $buku->zip_bundle_path);
                $zipSize = file_exists($zipAbs) ? round(filesize($zipAbs) / 1048576, 1) . ' MB' : null;
            @endphp
            @if($zipSize)
                <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-center">
                    <p class="text-xs text-green-700 font-semibold">📦 Bundle tersedia</p>
                    <p class="text-xs text-green-600 mt-0.5">{{ $zipSize }}</p>
                    <a href="{{ asset('storage/' . $buku->zip_bundle_path) }}"
                       class="mt-2 inline-block text-xs text-green-700 underline hover:text-green-900"
                       target="_blank">
                        Unduh ZIP
                    </a>
                </div>
            @endif
        @endif
    </div>
</div>

{{-- Flipbook Preview --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Pratinjau Flipbook</h2>

    @if($buku->halaman()->count() > 0)
        <div class="flex flex-col items-center gap-4">
            <p class="text-gray-500 text-sm">Pratinjau Flipbook ({{ $buku->halaman()->count() }} Halaman)</p>
            <p class="text-gray-400 text-xs">Klik tombol di bawah untuk melihat pratinjau interaktif</p>
            <a href="{{ route('halaman.management', ['id_buku' => $buku->id_buku]) }}"
               class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm transition-colors">
                Buka Pratinjau Penuh
            </a>
        </div>
    @else
        <div class="text-center py-10 bg-gray-50 rounded-lg border border-dashed border-gray-300">
            <p class="text-gray-400">Belum ada halaman. Silakan unggah PDF untuk membuat halaman.</p>
        </div>
    @endif
</div>

{{-- Modal Konfirmasi Kembalikan ke Draft --}}
<div id="modal-unpublish" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
        <div class="flex items-start gap-4 mb-5">
            <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-xl">
                ⚠️
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Kembalikan ke Draft?</h3>
                <p class="text-sm text-gray-600">
                    Buku <strong>{{ $buku->judul_idn }}</strong> akan disembunyikan dari aplikasi Flutter dan tidak bisa diunduh pengguna hingga dipublikasikan kembali.
                </p>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button
                onclick="document.getElementById('modal-unpublish').classList.add('hidden')"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-sm transition-colors">
                Batal
            </button>

            <form action="{{ route('buku.updateStatus', $buku->id_buku) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status_publikasi" value="Draft">
                <input type="hidden" name="confirm_unpublish" value="yes">
                <button type="submit"
                        class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold text-sm transition-colors">
                    Ya, Kembalikan ke Draft
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Tutup modal jika klik backdrop --}}
<script>
    document.getElementById('modal-unpublish').addEventListener('click', function (e) {
        if (e.target === this) this.classList.add('hidden');
    });
</script>

@endsection