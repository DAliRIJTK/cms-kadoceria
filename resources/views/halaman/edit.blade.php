@extends('layouts.dashboard')

@section('content')

<div class="mb-4">
    <a href="{{ route('halaman.management', ['id_buku' => $halaman->buku->id_buku]) }}"
       class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
        ← Kembali ke Daftar Halaman
    </a>
</div>

{{-- Alerts --}}
@if (session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 flex gap-3">
        <span class="text-xl">✅</span>
        <p class="text-green-800 font-medium self-center">{{ session('success') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <div class="flex gap-3">
            <span class="text-xl">⚠️</span>
            <div>
                <h3 class="font-semibold text-red-800 mb-1">Terjadi Kesalahan</h3>
                <ul class="text-sm text-red-700 space-y-0.5 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

{{-- Header --}}
<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <div>
            {{-- Judul buku sekarang ditampilkan sebagai button/badge --}}
            <a href="{{ route('halaman.management', ['id_buku' => $halaman->buku->id_buku]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 mb-2 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 rounded-lg text-xs font-semibold transition-colors">
                📖 {{ $halaman->buku->judul_idn }}
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Halaman {{ $halaman->nomor_halaman }}</h1>
            <p class="text-gray-500 mt-0.5 text-sm">Kelola anotasi dan audio halaman</p>
        </div>
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        <button id="saveAreaBtn" type="button"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm">
            💾 Simpan Area Baru
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── KIRI: Gambar + Canvas ── --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-base font-bold text-gray-900 mb-1">Area Interaktif</h2>
            <p class="text-gray-400 text-xs mb-4">Klik dan drag di halaman untuk membuat area interaktif, lalu isi label dan unggah audio</p>

            <div class="relative rounded-lg overflow-hidden border border-gray-200 bg-gray-50 select-none" id="imageWrapper">
                @if($halaman->path_gambar && file_exists(storage_path('app/public/' . $halaman->path_gambar)))
                    <img id="pageImage"
                         src="{{ asset('storage/' . $halaman->path_gambar) }}"
                         alt="Halaman {{ $halaman->nomor_halaman }}"
                         class="w-full block"
                         draggable="false">
                    <canvas id="drawCanvas"
                            class="absolute inset-0 w-full h-full cursor-crosshair"
                            style="touch-action: none;"></canvas>
                    {{-- Existing area overlays --}}
                    @foreach($halaman->areaInteraktif as $area)
                        <div class="absolute border-2 border-red-500 bg-red-500/10 pointer-events-none area-overlay"
                             data-id="{{ $area->id_area }}"
                             style="left:{{ $area->x_pct ?? 0 }}%; top:{{ $area->y_pct ?? 0 }}%; width:{{ $area->w_pct ?? 0 }}%; height:{{ $area->h_pct ?? 0 }}%;">
                            <span class="absolute -top-5 left-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded whitespace-nowrap max-w-[120px] truncate">
                                {{ $area->label ?? 'Area ' . $loop->iteration }}
                            </span>
                        </div>
                    @endforeach
                @else
                    <div class="w-full aspect-[3/4] bg-gray-100 flex items-center justify-center rounded-lg">
                        <span class="text-3xl font-bold text-gray-400">{{ $halaman->nomor_halaman }}</span>
                    </div>
                @endif
            </div>

            {{-- [DIPINDAH] Navigasi halaman sebelumnya / berikutnya, sekarang di bawah gambar --}}
            <div class="flex items-center justify-between gap-2 mt-5 pt-4 border-t border-gray-100">
                @if(isset($prevHalaman) && $prevHalaman)
                    <a href="{{ route('halaman.edit', $prevHalaman->id_halaman) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-sm transition-colors border border-gray-200"
                       title="Halaman {{ $prevHalaman->nomor_halaman }}">
                        ‹ Hal. {{ $prevHalaman->nomor_halaman }}
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-50 text-gray-300 rounded-lg font-semibold text-sm border border-gray-100 cursor-not-allowed">
                        ‹ Sebelumnya
                    </span>
                @endif

                <span class="text-xs font-medium text-gray-400">Halaman {{ $halaman->nomor_halaman }}</span>

                @if(isset($nextHalaman) && $nextHalaman)
                    <a href="{{ route('halaman.edit', $nextHalaman->id_halaman) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-sm transition-colors border border-gray-200"
                       title="Halaman {{ $nextHalaman->nomor_halaman }}">
                        Hal. {{ $nextHalaman->nomor_halaman }} ›
                    </a>
                @else
                    <span class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-50 text-gray-300 rounded-lg font-semibold text-sm border border-gray-100 cursor-not-allowed">
                        Berikutnya ›
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- ── KANAN: Daftar area + Audio Halaman ── --}}
    <div class="space-y-5">
            {{-- Area Interaktif Panel --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                {{-- Label input (muncul setelah drag) --}}
            <div id="labelInputArea" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs font-bold text-blue-800 mb-2">Beri label untuk area baru</p>
                <div class="flex gap-2">
                    <input type="text" id="newAreaLabel"
                            placeholder="Contoh: Mata, Telinga, Pohon..."
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <button type="button" id="cancelAreaBtn"
                            class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                        Batal
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-1">Tekan "Simpan Area Baru" di atas setelah mengisi label.</p>
            </div>
            <br>
            <h2 class="text-base font-bold text-gray-900 mb-4">
                Area Interaktif (<span id="areaCount">{{ $halaman->areaInteraktif()->count() }}</span>)
            </h2>

            @if($halaman->areaInteraktif()->count() > 0)
                <div class="space-y-4" id="areaList">
                    @foreach($halaman->areaInteraktif as $area)
                        @include('halaman.area-item', ['area' => $area, 'loop' => $loop])
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200" id="emptyAreaMsg">
                    <p class="text-gray-400 text-sm">Belum ada area interaktif.</p>
                    <p class="text-gray-400 text-xs mt-1">Klik dan drag pada gambar halaman untuk membuat area.</p>
                </div>
                <div class="space-y-4 hidden" id="areaList"></div>
            @endif
        </div>

        {{-- Audio Halaman --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-base font-bold text-gray-900 mb-5">Audio Halaman</h2>

            <div class="space-y-4">

                {{-- Narasi Indonesia --}}
                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm font-bold text-gray-800 mb-2">Narasi - Bahasa Indonesia</p>
                    @if($halaman->narasi_indo)
                        <div class="mb-2 flex items-center gap-2">
                            <audio controls class="flex-1 h-8"
                                   src="{{ asset('storage/' . $halaman->narasi_indo) }}"></audio>
                        </div>
                        {{-- [FIX #3] Label unggah berbeda jika sudah ada audio --}}
                        <p class="text-xs text-yellow-600 font-medium mb-1">⚠️ Mengunggah file baru akan menggantikan audio yang ada</p>
                    @endif
                    <form action="{{ route('halaman.storeNarasi', $halaman->id_halaman) }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="narasi_type" value="indo">
                        <div class="flex gap-2">
                            <label class="flex-shrink-0 px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium cursor-pointer hover:bg-gray-50 transition-colors">
                                {{ $halaman->narasi_indo ? 'Ganti File' : 'Pilih File' }}
                                <input type="file" name="audio_file" accept=".mp3,.m4a"
                                       required class="hidden" onchange="updateFileName(this)">
                            </label>
                            <span class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-400 truncate self-center file-name-display">
                                Belum ada file dipilih
                            </span>
                            <button type="submit"
                                    class="px-3 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold transition-colors">
                                Unggah
                            </button>
                            @if($halaman->narasi_indo)
                                <form action="{{ route('halaman.deleteNarasi', $halaman->id_halaman) }}"
                                      method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="narasi_type" value="indo">
                                    <button type="submit"
                                            class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A</p>
                    </form>
                </div>

                {{-- Narasi Sunda --}}
                <div class="border-l-4 border-purple-500 pl-4">
                    <p class="text-sm font-bold text-gray-800 mb-2">Narasi - Bahasa Sunda</p>
                    @if($halaman->narasi_sunda)
                        <div class="mb-2 flex items-center gap-2">
                            <audio controls class="flex-1 h-8"
                                   src="{{ asset('storage/' . $halaman->narasi_sunda) }}"></audio>
                        </div>
                        {{-- [FIX #3] Label unggah berbeda jika sudah ada audio --}}
                        <p class="text-xs text-yellow-600 font-medium mb-1">⚠️ Mengunggah file baru akan menggantikan audio yang ada</p>
                    @endif
                    <form action="{{ route('halaman.storeNarasi', $halaman->id_halaman) }}"
                          method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="narasi_type" value="sunda">
                        <div class="flex gap-2">
                            <label class="flex-shrink-0 px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium cursor-pointer hover:bg-gray-50 transition-colors">
                                {{ $halaman->narasi_sunda ? 'Ganti File' : 'Pilih File' }}
                                <input type="file" name="audio_file" accept=".mp3,.m4a"
                                       required class="hidden" onchange="updateFileName(this)">
                            </label>
                            <span class="flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-400 truncate self-center file-name-display">
                                Belum ada file dipilih
                            </span>
                            <button type="submit"
                                    class="px-3 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-semibold transition-colors">
                                Unggah
                            </button>
                            @if($halaman->narasi_sunda)
                                <form action="{{ route('halaman.deleteNarasi', $halaman->id_halaman) }}"
                                      method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="narasi_type" value="sunda">
                                    <button type="submit"
                                            class="px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A</p>
                    </form>
                </div>

                {{-- Backsound — menggunakan relasi AudioLatar --}}
                <div class="border-l-4 border-yellow-500 pl-4">
                    <p class="text-sm font-bold text-gray-800 mb-2">Backsound Halaman</p>

                    {{-- Tampilkan audio aktif jika ada --}}
                    @if($halaman->audioLatar)
                        <div class="mb-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-yellow-800 truncate">
                                    🎵 {{ $halaman->audioLatar->nama_audio }}
                                </p>
                                <audio controls class="w-full h-7 mt-1"
                                       src="{{ asset('storage/' . $halaman->audioLatar->path_file) }}"></audio>
                            </div>
                            {{-- Hapus: lepas relasi saja (set id_audio_latar = null) --}}
                            <form action="{{ route('halaman.removeBacksound', $halaman->id_halaman) }}"
                                  method="POST" class="flex-shrink-0">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Pilih dari daftar AudioLatar yang sudah ada --}}
                    <form action="{{ route('halaman.setBacksound', $halaman->id_halaman) }}"
                          method="POST">
                        @csrf @method('PATCH')
                        <div class="flex gap-2">
                            <select name="id_audio_latar"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-yellow-400 bg-white"
                                    required>
                                <option value="">-- Pilih backsound --</option>
                                @foreach($allAudioLatar as $al)
                                    <option value="{{ $al->id_audio_latar }}"
                                        {{ $halaman->id_audio_latar == $al->id_audio_latar ? 'selected' : '' }}>
                                        {{ $al->nama_audio }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit"
                                    class="px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-xs font-semibold transition-colors">
                                Atur
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            Pilih dari daftar audio latar yang tersedia.
                            <a href="{{ route('audio-latar.index') }}"
                               class="text-yellow-600 hover:underline">
                                + Tambah audio latar baru
                            </a>
                        </p>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
// ── File name display ─────────────────────────────────────────────────────────
function updateFileName(input) {
    const span = input.closest('label').parentElement.querySelector('.file-name-display');
    if (span && input.files.length > 0) {
        span.textContent = input.files[0].name;
        span.classList.remove('text-gray-400');
        span.classList.add('text-gray-700');
    }
}

// ── Canvas drag-to-draw ───────────────────────────────────────────────────────
(function () {
    const img    = document.getElementById('pageImage');
    const canvas = document.getElementById('drawCanvas');
    if (!canvas || !img) return;

    const ctx = canvas.getContext('2d');
    let drawing = false, startX, startY, currentRect = null;

    function syncCanvas() {
        canvas.width  = img.offsetWidth;
        canvas.height = img.offsetHeight;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    window.addEventListener('resize', syncCanvas);
    img.addEventListener('load', syncCanvas);
    if (img.complete) syncCanvas();

    function getPos(e) {
        const rect    = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return {
            x: (clientX - rect.left) * (canvas.width  / rect.width),
            y: (clientY - rect.top)  * (canvas.height / rect.height),
        };
    }

    canvas.addEventListener('mousedown',  onStart);
    canvas.addEventListener('touchstart', onStart, { passive: false });

    function onStart(e) {
        e.preventDefault();
        drawing = true;
        const pos = getPos(e);
        startX = pos.x; startY = pos.y;
        currentRect = null;
        hideLabelInput();
    }

    canvas.addEventListener('mousemove',  onMove);
    canvas.addEventListener('touchmove',  onMove, { passive: false });

    function onMove(e) {
        if (!drawing) return;
        e.preventDefault();
        const pos = getPos(e);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = '#ef4444';
        ctx.lineWidth   = 2;
        ctx.fillStyle   = 'rgba(239,68,68,0.1)';
        ctx.strokeRect(startX, startY, pos.x - startX, pos.y - startY);
        ctx.fillRect(startX, startY, pos.x - startX, pos.y - startY);
    }

    canvas.addEventListener('mouseup',  onEnd);
    canvas.addEventListener('touchend', onEnd);

    function onEnd(e) {
        if (!drawing) return;
        drawing = false;
        const rect = canvas.getBoundingClientRect();
        const pos  = e.changedTouches
            ? { x: (e.changedTouches[0].clientX - rect.left) * (canvas.width  / rect.width),
                y: (e.changedTouches[0].clientY - rect.top)  * (canvas.height / rect.height) }
            : getPos(e);

        const w = pos.x - startX, h = pos.y - startY;
        if (Math.abs(w) < 10 || Math.abs(h) < 10) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        currentRect = {
            x: w < 0 ? pos.x : startX,
            y: h < 0 ? pos.y : startY,
            w: Math.abs(w), h: Math.abs(h),
            xPct: ((w < 0 ? pos.x : startX) / canvas.width  * 100).toFixed(4),
            yPct: ((h < 0 ? pos.y : startY) / canvas.height * 100).toFixed(4),
            wPct: (Math.abs(w) / canvas.width  * 100).toFixed(4),
            hPct: (Math.abs(h) / canvas.height * 100).toFixed(4),
        };
        showLabelInput();
    }

    function showLabelInput() {
        document.getElementById('labelInputArea').classList.remove('hidden');
        document.getElementById('newAreaLabel').focus();
    }
    function hideLabelInput() {
        document.getElementById('labelInputArea').classList.add('hidden');
        document.getElementById('newAreaLabel').value = '';
    }

    document.getElementById('cancelAreaBtn').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        currentRect = null;
        hideLabelInput();
    });

    // ── Simpan area baru ──────────────────────────────────────────────────────
    document.getElementById('saveAreaBtn').addEventListener('click', function () {
        if (!currentRect) {
            alert('Silakan gambar area terlebih dahulu dengan klik dan drag pada gambar.');
            return;
        }
        const label = document.getElementById('newAreaLabel').value.trim();
        if (!label) {
            const inp = document.getElementById('newAreaLabel');
            inp.focus();
            inp.classList.add('ring-2', 'ring-red-400');
            setTimeout(() => inp.classList.remove('ring-2', 'ring-red-400'), 1500);
            return;
        }

        const btn = this;
        btn.disabled    = true;
        btn.textContent = 'Menyimpan...';

        fetch('{{ route('halaman.storeAreaInteraktif') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                id_halaman   : {{ $halaman->id_halaman }},
                label        : label,
                x_pct        : currentRect.xPct,
                y_pct        : currentRect.yPct,
                w_pct        : currentRect.wPct,
                h_pct        : currentRect.hPct,
                x            : Math.round(currentRect.x),
                y            : Math.round(currentRect.y),
                lebar_area   : Math.round(currentRect.w),
                panjang_area : Math.round(currentRect.h),
            }),
        })
        .then(r => r.json())
        .then(data => {
        if (data.success) {
            appendAreaCard(data.area);
            appendAreaOverlay(data.area, label);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            currentRect = null;
            hideLabelInput();
            updateCount(1);
        } else {
                alert('Gagal menyimpan: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => alert('Error: ' + err.message))
        .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = '💾 Simpan Area Baru';
        });
    });

    // ── Helpers ───────────────────────────────────────────────────────────────

    function updateCount(delta) {
        const el = document.getElementById('areaCount');
        el.textContent = parseInt(el.textContent) + delta;
        const empty = document.getElementById('emptyAreaMsg');
        if (empty) empty.classList.add('hidden');
        document.getElementById('areaList').classList.remove('hidden');
    }

    function appendAreaCard(area) {
        const list  = document.getElementById('areaList');
        const index = list.children.length + 1;
        list.insertAdjacentHTML('beforeend', buildAreaCard(area, index));
        const newCard = list.lastElementChild;
        newCard.querySelector('.btn-delete-area').addEventListener('click', handleDeleteArea);
        newCard.querySelectorAll('input[type="file"]').forEach(inp => {
            inp.addEventListener('change', function () { updateFileName(this); });
        });
    }

    function buildAreaCard(area, index) {
        const label       = area.label || ('Area ' + index);
        const aId         = area.id_area;
        const uploadRoute = `{{ url('area-interaktif') }}/${aId}/audio`;
        const deleteRoute = `{{ url('area-interaktif') }}/${aId}`;
        const csrf        = '{{ csrf_token() }}';

        return `
        <div class="rounded-xl border border-gray-200 p-4" id="area-card-${aId}">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <p class="font-bold text-gray-900 text-sm">${label}</p>
                    <p class="text-xs text-gray-400">Posisi: (${area.x ?? '—'}, ${area.y ?? '—'}) – Ukuran: ${area.lebar_area ?? '—'}×${area.panjang_area ?? '—'}px</p>
                </div>
                <button type="button"
                        class="btn-delete-area w-9 h-9 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm"
                        data-id="${aId}" data-route="${deleteRoute}" data-csrf="${csrf}">🗑️</button>
            </div>
            <div class="bg-blue-50 rounded-lg p-3 mb-2 border border-blue-100">
                <p class="text-xs font-bold text-blue-800 mb-2">Audio Objek - Bahasa Indonesia</p>
                <form action="${uploadRoute}" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="${csrf}">
                    <input type="hidden" name="audio_type" value="indo">
                    <div class="flex gap-2">
                        <label class="flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium cursor-pointer hover:bg-gray-50 transition-colors">
                            Pilih File<input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden">
                        </label>
                        <span class="flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate self-center file-name-display">Belum ada file dipilih</span>
                        <button type="submit" class="px-3 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold transition-colors">Unggah</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A</p>
                </form>
            </div>
            <div class="bg-purple-50 rounded-lg p-3 border border-purple-100">
                <p class="text-xs font-bold text-purple-800 mb-2">Audio Objek - Bahasa Sunda</p>
                <form action="${uploadRoute}" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="${csrf}">
                    <input type="hidden" name="audio_type" value="sunda">
                    <div class="flex gap-2">
                        <label class="flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium cursor-pointer hover:bg-gray-50 transition-colors">
                            Pilih File<input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden">
                        </label>
                        <span class="flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate self-center file-name-display">Belum ada file dipilih</span>
                        <button type="submit" class="px-3 py-1.5 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-semibold transition-colors">Unggah</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A</p>
                </form>
            </div>
        </div>`;
    }

    function appendAreaOverlay(area, label) {
        const wrapper = document.getElementById('imageWrapper');
        const div = document.createElement('div');
        div.className = 'absolute border-2 border-red-500 bg-red-500/10 pointer-events-none area-overlay';
        div.dataset.id = area.id_area;
        div.style.left   = (area.x_pct ?? 0) + '%';
        div.style.top    = (area.y_pct ?? 0) + '%';
        div.style.width  = (area.w_pct ?? 0) + '%';
        div.style.height = (area.h_pct ?? 0) + '%';
        div.innerHTML = `<span class="absolute -top-5 left-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded whitespace-nowrap max-w-[120px] truncate">${label}</span>`;
        wrapper.appendChild(div);
    }

    document.querySelectorAll('.btn-delete-area').forEach(btn => {
        btn.addEventListener('click', handleDeleteArea);
    });

    function handleDeleteArea() {
        if (!confirm('Hapus area ini beserta audionya?')) return;
        const aId   = this.dataset.id;
        const route = this.dataset.route;
        const csrf  = this.dataset.csrf;

        fetch(route, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ _method: 'DELETE' }),
        })
        .then(r => r.json())
        .then(data => {
        if (data.success) {
            document.getElementById('area-card-' + aId)?.remove();
            document.querySelector('.area-overlay[data-id="' + aId + '"]')?.remove();
            updateCount(-1);
        } else {
                alert('Gagal menghapus: ' + (data.message || 'Unknown'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }

    document.querySelectorAll('input[type="file"]').forEach(inp => {
        inp.addEventListener('change', function () { updateFileName(this); });
    });

})();
</script>

@endsection