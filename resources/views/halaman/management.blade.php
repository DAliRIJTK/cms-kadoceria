@extends('layouts.dashboard')

@section('content')

<div class="mb-6">
    <a href="{{ route('buku.show', request('id_buku')) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
        ← Kembali ke Halaman Buku
    </a>
</div>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Kelola Halaman</h1>
    <p class="text-gray-500 mt-1">Kelola semua halaman dari buku cerita dwibahasa</p>
</div>
<x-modal-alert id="alertModal" type="error" />
<x-modal-alert id="successModal" type="success" />

<div id="flash-data" 
     data-error="{{ $errors->any() ? $errors->first() : '' }}"
     data-success="{{ session('success') }}">
</div>

@if($halaman->isEmpty())
    <div class="flex flex-col items-center justify-center mt-16 bg-white rounded-xl shadow-sm p-12 border border-gray-200">
        <div class="text-6xl mb-4 opacity-40">📭</div>
        <p class="text-xl font-semibold text-gray-700 mb-2">Tidak ada halaman</p>
        <p class="text-gray-400 mb-6 text-sm">Halaman dibuat secara otomatis saat Anda mengunggah PDF ke buku baru</p>
        <a href="{{ route('buku.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-semibold text-sm">
             + Buat Buku Baru
        </a>
    </div>
@else
    <form action="{{ route('halaman.bulkDestroy') }}" method="POST" id="bulkDeleteForm">
        @csrf
        @method('DELETE')
        
        <div class="mb-4 flex justify-end">
            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm">
                Hapus Halaman Terpilih
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Halaman</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Buku</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pratinjau</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Anotasi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Audio Area</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Narasi</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Latar</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="sortableBody" class="divide-y divide-gray-100">
                        @foreach($halaman as $page)
                            @php
                                $isCover = $page->nomor_halaman === 1;
                                $anotasiCount = $page->areaInteraktif->count();
                                
                                // Pengecekan Narasi & Latar
                                $narasiId = !empty($page->narasi_indo);
                                $narasiSu = !empty($page->narasi_sunda);
                                $hasBacksound = !empty($page->id_audio_latar);
                                
                                // Pengecekan Kelengkapan Audio Area Interaktif
                                $areaAudioIdCount = $page->areaInteraktif->whereNotNull('audio_indo')->count();
                                $areaAudioSuCount = $page->areaInteraktif->whereNotNull('audio_sunda')->count();
                                $totalExpectedAreaAudio = $anotasiCount * 2;
                                $totalActualAreaAudio = $areaAudioIdCount + $areaAudioSuCount;
                            @endphp

                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if($page->nomor_halaman === 1)
                                            <div>
                                                <p class="font-semibold text-gray-900 text-sm page-number-label">Sampul Halaman</p>
                                                <p class="text-[10px] text-gray-500 mt-0.5 whitespace-normal max-w-[250px] leading-tight">halaman ini tidak akan ditampilkan pada aplikasi mobile Kado Ceria</p>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" name="selected_pages[]" value="{{ $page->id_halaman }}" id="page-{{ $page->id_halaman }}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                <label for="page-{{ $page->id_halaman }}" class="font-semibold text-gray-900 text-sm page-number-label cursor-pointer select-none">
                                                    Halaman {{ $page->nomor_halaman - 1 }}
                                                </label>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <a href="{{ route('buku.show', $page->buku) }}"
                                       class="text-blue-600 hover:text-blue-700 hover:underline font-semibold text-sm leading-tight block">
                                        {{ $page->buku->judul_idn }}
                                    </a>
                                    <p class="text-xs text-gray-400">{{ $page->buku->penulis ?? '-' }}</p>
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($page->path_gambar)
                                        <img
                                            src="{{ Storage::disk(config('filesystems.default'))->url($page->path_gambar) }}"
                                            alt="Halaman {{ $page->nomor_halaman }}"
                                            class="h-14 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:shadow-md transition-shadow"
                                            data-src="{{ Storage::disk(config('filesystems.default'))->url($page->path_gambar) }}"
                                            data-title="Halaman {{ $page->nomor_halaman }}"
                                            onclick="showImageModal(this.dataset.src, this.dataset.title)"
                                        >
                                    @else
                                        <div class="h-14 w-10 bg-gray-100 rounded border border-gray-200 flex items-center justify-center">
                                            <span class="text-xs text-gray-400">-</span>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($isCover)
                                        <span class="text-gray-400 text-xs">-</span>
                                    @else
                                        <span class="font-semibold text-gray-700 text-sm">{{ $anotasiCount }}</span> <span class="text-xs text-gray-500">Area</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($isCover || $anotasiCount === 0)
                                        <span class="text-gray-400 text-xs">-</span>
                                    @else
                                        @if($totalActualAreaAudio === $totalExpectedAreaAudio)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-50 text-green-700 rounded text-xs font-semibold border border-green-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Lengkap ({{ $totalActualAreaAudio }}/{{ $totalExpectedAreaAudio }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 text-red-700 rounded text-xs font-semibold border border-red-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                Kurang ({{ $totalActualAreaAudio }}/{{ $totalExpectedAreaAudio }})
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($isCover)
                                        <span class="text-gray-400 text-xs">-</span>
                                    @else
                                        <div class="flex gap-1.5">
                                            <span class="px-2 py-1 rounded text-[10px] font-bold border {{ $narasiId ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}" title="{{ $narasiId ? 'Narasi Indonesia Terisi' : 'Narasi Indonesia Kosong' }}">ID</span>
                                            <span class="px-2 py-1 rounded text-[10px] font-bold border {{ $narasiSu ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}" title="{{ $narasiSu ? 'Narasi Sunda Terisi' : 'Narasi Sunda Kosong' }}">SU</span>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($isCover)
                                        <span class="text-gray-400 text-xs">-</span>
                                    @else
                                        @if($hasBacksound)
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-50 text-green-600 border border-green-200" title="Audio Latar Terisi">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-50 text-red-500 border border-red-200" title="Audio Latar Kosong">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </span>
                                        @endif
                                    @endif
                                </td>

                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if($page->buku->status_publikasi !== 'Terbit')
                                            @if($page->nomor_halaman !== 1)
                                                <a href="{{ route('halaman.edit', [$page->buku, $page->nomor_halaman]) }}"
                                                   class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-xs font-semibold transition-colors">
                                                    Sunting
                                                </a>
                                                <button type="button"
                                                        onclick="confirmSingleDelete('{{ route('halaman.destroy', $page->id_halaman) }}')"
                                                        class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                                    Hapus
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Cover Buku</span>
                                                <button
                                                    type="button"
                                                    onclick="confirmDeleteCover('{{ route("halaman.destroy", $page->id_halaman) }}')"
                                                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                                    Hapus
                                                </button>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400 italic">Buku Terbit (Read-only)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
@endif
<form id="singleDeleteForm" method="POST" action="" class="hidden">
    @csrf
    @method('DELETE')
</form>

{{-- Modal konfirmasi hapus cover --}}
<div id="deleteCoverModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-semibold text-gray-900">Hapus Cover Buku?</h3>
        </div>

        <p class="text-sm text-gray-600 mb-3">
            Tindakan ini akan menghapus <strong>halaman cover (halaman 1)</strong> secara permanen.
        </p>
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-5 text-sm text-amber-800">
            <p class="font-semibold mb-1">⚠️ Perhatian:</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Halaman 2 akan otomatis menjadi <strong>Cover baru (Halaman 1)</strong>.</li>
                <li>Semua <strong>audio</strong> (narasi, backsound, area interaktif) pada halaman 2 akan <strong>dihapus</strong>.</li>
                <li>Semua <strong>anotasi</strong> pada halaman 2 akan <strong>dihapus</strong>.</li>
                <li>Tindakan ini <strong>tidak dapat dibatalkan</strong>.</li>
            </ul>
        </div>

        <form id="deleteCoverForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeCoverModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                    Ya, Hapus Cover
                </button>
            </div>
        </form>
    </div>
</div>

<div id="imageModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50" onclick="closeImageModal()">
    <div class="bg-white rounded-xl shadow-xl p-5 max-w-lg w-full mx-4 max-h-[90vh] overflow-auto" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-3">
            <h3 id="imageModalTitle" class="text-base font-semibold text-gray-800"></h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">×</button>
        </div>
        <img id="imageModalImage" src="" alt="" class="w-full rounded-lg">
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    const flashData = document.getElementById('flash-data');
    if (flashData) {
        const err = flashData.getAttribute('data-error');
        const success = flashData.getAttribute('data-success');
        if (err) {
            ModalAlert.show('alertModal', { title: 'Terjadi Kesalahan', subtitle: err });
        }
        if (success) {
            ModalAlert.show('successModal', { title: 'Berhasil!', subtitle: success });
        }
    }
});
</script>

<script>
function confirmSingleDelete(actionUrl) {
    const form = document.getElementById('singleDeleteForm');
    form.action = actionUrl;
    
    ModalAlert.confirm('globalConfirmModal', {
        title: 'Hapus Halaman',
        subtitle: 'Apakah Anda yakin ingin menghapus halaman ini? Tindakan ini tidak dapat dibatalkan.'
    }, function() {
        ModalAlert.loading('globalLoadingModal');
        form.submit();
    });
}
function showImageModal(src, title) {
    document.getElementById('imageModalImage').src = src;
    document.getElementById('imageModalTitle').textContent = title;
    const modal = document.getElementById('imageModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function confirmDeleteCover(actionUrl) {
    document.getElementById('deleteCoverForm').action = actionUrl;
    const modal = document.getElementById('deleteCoverModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeCoverModal() {
    const modal = document.getElementById('deleteCoverModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

@endsection