@extends('layouts.dashboard')

@section('content')

<div class="mb-6">
    <a href="{{ route('buku.show', request('id_buku')) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
        ← Kembali ke Halaman Buku
    </a>
</div>

{{-- Header --}}
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Kelola Halaman</h1>
    <p class="text-gray-500 mt-1">Kelola semua halaman dari buku cerita dwibahasa</p>
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Halaman</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Buku</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pratinjau</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Anotasi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Audio</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @foreach($halaman as $page)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    {{-- Drag handle --}}
                                    <span class="text-gray-300 cursor-grab select-none text-lg leading-none">⠿</span>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-sm">Halaman {{ $page->nomor_halaman }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <a href="{{ route('buku.show', $page->buku->id_buku) }}"
                                   class="text-blue-600 hover:text-blue-700 hover:underline font-semibold text-sm leading-tight block">
                                    {{ $page->buku->judul_idn }}
                                </a>
                                <p class="text-xs text-gray-400">{{ $page->buku->penulis ?? '-' }}</p>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($page->path_gambar && file_exists(storage_path('app/public/' . $page->path_gambar)))
                                    <img
                                        src="{{ asset('storage/' . $page->path_gambar) }}"
                                        alt="Halaman {{ $page->nomor_halaman }}"
                                        class="h-14 w-10 object-cover rounded border border-gray-200 cursor-pointer hover:shadow-md transition-shadow"
                                        onclick="showImageModal('{{ asset('storage/' . $page->path_gambar) }}', 'Halaman {{ $page->nomor_halaman }}')"
                                    >
                                @else
                                    <div class="h-14 w-10 bg-gray-100 rounded border border-gray-200 flex items-center justify-center">
                                        <span class="text-xs text-gray-400">-</span>
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-8 h-7 rounded-full text-xs font-bold
                                    {{ $page->areaInteraktif()->count() > 0 ? 'bg-orange-100 text-orange-700' : 'bg-orange-50 text-orange-400' }}">
                                    {{ $page->areaInteraktif()->count() }}
                                </span>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                {{-- Count audio: narasi_indo + narasi_sunda --}}
                                @php
                                    $audioCount = ($page->narasi_indo ? 1 : 0) + ($page->narasi_sunda ? 1 : 0);
                                @endphp
                                <span class="inline-flex items-center justify-center w-8 h-7 rounded-full text-xs font-bold
                                    {{ $audioCount > 0 ? 'bg-orange-100 text-orange-700' : 'bg-orange-50 text-orange-400' }}">
                                    {{ $audioCount }}
                                </span>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $page->buku->status_publikasi === 'Terbit' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $page->buku->status_publikasi ?? 'Draft' }}
                                </span>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $page->created_at->locale('id_ID')->format('d M Y') }}
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('halaman.edit', $page->id_halaman) }}"
                                       class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-xs font-semibold transition-colors">
                                        Sunting
                                    </a>
                                    <form method="POST" action="{{ route('halaman.destroy', $page->id_halaman) }}" class="inline"
                                          onsubmit="return confirm('Hapus halaman ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($halaman->hasPages())
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    Menampilkan {{ $halaman->firstItem() }} – {{ $halaman->lastItem() }} dari {{ $halaman->total() }} halaman
                </p>
                <div>{{ $halaman->links('pagination::tailwind') }}</div>
            </div>
        @endif
    </div>
@endif

{{-- Image Modal --}}
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
</script>

@endsection