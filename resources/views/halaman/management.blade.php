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
    <div id="reorderToast" class="hidden fixed top-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium transition-all"></div>

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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>

                <tbody id="sortableBody" class="divide-y divide-gray-100">
                    @foreach($halaman as $page)
                        @php
                            $audioAreaIndo  = $page->areaInteraktif->whereNotNull('audio_indo')->count();
                            $audioAreaSunda = $page->areaInteraktif->whereNotNull('audio_sunda')->count();
                            $audioCount = ($page->narasi_indo    ? 1 : 0)
                                        + ($page->narasi_sunda   ? 1 : 0)
                                        + ($page->id_audio_latar ? 1 : 0)
                                        + $audioAreaIndo
                                        + $audioAreaSunda;
                            $anotasiCount = $page->areaInteraktif->count();
                        @endphp

                        <tr class="hover:bg-gray-50 transition-colors {{ $page->buku->status_publikasi === 'Terbit' ? '' : 'sortable-row' }}" data-id="{{ $page->id_halaman }}">

                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($page->buku->status_publikasi !== 'Terbit')
                                        <span class="drag-handle text-gray-400 hover:text-gray-600 cursor-grab active:cursor-grabbing select-none text-lg leading-none" title="Seret untuk mengubah urutan">⠿</span>
                                    @endif
                                    <p class="font-semibold text-gray-900 text-sm page-number-label">Halaman {{ $page->nomor_halaman }}</p>
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
                                    {{ $anotasiCount > 0 ? 'bg-orange-100 text-orange-700' : 'bg-orange-50 text-orange-400' }}">
                                    {{ $anotasiCount }}
                                </span>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                @php
                                    $tooltipParts = [
                                        'Narasi ID: '    . ($page->narasi_indo    ? '✓' : '✗'),
                                        'Narasi Sunda: ' . ($page->narasi_sunda   ? '✓' : '✗'),
                                        'Backsound: '    . ($page->id_audio_latar ? '✓' : '✗'),
                                        'Audio Area ID: '    . $audioAreaIndo,
                                        'Audio Area Sunda: ' . $audioAreaSunda,
                                    ];
                                    $tooltip = implode(' | ', $tooltipParts);
                                @endphp
                                <span
                                    class="inline-flex items-center justify-center w-8 h-7 rounded-full text-xs font-bold cursor-default
                                        {{ $audioCount > 0 ? 'bg-blue-100 text-blue-700' : 'bg-blue-50 text-blue-400' }}"
                                    title="{{ $tooltip }}"
                                >
                                    {{ $audioCount }}
                                </span>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $page->created_at->locale('id_ID')->format('d M Y') }}
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($page->buku->status_publikasi !== 'Terbit')
                                        <a href="{{ route('halaman.edit', $page->id_halaman) }}"
                                           class="px-3 py-1.5 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-xs font-semibold transition-colors">
                                            Sunting
                                        </a>
                                        <form method="POST" action="{{ route('halaman.destroy', $page->id_halaman) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                                Hapus
                                            </button>
                                        </form>
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

(function () {
    const tbody = document.getElementById('sortableBody');
    if (!tbody) return;

    let dragRow = null;
    let placeholder = null;

    tbody.addEventListener('mousedown', function (e) {
        const handle = e.target.closest('.drag-handle');
        if (!handle) return;
        dragRow = handle.closest('tr');
        if (!dragRow) return;
        e.preventDefault();

        placeholder = document.createElement('tr');
        placeholder.style.height = dragRow.offsetHeight + 'px';
        placeholder.style.background = '#eff6ff';
        placeholder.style.outline = '2px dashed #93c5fd';
        placeholder.style.outlineOffset = '-2px';
        const td = document.createElement('td');
        td.colSpan = dragRow.cells.length;
        placeholder.appendChild(td);

        dragRow.style.opacity = '0.5';
        dragRow.style.background = '#f0f9ff';
        dragRow.classList.add('pointer-events-none');

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    });

    function onMouseMove(e) {
        if (!dragRow) return;
        const rows = [...tbody.querySelectorAll('tr.sortable-row:not(.pointer-events-none)')];
        let targetRow = null;
        for (const row of rows) {
            const rect = row.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) { targetRow = row; break; }
        }
        if (placeholder.parentNode) placeholder.parentNode.removeChild(placeholder);
        targetRow ? tbody.insertBefore(placeholder, targetRow) : tbody.appendChild(placeholder);
    }

    function onMouseUp() {
        if (!dragRow) return;
        if (placeholder.parentNode) {
            tbody.insertBefore(dragRow, placeholder);
            placeholder.parentNode.removeChild(placeholder);
        }
        dragRow.style.opacity = '';
        dragRow.style.background = '';
        dragRow.classList.remove('pointer-events-none');

        const rows = [...tbody.querySelectorAll('tr.sortable-row')];
        rows.forEach((row, i) => {
            const label = row.querySelector('.page-number-label');
            if (label) label.textContent = 'Halaman ' + (i + 1);
        });

        saveOrder(rows.map(r => r.dataset.id));
        dragRow = null;
        placeholder = null;
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    function saveOrder(ids) {
        showToast('Menyimpan urutan...', 'info');
        fetch('{{ route('halaman.reorder') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ halaman: ids }),
        })
        .then(r => r.json())
        .then(data => showToast(data.success ? '✓ Urutan berhasil disimpan' : '✗ Gagal menyimpan', data.success ? 'success' : 'error'))
        .catch(() => showToast('✗ Gagal menyimpan urutan', 'error'));
    }

    function showToast(msg, type) {
        const toast = document.getElementById('reorderToast');
        if (!toast) return;
        const styles = {
            info:    'bg-blue-50 text-blue-700 border border-blue-200',
            success: 'bg-green-50 text-green-700 border border-green-200',
            error:   'bg-red-50 text-red-700 border border-red-200',
        };
        toast.className = 'fixed top-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium ' + (styles[type] || styles.info);
        toast.textContent = msg;
        toast.classList.remove('hidden');
        clearTimeout(toast._timeout);
        if (type !== 'info') toast._timeout = setTimeout(() => toast.classList.add('hidden'), 3000);
    }
})();
</script>

@endsection