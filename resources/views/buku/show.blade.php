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
<x-modal-alert id="alertModal" type="error" />
<x-modal-alert id="successModal" type="success" />

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
                @if($buku->status_publikasi !== 'Terbit')
                    <a href="{{ route('buku.edit', $buku->id_buku) }}"
                       class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm transition-colors">
                        Edit Informasi
                    </a>

                    <a href="{{ route('halaman.management', ['id_buku' => $buku->id_buku]) }}"
                       class="px-5 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold text-sm transition-colors">
                        Kelola Halaman
                    </a>

                    <form action="{{ route('buku.destroy', $buku->id_buku) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors">
                            Hapus Buku
                        </button>
                    </form>
                @endif

                @if($buku->status_publikasi === 'Draft')
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
                    <button
                        onclick="document.getElementById('modal-unpublish').classList.remove('hidden')"
                        class="px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold text-sm transition-colors">
                        📋 Kembalikan ke Draft
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- [FIX #2] Cover — fallback ke halaman pertama jika cover tidak ada --}}
    <div class="lg:col-span-1">
        @php
            // Cari gambar cover yang valid: path_cover → halaman pertama
            $coverUrl = null;
            if ($buku->path_cover && file_exists(storage_path('app/public/' . $buku->path_cover))) {
                $coverUrl = asset('storage/' . $buku->path_cover);
            } else {
                $firstPage = $buku->halaman->sortBy('nomor_halaman')->first();
                if ($firstPage && $firstPage->path_gambar && file_exists(storage_path('app/public/' . $firstPage->path_gambar))) {
                    $coverUrl = asset('storage/' . $firstPage->path_gambar);
                }
            }
        @endphp

        @if($coverUrl)
            <img src="{{ $coverUrl }}"
                 alt="{{ $buku->judul_idn }}"
                 class="w-full rounded-xl shadow-md border border-gray-200 object-cover">
        @else
            <div class="w-full aspect-[3/4] bg-gray-100 rounded-xl border border-gray-200 flex items-center justify-center">
                <span class="text-gray-400 text-sm">Tidak ada cover</span>
            </div>
        @endif

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

{{-- ═══════════════════════════════════════════════════════════════
     PRATINJAU FLIPBOOK — Embedded langsung di card
     ═══════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

    {{-- Card Header --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="text-lg font-bold text-gray-800">Pratinjau Flipbook</h2>
        <div class="flex items-center gap-3">
            @if($buku->halaman()->count() > 0 && $buku->status_publikasi !== 'Terbit')
                <a href="{{ route('halaman.management', ['id_buku' => $buku->id_buku]) }}"
                   class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-xs transition-colors">
                    Kelola Halaman
                </a>
            @endif
        </div>
    </div>

    @if($buku->halaman()->count() > 0)

    @php
        $getHexColor = function($value, $default) {
            if (!$value) return $default;
            $value = trim($value);
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) return strtoupper($value);
            $parts = array_map('trim', explode(',', $value));
            if (count($parts) !== 3) return $default;
            return sprintf('#%02X%02X%02X', max(0, min(255, (int)$parts[0])), max(0, min(255, (int)$parts[1])), max(0, min(255, (int)$parts[2])));
        };
        $primaryHex   = $getHexColor($buku->warna_primer, '#6366F1');
        $secondaryHex = $getHexColor($buku->warna_sekunder, '#8B5CF6');

        // Urutkan berdasarkan nomor_halaman ascending
        $halamanSorted = $buku->halaman->sortBy('nomor_halaman')->values();

        $pagesData = $halamanSorted->map(function($page) {
            return [
                'id'        => $page->id_halaman,
                'nomor'     => $page->nomor_halaman,
                'img'       => asset('storage/' . $page->path_gambar),
                'narasi_id' => $page->narasi_indo  ? asset('storage/' . $page->narasi_indo)  : null,
                'narasi_su' => $page->narasi_sunda ? asset('storage/' . $page->narasi_sunda) : null,
                'backsound' => $page->audioLatar   ? asset('storage/' . $page->audioLatar->path_file) : null,
                'areas'     => $page->areaInteraktif->map(function($area) {
                    return [
                        'id'       => $area->id_area,
                        'label'    => $area->label,
                        'x_pct'   => $area->x_pct,
                        'y_pct'   => $area->y_pct,
                        'w_pct'   => $area->w_pct,
                        'h_pct'   => $area->h_pct,
                        'audio_id' => $area->audio_indo  ? asset('storage/' . $area->audio_indo)  : null,
                        'audio_su' => $area->audio_sunda ? asset('storage/' . $area->audio_sunda) : null,
                    ];
                })->values()->toArray(),
            ];
        })->values();
    @endphp

    {{-- ── Flipbook Container ── --}}
    <div id="fb-shell" style="
        background: #1a1a2e;
        --fb-primary: {{ $primaryHex }};
        --fb-secondary: {{ $secondaryHex }};
        display: flex;
        flex-direction: column;
        height: 620px;
        position: relative;
        overflow: hidden;
        font-family: 'Segoe UI', system-ui, sans-serif;
        user-select: none;
    ">
        {{-- Loading overlay --}}
        <div id="fb-loading" style="
            position: absolute; inset: 0;
            background: #1a1a2e;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            z-index: 99; color: #fff; gap: 12px;
        ">
            <div style="
                width: 40px; height: 40px;
                border: 3px solid rgba(255,255,255,0.15);
                border-top-color: var(--fb-primary);
                border-radius: 50%;
                animation: fb-spin 0.8s linear infinite;
            "></div>
            <p style="font-size:13px;opacity:.6">Memuat flipbook...</p>
        </div>

        {{-- Top mini-bar --}}
        <div style="
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 8px 16px; gap: 10px; flex-shrink: 0; z-index: 10;
        ">
            {{-- Lang & Backsound controls --}}
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="display:flex; background:rgba(255,255,255,0.1); border-radius:7px; overflow:hidden; border:1px solid rgba(255,255,255,0.18);">
                    <button id="fb-lang-id" onclick="fbSetLang('id')" style="
                        padding:5px 11px; font-size:11px; font-weight:700;
                        background: var(--fb-primary); color:#fff;
                        border:none; cursor:pointer;">ID</button>
                    <button id="fb-lang-su" onclick="fbSetLang('su')" style="
                        padding:5px 11px; font-size:11px; font-weight:700;
                        background:transparent; color:rgba(255,255,255,0.55);
                        border:none; cursor:pointer;">SU</button>
                </div>
                {{-- Backsound pause/play toggle --}}
                <button id="fb-btn-backsound-toggle" onclick="fbToggleBacksound()" title="Pause/Play Audio Latar" style="
                    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
                    color: #fff; padding: 5px 10px; border-radius: 7px;
                    font-size: 11px; font-weight: 700; cursor: pointer; display: none; align-items: center; gap: 4px; transition: background 0.2s;">
                    🔊 Musik
                </button>
            </div>

            {{-- Page slider --}}
            <input type="range" id="fb-slider" min="0" max="{{ $halamanSorted->count() - 1 }}" value="0" step="1"
                style="
                    flex:1; max-width:240px; height:4px; border-radius:4px;
                    accent-color: var(--fb-primary); cursor:pointer; margin: 0 4px;
                ">

            {{-- Page counter --}}
            <span id="fb-counter" style="font-size:12px; color:rgba(255,255,255,0.65); min-width:70px; text-align:center; flex-shrink:0;"></span>

            {{-- [FIX #4] Narasi button group: SU→ID otomatis, atau pilih per bahasa --}}
            <div id="fb-narasi-group" style="display:none; align-items:center; gap:5px;">
                {{-- Tombol play Sunda lalu sambung Indo --}}
                <button id="fb-btn-narasi-both" onclick="fbPlayNarasiBoth()" title="Play Sunda → Indo" style="
                    background:linear-gradient(90deg,#7c3aed,#2563eb);
                    border:1px solid rgba(255,255,255,0.2);
                    color:#fff; padding:5px 10px; border-radius:7px;
                    font-size:11px; font-weight:700; cursor:pointer;
                    white-space:nowrap;">
                    🔊 SU→ID
                </button>
                {{-- Tombol play Sunda saja --}}
                <button id="fb-btn-narasi-su" onclick="fbPlayNarasi('su')" title="Play Narasi Sunda" style="
                    background:rgba(124,58,237,0.5); border:1px solid rgba(255,255,255,0.2);
                    color:#fff; padding:5px 10px; border-radius:7px;
                    font-size:11px; font-weight:700; cursor:pointer;">
                    🔊 SU
                </button>
                {{-- Tombol play Indo saja --}}
                <button id="fb-btn-narasi-id" onclick="fbPlayNarasi('id')" title="Play Narasi Indonesia" style="
                    background:rgba(37,99,235,0.5); border:1px solid rgba(255,255,255,0.2);
                    color:#fff; padding:5px 10px; border-radius:7px;
                    font-size:11px; font-weight:700; cursor:pointer;">
                    🔊 ID
                </button>
                {{-- Tombol stop --}}
                <button id="fb-btn-stop-narasi" onclick="fbStopAudio(false)" title="Stop" style="
                    display:none;
                    background:rgba(220,38,38,0.6); border:1px solid rgba(255,255,255,0.2);
                    color:#fff; padding:5px 9px; border-radius:7px;
                    font-size:11px; font-weight:700; cursor:pointer;">
                    ■
                </button>
            </div>
        </div>

        {{-- Stage --}}
        <div id="fb-stage" style="
            flex:1; display:flex; align-items:center; justify-content:center;
            overflow:hidden; position:relative; padding:16px 70px;
        ">
            {{-- Book wrap --}}
            <div id="fb-book-wrap" style="position:relative; display:flex; align-items:center; justify-content:center;">

                {{-- Single page card --}}
                <div id="fb-page-card" style="
                    position:relative; border-radius:4px; overflow:hidden;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.7), 4px 0 12px rgba(0,0,0,0.4);
                    background:#fff; flex-shrink:0;
                ">
                    <img id="fb-page-img" src="" alt="" style="
                        display:block; width:100%; height:100%;
                        object-fit:contain; pointer-events:none;
                    ">
                    <div id="fb-areas" style="position:absolute; inset:0; pointer-events:none;"></div>
                </div>

                {{-- Nav arrows --}}
                <button id="fb-btn-prev" onclick="fbGoPage(-1)" style="
                    position:absolute; left:-56px; top:50%; transform:translateY(-50%);
                    background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.2);
                    color:#fff; width:40px; height:40px; border-radius:50%;
                    font-size:20px; cursor:pointer; display:flex;
                    align-items:center; justify-content:center;
                    backdrop-filter:blur(4px); z-index:20; transition:background 0.2s;">‹</button>

                <button id="fb-btn-next" onclick="fbGoPage(1)" style="
                    position:absolute; right:-56px; top:50%; transform:translateY(-50%);
                    background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.2);
                    color:#fff; width:40px; height:40px; border-radius:50%;
                    font-size:20px; cursor:pointer; display:flex;
                    align-items:center; justify-content:center;
                    backdrop-filter:blur(4px); z-index:20; transition:background 0.2s;">›</button>
            </div>

            {{-- Audio bar --}}
            <div id="fb-audio-bar" style="
                position:absolute; bottom:14px; left:50%; transform:translateX(-50%);
                background:rgba(0,0,0,0.82); backdrop-filter:blur(10px);
                border:1px solid rgba(255,255,255,0.13); border-radius:50px;
                padding:8px 18px; display:flex; align-items:center; gap:10px;
                color:#fff; font-size:12px; opacity:0;
                transition:opacity 0.3s; pointer-events:none; max-width:85%;
            ">
                <span style="animation:fb-note 1s ease-in-out infinite; display:inline-block; font-size:16px;">🎵</span>
                <span id="fb-audio-label" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;color:rgba(255,255,255,0.8);"></span>
                <button onclick="fbStopAudio()" style="
                    background:rgba(255,255,255,0.14); border:none; color:#fff;
                    border-radius:50%; width:26px; height:26px; font-size:12px;
                    cursor:pointer; display:flex; align-items:center; justify-content:center;">■</button>
            </div>
        </div>

        {{-- Thumbnail strip --}}
        <div id="fb-thumb-strip" style="
            position: relative;
            background:rgba(0,0,0,0.45);
            display:flex; gap:5px; padding:7px 14px;
            overflow-x:auto; flex-shrink:0; align-items:center;
            border-top:1px solid rgba(255,255,255,0.07);
            scrollbar-width:thin; scrollbar-color:rgba(255,255,255,0.18) transparent;
        ">
            @foreach($halamanSorted as $loopIdx => $page)
            <div class="fb-thumb" data-idx="{{ $loopIdx }}" onclick="fbJumpTo({{ $loopIdx }})" style="
                flex-shrink:0; width:40px; height:54px; border-radius:3px;
                overflow:hidden; cursor:pointer;
                border:2px solid transparent;
                transition:border-color 0.2s, transform 0.2s;
                position:relative;
            ">
                <img src="{{ asset('storage/' . $page->path_gambar) }}"
                     alt="Hal {{ $page->nomor_halaman }}"
                     loading="lazy"
                     style="width:100%;height:100%;object-fit:cover;">
                <div style="
                    position:absolute; bottom:0; left:0; right:0;
                    background:rgba(0,0,0,0.58); color:#fff;
                    font-size:8px; text-align:center; padding:1px 0;
                ">{{ $page->nomor_halaman }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Styles ── --}}
    <style>
        @keyframes fb-spin  { to { transform: rotate(360deg); } }
        @keyframes fb-note  { 0%,100%{transform:rotate(-10deg)} 50%{transform:rotate(10deg)} }
        @keyframes fb-pulse { 0%,100%{box-shadow:0 0 0 0 rgba(255,215,0,.5)} 50%{box-shadow:0 0 0 6px rgba(255,215,0,0)} }

        #fb-btn-prev:hover, #fb-btn-next:hover { background: rgba(255,255,255,0.28) !important; }
        #fb-btn-prev:disabled, #fb-btn-next:disabled { opacity:.22; cursor:not-allowed; }

        .fb-thumb:hover { transform: scale(1.1); }
        .fb-thumb.fb-active { border-color: var(--fb-primary) !important; }

        #fb-narasi-group button:hover { filter: brightness(1.2); }
        #fb-btn-narasi-both.fb-playing-both { background: linear-gradient(90deg,#6d28d9,#1d4ed8) !important; box-shadow: 0 0 8px rgba(124,58,237,0.6); }

        .fb-area-box {
            position:absolute; border:2px solid rgba(255,200,0,.7);
            background:rgba(255,200,0,.08); border-radius:4px;
            cursor:pointer; pointer-events:all;
            transition:background .15s, border-color .15s;
            display:flex; align-items:center; justify-content:center;
        }
        .fb-area-box:hover  { background:rgba(255,200,0,.25); border-color:rgba(255,200,0,1); }
        .fb-area-box.fb-playing {
            background:rgba(255,200,0,.35); border-color:#FFD700;
            animation: fb-pulse 1s ease-in-out infinite;
        }
        .fb-area-label {
            position:absolute; top:-22px; left:-2px;
            background:rgba(255,215,0,.95); color:#333;
            font-size:9px; font-weight:700; padding:2px 5px;
            border-radius:4px; white-space:nowrap; pointer-events:none;
            box-shadow:0 2px 4px rgba(0,0,0,.3); z-index:10;
        }
        .fb-area-icon {
            width:26px; height:26px;
            background:rgba(255,215,0,.9); border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:13px; box-shadow:0 2px 6px rgba(0,0,0,.3);
            pointer-events:none;
        }
    </style>

    {{-- ── Flipbook JS ── --}}
    <script>
    (function(){
        const PAGES = @json($pagesData);
        const TOTAL = PAGES.length;

        let fbIdx         = 0;
        let fbLang        = 'id';
        let fbActiveAudio = null;
        let fbBacksound   = null;
        let fbAnimating   = false;
        // [FIX #4] State untuk SU→ID chain
        let fbNarasiChain = false;

        const shell         = document.getElementById('fb-shell');
        const loading       = document.getElementById('fb-loading');
        const pageCard      = document.getElementById('fb-page-card');
        const pageImg       = document.getElementById('fb-page-img');
        const areasEl       = document.getElementById('fb-areas');
        const counter       = document.getElementById('fb-counter');
        const btnPrev       = document.getElementById('fb-btn-prev');
        const btnNext       = document.getElementById('fb-btn-next');
        const narasiGroup   = document.getElementById('fb-narasi-group');
        const btnNarasiBoth = document.getElementById('fb-btn-narasi-both');
        const btnNarasiSu   = document.getElementById('fb-btn-narasi-su');
        const btnNarasiId   = document.getElementById('fb-btn-narasi-id');
        const btnStopNarasi = document.getElementById('fb-btn-stop-narasi');
        const audioBar      = document.getElementById('fb-audio-bar');
        const audioLabel    = document.getElementById('fb-audio-label');
        const stage         = document.getElementById('fb-stage');
        const slider        = document.getElementById('fb-slider');

        /* ── Size ── */
        function fbSize() {
            const W = stage.clientWidth;
            const H = stage.clientHeight - 32;
            let pageW = Math.min(H * (3/4), W - 20);
            let pageH = pageW * (4/3);
            if (pageH > H) { pageH = H; pageW = pageH * (3/4); }
            pageCard.style.width  = pageW + 'px';
            pageCard.style.height = pageH + 'px';
        }

        /* ── Render ── */
        function fbRender() {
            const page = PAGES[fbIdx];
            pageImg.src = page ? page.img : '';
            fbRenderAreas(page);

            counter.textContent = `${page ? page.nomor : fbIdx + 1} / ${TOTAL}`;
            slider.value = fbIdx;

            // [FIX #4] Tampilkan narasi group jika halaman punya narasi (id atau su)
            const hasNarasiId = page && !!page.narasi_id;
            const hasNarasiSu = page && !!page.narasi_su;
            const hasAnyNarasi = hasNarasiId || hasNarasiSu;
            narasiGroup.style.display = hasAnyNarasi ? 'flex' : 'none';

            // Sembunyikan tombol yang tidak punya audio
            btnNarasiBoth.style.display = (hasNarasiId && hasNarasiSu) ? 'inline-flex' : 'none';
            btnNarasiSu.style.display   = hasNarasiSu ? 'inline-flex' : 'none';
            btnNarasiId.style.display   = hasNarasiId ? 'inline-flex' : 'none';
            btnStopNarasi.style.display = 'none';

            fbPlayBacksound(page);
            fbUpdateThumbs();

            btnPrev.disabled = fbIdx <= 0;
            btnNext.disabled = fbIdx >= TOTAL - 1;
        }

        /* ── Areas ── */
        function fbRenderAreas(page) {
            areasEl.innerHTML = '';
            areasEl.style.pointerEvents = 'none';
            if (!page || !page.areas || !page.areas.length) return;

            page.areas.forEach(area => {
                if (area.x_pct == null) return;
                const box = document.createElement('div');
                box.className = 'fb-area-box';
                box.style.cssText = `left:${area.x_pct}%;top:${area.y_pct}%;width:${area.w_pct}%;height:${area.h_pct}%;position:absolute;`;
                box.dataset.areaId = area.id;

                const lbl = document.createElement('div');
                lbl.className = 'fb-area-label';
                lbl.textContent = area.label || ('Area ' + area.id);
                box.appendChild(lbl);

                const ico = document.createElement('div');
                ico.className = 'fb-area-icon';
                ico.textContent = '🔊';
                box.appendChild(ico);

                box.addEventListener('click', e => {
                    e.stopPropagation();
                    const src = fbLang === 'id' ? area.audio_id : area.audio_su;
                    fbPlayAreaAudio(src || area.audio_id || area.audio_su, lbl.textContent, area.id, box);
                });

                areasEl.appendChild(box);
            });
        }

        /* ── Audio area ── */
        function fbPlayAreaAudio(src, label, areaId, boxEl) {
            if (!src) return;
            fbStopAudio(false);
            fbActiveAudio = new Audio(src);
            boxEl.classList.add('fb-playing');
            audioLabel.textContent = label;
            audioBar.style.opacity = '1';
            audioBar.style.pointerEvents = 'all';
            fbActiveAudio.play().catch(()=>{});
            fbActiveAudio.addEventListener('ended', () => {
                boxEl.classList.remove('fb-playing');
                audioBar.style.opacity = '0';
                audioBar.style.pointerEvents = 'none';
                fbActiveAudio = null;
            });
        }

        window.fbStopAudio = function(stopBack = false) {
            fbNarasiChain = false;
            if (fbActiveAudio) { fbActiveAudio.pause(); fbActiveAudio = null; }
            document.querySelectorAll('.fb-area-box.fb-playing').forEach(b => b.classList.remove('fb-playing'));
            audioBar.style.opacity = '0';
            audioBar.style.pointerEvents = 'none';
            btnStopNarasi.style.display = 'none';
            btnNarasiBoth.classList.remove('fb-playing-both');
            if (stopBack && fbBacksound) { fbBacksound.pause(); fbBacksound = null; }
        };

        let fbBacksoundPaused = false; // Initial state

        window.fbToggleBacksound = function() {
            if (!fbBacksound) {
                fbBacksoundPaused = !fbBacksoundPaused;
                fbUpdateBacksoundButton();
                return;
            }

            if (fbBacksound.paused) {
                fbBacksoundPaused = false;
                fbBacksound.play().catch(()=>{});
            } else {
                fbBacksoundPaused = true;
                fbBacksound.pause();
            }
            fbUpdateBacksoundButton();
        };

        function fbUpdateBacksoundButton() {
            const btn = document.getElementById('fb-btn-backsound-toggle');
            if (!btn) return;
            const page = PAGES[fbIdx];
            if (!page || !page.backsound) {
                btn.style.display = 'none';
                return;
            }
            btn.style.display = 'flex';
            if (fbBacksoundPaused) {
                btn.innerHTML = '🔇 Musik';
                btn.style.background = 'rgba(220,38,38,0.3)';
            } else {
                btn.innerHTML = '🔊 Musik';
                btn.style.background = 'rgba(255,255,255,0.1)';
            }
        }

        function fbPlayBacksound(page) {
            if (fbBacksound) { fbBacksound.pause(); fbBacksound = null; }
            if (page && page.backsound) {
                fbBacksound = new Audio(page.backsound);
                fbBacksound.loop = true;
                fbBacksound.volume = 0.35;
                if (!fbBacksoundPaused) {
                    fbBacksound.play().catch(()=>{});
                }
            }
            fbUpdateBacksoundButton();
        }

        /* ── [FIX #4] Narasi: play per bahasa / SU→ID chain ── */

        /**
         * Play narasi per bahasa (id atau su).
         * Dipakai oleh tombol SU dan ID individual.
         */
        window.fbPlayNarasi = function(lang) {
            const page = PAGES[fbIdx];
            if (!page) return;
            const src = lang === 'su' ? page.narasi_su : page.narasi_id;
            if (!src) return;
            fbStopAudio(false);
            fbNarasiChain = false;
            fbActiveAudio = new Audio(src);
            const langLabel = lang === 'su' ? 'Sunda' : 'Indonesia';
            audioLabel.textContent = `Narasi ${langLabel} - Hal. ${page.nomor}`;
            audioBar.style.opacity = '1';
            audioBar.style.pointerEvents = 'all';
            btnStopNarasi.style.display = 'inline-flex';
            fbActiveAudio.play().catch(()=>{});
            fbActiveAudio.addEventListener('ended', () => {
                audioBar.style.opacity = '0';
                audioBar.style.pointerEvents = 'none';
                btnStopNarasi.style.display = 'none';
                fbActiveAudio = null;
            });
        };

        /**
         * [FIX #4] Play Sunda terlebih dahulu, kemudian otomatis lanjut ke Indo.
         * Jika hanya satu yang tersedia, play yang ada.
         */
        window.fbPlayNarasiBoth = function() {
            const page = PAGES[fbIdx];
            if (!page) return;

            fbStopAudio(false);

            const srcSu = page.narasi_su;
            const srcId = page.narasi_id;

            if (!srcSu && !srcId) return;

            // Jika hanya salah satu tersedia, langsung play yang ada
            if (!srcSu) { fbPlayNarasi('id'); return; }
            if (!srcId)  { fbPlayNarasi('su'); return; }

            // Keduanya ada: chain SU → ID
            fbNarasiChain = true;
            btnNarasiBoth.classList.add('fb-playing-both');
            btnStopNarasi.style.display = 'inline-flex';

            fbActiveAudio = new Audio(srcSu);
            audioLabel.textContent = `Narasi Sunda → Indonesia - Hal. ${page.nomor}`;
            audioBar.style.opacity = '1';
            audioBar.style.pointerEvents = 'all';
            fbActiveAudio.play().catch(()=>{});

            fbActiveAudio.addEventListener('ended', () => {
                // Setelah Sunda selesai, lanjut ke Indo jika masih dalam chain dan belum dihentikan
                if (!fbNarasiChain) return;

                // Update label
                audioLabel.textContent = `Narasi Indonesia - Hal. ${page.nomor}`;

                fbActiveAudio = new Audio(srcId);
                fbActiveAudio.play().catch(()=>{});
                fbActiveAudio.addEventListener('ended', () => {
                    fbNarasiChain = false;
                    btnNarasiBoth.classList.remove('fb-playing-both');
                    audioBar.style.opacity = '0';
                    audioBar.style.pointerEvents = 'none';
                    btnStopNarasi.style.display = 'none';
                    fbActiveAudio = null;
                });
            });
        };

        /* ── Lang ── */
        window.fbSetLang = function(lang) {
            fbLang = lang;
            document.getElementById('fb-lang-id').style.background = lang === 'id' ? 'var(--fb-primary)' : 'transparent';
            document.getElementById('fb-lang-id').style.color      = lang === 'id' ? '#fff' : 'rgba(255,255,255,0.55)';
            document.getElementById('fb-lang-su').style.background = lang === 'su' ? 'var(--fb-primary)' : 'transparent';
            document.getElementById('fb-lang-su').style.color      = lang === 'su' ? '#fff' : 'rgba(255,255,255,0.55)';
            fbStopAudio(false);
        };

        /* ── Navigation ── */
        window.fbGoPage = function(dir) {
            if (fbAnimating) return;
            const newIdx = fbIdx + dir;
            if (newIdx < 0 || newIdx >= TOTAL) return;
            fbStopAudio(false);
            fbAnimating = true;
            fbIdx = newIdx;
            fbRender();
            setTimeout(() => fbAnimating = false, 300);
        };

        window.fbJumpTo = function(idx) {
            fbStopAudio(false);
            fbIdx = idx;
            fbRender();
        };

        /* ── Thumbs ── */
        function fbUpdateThumbs() {
            const strip = document.getElementById('fb-thumb-strip');
            document.querySelectorAll('.fb-thumb').forEach(el => {
                const active = parseInt(el.dataset.idx) === fbIdx;
                el.classList.toggle('fb-active', active);
                if (active && strip) {
                    const elOffset = el.offsetLeft;
                    const elWidth = el.offsetWidth;
                    const stripWidth = strip.clientWidth;
                    strip.scrollTo({
                        left: elOffset - (stripWidth / 2) + (elWidth / 2),
                        behavior: 'smooth'
                    });
                }
            });
        }

        /* ── Slider ── */
        slider.addEventListener('input', e => {
            const idx = parseInt(e.target.value, 10);
            if (idx === fbIdx) return;
            fbStopAudio(false);
            fbIdx = idx;
            fbRender();
        });

        /* ── Scroll wheel navigation ── */
        let fbWheelLock = false;
        stage.addEventListener('wheel', e => {
            e.preventDefault();
            if (fbWheelLock) return;
            const dir = e.deltaY > 0 ? 1 : -1;
            const newIdx = fbIdx + dir;
            if (newIdx < 0 || newIdx >= TOTAL) return;
            fbWheelLock = true;
            fbGoPage(dir);
            setTimeout(() => fbWheelLock = false, 350);
        }, { passive: false });

        /* ── Touch swipe ── */
        let fbTouchX = 0;
        stage.addEventListener('touchstart', e => fbTouchX = e.touches[0].clientX, { passive:true });
        stage.addEventListener('touchend',   e => {
            const dx = e.changedTouches[0].clientX - fbTouchX;
            if (Math.abs(dx) > 50) fbGoPage(dx < 0 ? 1 : -1);
        }, { passive:true });

        /* ── Keyboard ── */
        shell.setAttribute('tabindex', '0');
        shell.addEventListener('keydown', e => {
            if (e.key === 'ArrowRight') fbGoPage(1);
            if (e.key === 'ArrowLeft')  fbGoPage(-1);
        });

        /* ── Init ── */
        window.addEventListener('load', () => {
            fbSize();
            fbRender();
            setTimeout(() => loading.style.display = 'none', 350);
        });
        new ResizeObserver(fbSize).observe(stage);

    })();
    </script>

    @else
    {{-- Kosong --}}
    <div style="padding: 60px 20px; text-align:center; background:#f9fafb;">
        <p class="text-gray-400">Belum ada halaman. Silakan unggah PDF untuk membuat halaman.</p>
        <a href="{{ route('halaman.management', ['id_buku' => $buku->id_buku]) }}"
           class="mt-4 inline-block px-5 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold text-sm transition-colors">
            Kelola Halaman
        </a>
    </div>
    @endif

</div>
{{-- ── End Pratinjau Flipbook ── --}}

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

<script>
    document.getElementById('modal-unpublish').addEventListener('click', function (e) {
        if (e.target === this) this.classList.add('hidden');
    });
</script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->has('publication'))
            ModalAlert.show('alertModal', {
                title: 'Buku Belum Siap Diterbitkan',
                subtitle: '{{ $errors->first('publication') }}'
            });
        @endif
        @if (session('success'))
            ModalAlert.show('successModal', {
                title: 'Berhasil!',
                subtitle: '{{ session('success') }}'
            });
        @endif
    });
</script>
@endpush

@endsection