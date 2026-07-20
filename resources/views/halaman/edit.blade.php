@extends('layouts.dashboard')

@section('content')

<div class="mb-4">
    <a href="{{ route('halaman.management', ['id_buku' => $halaman->buku->id_buku]) }}"
       class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
        ← Kembali ke Daftar Halaman
    </a>
</div>

<x-modal-alert id="alertModal" type="error" />
<x-modal-alert id="successModal" type="success" />

<div id="flash-data" 
     data-error="{{ $errors->any() ? $errors->first() : '' }}"
     data-success="{{ session('success') }}">
</div>

{{-- Header --}}
<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Halaman {{ $halaman->nomor_halaman - 1 }}</h1>
            <p class="text-gray-500 mt-0.5 text-sm">Kelola anotasi dan audio halaman</p>
        </div>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── KIRI: Gambar + Canvas ── --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            @if($halaman->nomor_halaman !== 1)
                <h2 class="text-base font-bold text-gray-900 mb-1">Area Interaktif</h2>
                @if($halaman->buku->status_publikasi === 'Terbit')
                    <p class="text-gray-400 text-xs mb-4">Daftar area interaktif yang terdapat pada halaman ini.</p>
                @else
                    <p class="text-gray-400 text-xs mb-4">Klik dan drag di halaman untuk membuat area interaktif, lalu isi label dan unggah audio</p>
                @endif
            @else
                <h2 class="text-base font-bold text-gray-900 mb-1">Halaman Cover</h2>
                <p class="text-gray-400 text-xs mb-4">Pratinjau halaman cover buku.</p>
            @endif

            <div class="relative rounded-lg overflow-hidden border border-gray-200 bg-gray-50 select-none" id="imageWrapper">
                @if($halaman->path_gambar)
                    <img id="pageImage"
                         src="{{ Storage::disk(config('filesystems.default'))->url($halaman->path_gambar) }}"
                         alt="Halaman {{ $halaman->nomor_halaman }}"
                         class="w-full block"
                         draggable="false">
                    @if($halaman->nomor_halaman !== 1)
                    <canvas id="drawCanvas"
                            class="absolute inset-0 w-full h-full {{ $halaman->buku->status_publikasi === 'Terbit' ? 'cursor-default' : 'cursor-crosshair' }}"
                            style="touch-action: none;"></canvas>
                    {{-- Existing area overlays --}}
                    @foreach($halaman->areaInteraktif as $area)
                        <div class="absolute border-2 border-red-500 bg-red-500/10 pointer-events-none area-overlay"
                             data-id="{{ $area->id_area }}"
                             @style([
                                 "left: " . ($area->x_pct ?? 0) . "%",
                                 "top: " . ($area->y_pct ?? 0) . "%",
                                 "width: " . ($area->w_pct ?? 0) . "%",
                                 "height: " . ($area->h_pct ?? 0) . "%",
                             ])>
                            <span class="absolute -top-5 left-0 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded whitespace-nowrap max-w-[120px] truncate">
                                {{ $area->label ?? 'Area ' . $loop->iteration }}
                            </span>
                        </div>
                    @endforeach
                    @endif
                @else
                    <div class="w-full aspect-[3/4] bg-gray-100 flex items-center justify-center rounded-lg">
                        <span class="text-3xl font-bold text-gray-400">{{ $halaman->nomor_halaman }}</span>
                    </div>
                @endif
            </div>

            {{-- Navigasi halaman sebelumnya / berikutnya --}}
            <div class="flex items-center justify-between gap-2 mt-5 pt-4 border-t border-gray-100">
                @if(isset($prevHalaman) && $prevHalaman)
                    <a href="{{ route('halaman.edit', [$prevHalaman->buku, $prevHalaman->nomor_halaman]) }}"
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
                    <a href="{{ route('halaman.edit', [$nextHalaman->buku, $nextHalaman->nomor_halaman]) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-sm transition-colors border border-gray-200"
                       title="Halaman {{ $nextHalaman->nomor_halaman }}">
                        Hal. {{ $nextHalaman->nomor_halaman }} ›
                    </a>
                @else
                    <a href="{{ route('halaman.management', ['id_buku' => $halaman->buku->id_buku]) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-sm"
                       title="Selesai Sunting">
                        Selesai
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── KANAN: Daftar area + Audio Halaman ── --}}
    <div class="space-y-5">
        {{-- Audio Halaman --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="text-base font-bold text-gray-900 mb-5">Audio Halaman</h2>

            <div class="space-y-4">

                {{-- Narasi Indonesia --}}
                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm font-bold text-gray-800 mb-2">Narasi - Bahasa Indonesia</p>
                    
                    <div class="mb-2 flex items-center gap-2 {{ !$halaman->narasi_indo ? 'hidden' : '' }}" id="audio-player-narasi-indo">
                        <audio controls class="flex-1 h-8"
                               src="{{ $halaman->narasi_indo ? Storage::disk('s3')->url($halaman->narasi_indo) : '' }}"></audio>
                    </div>

                    @if($halaman->buku->status_publikasi !== 'Terbit')
                        {{-- Hidden delete form --}}
                        <form id="delete-narasi-indo-form" action="{{ route('halaman.deleteNarasi', $halaman->id_halaman) }}"
                              method="POST" class="hidden">
                            @csrf @method('DELETE')
                            <input type="hidden" name="narasi_type" value="indo">
                        </form>

                        {{-- Auto-upload area: Narasi Indo --}}
                        <div class="audio-upload-zone" 
                             data-url="{{ route('halaman.storeNarasi', $halaman->id_halaman) }}"
                             data-extra='{"narasi_type":"indo"}'
                             data-player-target="audio-player-narasi-indo"
                             data-has-audio="{{ $halaman->narasi_indo ? '1' : '0' }}">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <span class="upload-label flex-shrink-0 px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium group-hover:bg-gray-50 transition-colors">
                                    {{ $halaman->narasi_indo ? 'Ganti File' : 'Pilih File' }}
                                </span>
                                <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden auto-upload-input">
                                <span class="upload-filename flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-400 truncate">
                                    Belum ada file dipilih
                                </span>
                                <button type="submit" form="delete-narasi-indo-form" id="delete-btn-narasi-indo"
                                class="mt-2 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors {{ !$halaman->narasi_indo ? 'hidden' : '' }}">
                                    Hapus
                                </button>
                            </label>
                            <div class="upload-status mt-1.5 hidden text-xs font-medium"></div>
                            <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A</p>
                        </div>
                    @endif
                </div>

                {{-- Narasi Sunda --}}
                <div class="border-l-4 border-purple-500 pl-4">
                    <p class="text-sm font-bold text-gray-800 mb-2">Narasi - Bahasa Sunda</p>
                    
                    <div class="mb-2 flex items-center gap-2 {{ !$halaman->narasi_sunda ? 'hidden' : '' }}" id="audio-player-narasi-sunda">
                        <audio controls class="flex-1 h-8"
                               src="{{ $halaman->narasi_sunda ? Storage::disk('s3')->url($halaman->narasi_sunda) : '' }}"></audio>
                    </div>

                    @if($halaman->buku->status_publikasi !== 'Terbit')
                        {{-- Hidden delete form --}}
                        <form id="delete-narasi-sunda-form" action="{{ route('halaman.deleteNarasi', $halaman->id_halaman) }}"
                              method="POST" class="hidden">
                            @csrf @method('DELETE')
                            <input type="hidden" name="narasi_type" value="sunda">
                        </form>

                        {{-- Auto-upload area: Narasi Sunda --}}
                        <div class="audio-upload-zone"
                             data-url="{{ route('halaman.storeNarasi', $halaman->id_halaman) }}"
                             data-extra='{"narasi_type":"sunda"}'
                             data-player-target="audio-player-narasi-sunda"
                             data-has-audio="{{ $halaman->narasi_sunda ? '1' : '0' }}">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <span class="upload-label flex-shrink-0 px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium group-hover:bg-gray-50 transition-colors">
                                    {{ $halaman->narasi_sunda ? 'Ganti File' : 'Pilih File' }}
                                </span>
                                <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden auto-upload-input">
                                <span class="upload-filename flex-1 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-400 truncate">
                                    Belum ada file dipilih
                                </span>
                                <button type="submit" form="delete-narasi-sunda-form" id="delete-btn-narasi-sunda"
                                class="mt-2 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors {{ !$halaman->narasi_sunda ? 'hidden' : '' }}">
                                    Hapus
                                </button>
                            </label>
                            <div class="upload-status mt-1.5 hidden text-xs font-medium"></div>
                            <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A</p>
                        </div>


                    @endif
                </div>

                {{-- Backsound — menggunakan relasi AudioLatar --}}
                @if($halaman->nomor_halaman !== 1)
                <div class="border-l-4 border-yellow-500 pl-4">
                    <p class="text-sm font-bold text-gray-800 mb-2">Halaman Audio Latar</p>
 
                    {{-- Tampilkan audio aktif jika ada --}}
                    @if($halaman->audioLatar)
                        <div class="mb-3 p-3 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <audio controls class="w-full h-7 mt-1"
                                       src="{{ $halaman->audioLatar && $halaman->audioLatar->path_file ? Storage::disk(config('filesystems.default'))->url($halaman->audioLatar->path_file) : '' }}"></audio>
                            </div>
                            {{-- Hapus: lepas relasi saja (set id_audio_latar = null) --}}
                            @if($halaman->buku->status_publikasi !== 'Terbit')
                                <form action="{{ route('halaman.removeBacksound', $halaman->id_halaman) }}"
                                      method="POST" class="flex-shrink-0">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        @if($halaman->buku->status_publikasi === 'Terbit')
                            <p class="text-xs text-gray-400 italic mb-2">Belum ada Audio Latar.</p>
                        @endif
                    @endif
 
                    {{-- Pilih dari daftar AudioLatar yang sudah ada --}}
                    @if($halaman->buku->status_publikasi !== 'Terbit')
                        <form action="{{ route('halaman.setBacksound', $halaman->id_halaman) }}"
                              method="POST">
                            @csrf @method('PATCH')
                            <div class="flex gap-2">
                                <select name="id_audio_latar"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-yellow-400 bg-white"
                                        required>
                                    <option value="">-- Pilih Audio Latar --</option>
                                    @foreach($allAudioLatar as $al)
                                        <option value="{{ $al->id_audio_latar }}"
                                            {{ $halaman->id_audio_latar == $al->id_audio_latar ? 'selected' : '' }}>
                                            {{ $al->nama_audio }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                        class="px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-xs font-semibold transition-colors">
                                    Pilih
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">
                                Pilih dari daftar audio latar yang tersedia.
                                <a href="{{ route('audio-latar.index', ['ref' => url()->current()]) }}"
                                   class="text-yellow-600 hover:underline">
                                    + Tambah audio latar baru
                                </a>
                            </p>
                        </form>
                    @endif
                </div>
                @endif
        </div>
    </div>

    @if($halaman->nomor_halaman !== 1)
        {{-- Area Interaktif Panel --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            {{-- Label input (muncul setelah drag) --}}
            <div id="labelInputArea" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs font-bold text-blue-800 mb-2">Beri label untuk area baru</p>
                <div class="flex gap-2">
                    <input type="text" id="newAreaLabel"
                            placeholder="Contoh: Mata, Telinga, Pohon..."
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <button id="saveAreaBtn" type="button"
                            class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                        Simpan
                    </button>
                    <button type="button" id="cancelAreaBtn"
                            class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                        Batal
                    </button>
                </div>
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
    @endif
</div>

<script>
// ═══════════════════════════════════════════════════════════════════════════════
// AUTO-UPLOAD ENGINE
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Renders an inline confirmation warning inside the upload zone.
 */
function showOverwriteConfirm(zone, onConfirm, onCancel) {
    // Remove any existing inline confirms in this zone
    zone.querySelector('.confirm-overwrite-inline')?.remove();

    const confirmDiv = document.createElement('div');
    confirmDiv.className = 'confirm-overwrite-inline mt-2.5 p-2.5 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center justify-between gap-3 text-xs';
    confirmDiv.innerHTML = `
        <span class="text-yellow-800 font-medium flex items-center gap-1">⚠️ Audio sudah ada. Timpa?</span>
        <div class="flex gap-1.5 flex-shrink-0">
            <button type="button" class="btn-confirm-yes px-2.5 py-1 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded shadow-sm transition-colors">Ya</button>
            <button type="button" class="btn-confirm-no px-2.5 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded transition-colors">Batal</button>
        </div>
    `;

    zone.appendChild(confirmDiv);

    confirmDiv.querySelector('.btn-confirm-yes').addEventListener('click', () => {
        confirmDiv.remove();
        onConfirm();
    });

    confirmDiv.querySelector('.btn-confirm-no').addEventListener('click', () => {
        confirmDiv.remove();
        onCancel();
    });
}

/**
 * Initializes auto-upload behavior for all `.audio-upload-zone` elements
 * within a given container.
 * @param {HTMLElement|HTMLDocument} container - The parent element to search within.
 */
function initAutoUpload(container) {
    container.querySelectorAll('.audio-upload-zone').forEach(zone => {
        const input    = zone.querySelector('.auto-upload-input');
        const filename = zone.querySelector('.upload-filename');
        const status   = zone.querySelector('.upload-status');
        const label    = zone.querySelector('.upload-label');

        if (!input || input.dataset.initialized === "1") return;
        input.dataset.initialized = "1";

        input.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;

            const file           = this.files[0];
            const url            = zone.dataset.url;
            const extra          = zone.dataset.audioType ? { audio_type: zone.dataset.audioType } : {};

            // Backward compatibility: if data-extra exists, parse it too
            if (!Object.keys(extra).length && zone.dataset.extra) {
                Object.assign(extra, JSON.parse(zone.dataset.extra));
            }
            const csrf           = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            const playerTargetId = zone.dataset.playerTarget;

            // — Show selected filename immediately
            if (filename) {
                filename.textContent = file.name;
                filename.classList.remove('text-gray-400');
                filename.classList.add('text-gray-700');
            }

            const startUpload = () => {
                setStatus(status, 'loading', '🔄 Mengunggah...');
                if (label) label.style.pointerEvents = 'none';

                const fd = new FormData();
                fd.append('audio_file', file);
                fd.append('_token', csrf);
                for (const [k, v] of Object.entries(extra)) fd.append(k, v);

                fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        setStatus(status, 'success', '✅ Berhasil diunggah!');
                        
                        // Update status to has audio
                        zone.dataset.hasAudio = "1";

                        // Update label to "Ganti File"
                        const labelSpan = zone.querySelector('.upload-label');
                        if (labelSpan) labelSpan.textContent = 'Ganti File';

                        // Refresh/Show audio player
                        if (data.url && playerTargetId) {
                            refreshAudioPlayer(playerTargetId, data.url);
                        }

                        // Dynamically show separate delete button for Narasi
                        if (playerTargetId === 'audio-player-narasi-indo') {
                            document.getElementById('delete-btn-narasi-indo')?.classList.remove('hidden');
                        } else if (playerTargetId === 'audio-player-narasi-sunda') {
                            document.getElementById('delete-btn-narasi-sunda')?.classList.remove('hidden');
                        }

                        setTimeout(() => setStatus(status, 'hidden'), 3000);
                    } else {
                        const msg = data.message || 'Terjadi kesalahan.';
                        setStatus(status, 'error', '❌ Gagal: ' + msg);
                        resetDisplay();
                    }
                })
                .catch(() => {
                    setStatus(status, 'error', '❌ Gagal: Kesalahan jaringan atau server.');
                    resetDisplay();
                })
                .finally(() => {
                    if (label) label.style.pointerEvents = '';
                    input.value = '';
                });
            };

            const resetDisplay = () => {
                if (filename) {
                    filename.textContent = 'Belum ada file dipilih';
                    filename.classList.add('text-gray-400');
                    filename.classList.remove('text-gray-700');
                }
                input.value = '';
            };

            // Check if overwrite confirmation is needed
            if (zone.dataset.hasAudio === "1") {
                showOverwriteConfirm(zone, startUpload, resetDisplay);
            } else {
                startUpload();
            }
        });
    });
}

/**
 * Sets the visual state of an upload status element.
 */
function setStatus(el, state, text) {
    if (!el) return;
    el.className = 'upload-status mt-1.5 text-xs font-medium';

    if (state === 'hidden') {
        el.classList.add('hidden');
        el.textContent = '';
        return;
    }

    el.classList.remove('hidden');
    el.textContent = text || '';

    if (state === 'loading') el.classList.add('text-blue-600');
    if (state === 'success') el.classList.add('text-green-600');
    if (state === 'error')   el.classList.add('text-red-600');
}

/**
 * Refreshes the <audio> player inside a target element by updating its src.
 */
function refreshAudioPlayer(targetId, url) {
    const target = document.getElementById(targetId);
    if (!target) return;

    let audio = target.querySelector('audio');
    if (!audio) {
        audio = document.createElement('audio');
        audio.controls = true;
        audio.className = 'flex-1 h-8';
        target.insertBefore(audio, target.firstChild);
    }

    audio.src = url;
    audio.load();
    target.classList.remove('hidden');
}

// Init for static zones on load
document.addEventListener('DOMContentLoaded', () => {
    initAutoUpload(document);
});


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
        let clientX = 0;
        let clientY = 0;

        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        } else if (e.changedTouches && e.changedTouches.length > 0) {
            clientX = e.changedTouches[0].clientX;
            clientY = e.changedTouches[0].clientY;
        } else {
            clientX = e.clientX;
            clientY = e.clientY;
        }

        let x = (clientX - rect.left) * (canvas.width  / rect.width);
        let y = (clientY - rect.top)  * (canvas.height / rect.height);

        // Clamp values to canvas bounds
        x = Math.max(0, Math.min(canvas.width, x));
        y = Math.max(0, Math.min(canvas.height, y));

        return { x, y };
    }

    const isPublished = '{{ $halaman->buku->status_publikasi }}' === 'Terbit';

    canvas.addEventListener('mousedown',  onStart);
    canvas.addEventListener('touchstart', onStart, { passive: false });

    function onStart(e) {
        if (isPublished) return;
        e.preventDefault();
        drawing = true;
        const pos = getPos(e);
        startX = pos.x; startY = pos.y;
        currentRect = null;
        hideLabelInput();
    }

    window.addEventListener('mousemove',  onMove);
    window.addEventListener('touchmove',  onMove, { passive: false });

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

    window.addEventListener('mouseup',  onEnd);
    window.addEventListener('touchend', onEnd);

    function onEnd(e) {
        if (!drawing) return;
        drawing = false;
        const pos = getPos(e);

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

        fetch("{{ route('halaman.storeAreaInteraktif') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                id_halaman   : parseInt('{{ $halaman->id_halaman }}'),
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
                ModalAlert.show('alertModal', {
                    title: 'Gagal Menyimpan',
                    subtitle: data.message || 'Terjadi kesalahan tidak diketahui.'
                });
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                currentRect = null;
                hideLabelInput();
            }
        })
        .catch(err => {
            ModalAlert.show('alertModal', {
                title: 'Error',
                subtitle: 'Terjadi kesalahan jaringan atau server saat menyimpan area.'
            });
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            currentRect = null;
            hideLabelInput();
        })
        .finally(() => {
            btn.disabled  = false;
            btn.innerHTML = '💾 Simpan';
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
        
        // Init auto-upload trigger on newly created cards dynamically
        initAutoUpload(newCard);
    }

    function buildAreaCard(area, index) {
        const label       = area.label || ('Area ' + index);
        const aId         = area.id_area;
        const uploadRoute = `{{ url('area-interaktif') }}/${aId}/audio`;
        const deleteRoute = `{{ url('area-interaktif') }}/${aId}`;
        const deleteAudioRoute = `{{ url('area-interaktif') }}/${aId}/audio`;
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
                <div class="flex items-center gap-2 mb-2 hidden" id="audio-player-area-${aId}-indo">
                    <audio controls class="flex-1 h-8" src=""></audio>
                    <form action="${deleteAudioRoute}" method="POST" class="flex-shrink-0">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="audio_type" value="indo">
                        <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">Hapus</button>
                    </form>
                </div>
                <div class="audio-upload-zone"
                     data-url="${uploadRoute}"
                     data-audio-type="indo"
                     data-player-target="audio-player-area-${aId}-indo"
                     data-has-audio="0">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <span class="upload-label flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium group-hover:bg-gray-50 transition-colors">Pilih File</span>
                        <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden auto-upload-input">
                        <span class="upload-filename flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate">Belum ada file dipilih</span>
                    </label>
                    <div class="upload-status mt-1.5 hidden text-xs font-medium"></div>
                    <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A • Suara saat objek dipilih</p>
                </div>
            </div>
            <div class="bg-purple-50 rounded-lg p-3 border border-purple-100">
                <p class="text-xs font-bold text-purple-800 mb-2">Audio Objek - Bahasa Sunda</p>
                <div class="flex items-center gap-2 mb-2 hidden" id="audio-player-area-${aId}-sunda">
                    <audio controls class="flex-1 h-8" src=""></audio>
                    <form action="${deleteAudioRoute}" method="POST" class="flex-shrink-0">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="audio_type" value="sunda">
                        <button type="submit" class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">Hapus</button>
                    </form>
                </div>
                <div class="audio-upload-zone"
                     data-url="${uploadRoute}"
                     data-audio-type="sunda"
                     data-player-target="audio-player-area-${aId}-sunda"
                     data-has-audio="0">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <span class="upload-label flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium group-hover:bg-gray-50 transition-colors">Pilih File</span>
                        <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden auto-upload-input">
                        <span class="upload-filename flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate">Belum ada file dipilih</span>
                    </label>
                    <div class="upload-status mt-1.5 hidden text-xs font-medium"></div>
                    <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A • Suara saat objek dipilih</p>
                </div>
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
        const aId   = this.dataset.id;
        const route = this.dataset.route;
        const csrf  = this.dataset.csrf;

        ModalAlert.confirm('globalConfirmModal', {
            title: 'Hapus Area Interaktif',
            subtitle: 'Apakah Anda yakin ingin menghapus area interaktif ini beserta audionya?'
        }, () => {
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
                    ModalAlert.show('alertModal', {
                        title: 'Gagal Menghapus',
                        subtitle: data.message || 'Terjadi kesalahan tidak diketahui.'
                    });
                }
            })
            .catch(err => {
                ModalAlert.show('alertModal', {
                    title: 'Error',
                    subtitle: 'Terjadi kesalahan jaringan atau server saat menghapus area.'
                });
            });
        });
    }

})();
</script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const flashData = document.getElementById('flash-data');
        if (flashData) {
            const err = flashData.getAttribute('data-error');
            const success = flashData.getAttribute('data-success');

            if (err) {
                ModalAlert.show('alertModal', {
                    title: 'Terjadi Kesalahan',
                    subtitle: err
                });
            }
            if (success) {
                ModalAlert.show('successModal', {
                    title: 'Berhasil!',
                    subtitle: success
                });
            }
        }
    });
</script>
@endpush

@endsection