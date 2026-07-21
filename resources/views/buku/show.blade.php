@extends('layouts.dashboard')

@section('content')

<x-modal-alert id="alertModal" type="error" />
<x-modal-alert id="successModal" type="success" />

<div id="flash-data" 
     data-error="{{ $errors->any() ? $errors->first() : '' }}"
     data-success="{{ !$buku->is_processing ? session('success') : '' }}">
</div>

<div class="mb-6">
    <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium inline-flex items-center gap-1">
        ← Kembali ke Daftar Buku
    </a>
</div>

@if(isset($warning) && $warning)
<div class="mb-6 bg-gradient-to-r from-amber-50 to-orange-50/50 border border-amber-200 border-l-4 border-l-amber-500 rounded-r-xl p-4 shadow-sm">
    <div class="flex items-start gap-3">
        <div class="text-xl">⚠️</div>
        <div>
            <h4 class="text-sm font-bold text-amber-900">Perhatian: Aset Multimedia Tidak Lengkap</h4>
            <p class="text-xs text-amber-700 mt-1 font-medium">{{ $warning }}</p>
        </div>
    </div>
</div>
@endif

{{-- Info Card + Cover --}}
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
    <div class="lg:col-span-3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

            {{-- Header: Judul + Status di dalam Card --}}
            <div class="flex justify-between items-start mb-6 pb-6 border-b border-gray-100">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $buku->judul_idn }}</h1>
                    @if($buku->judul_sn)
                        <p class="text-gray-500 mt-1 text-sm italic">{{ $buku->judul_sn }}</p>
                    @endif
                </div>
                <div>
                    @if($buku->status_publikasi === 'Terbit')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 text-green-800 rounded-lg font-semibold text-xs border border-green-200">
                            ✅ Terbit
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-50 text-yellow-800 rounded-lg font-semibold text-xs border border-yellow-200">
                            📋 Draft
                        </span>
                    @endif
                </div>
            </div>

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
                @if($buku->is_processing)
                    {{-- Kondisi jika SQS masih memproses PDF --}}
                    <button disabled class="px-5 py-2 bg-gray-400 text-white rounded-lg font-semibold text-sm cursor-not-allowed">
                        <svg class="animate-spin inline-block w-4 h-4 mr-2 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Buku Sedang Diproses...
                    </button>
                @else
                    {{-- Kondisi normal setelah proses SQS selesai --}}
                    @if($buku->status_publikasi !== 'Terbit')
                        <a href="{{ route('buku.edit', $buku) }}"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm transition-colors">
                            Edit Informasi
                        </a>
                        <a href="{{ route('halaman.management', ['id_buku' => $buku->id_buku]) }}"
                        class="px-5 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold text-sm transition-colors">
                            Kelola Halaman
                        </a>
                        <form action="{{ route('buku.destroy', $buku) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors">
                                Hapus Buku
                            </button>
                        </form>
                    @endif

                    @if($buku->status_publikasi === 'Draft')
                        <button onclick="openPublishModal('modal-publish')"
                                class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold text-sm transition-colors">
                            Publikasikan
                        </button>
                    @else
                        <button
                            onclick="openPublishModal('modal-unpublish')"
                            class="px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold text-sm transition-colors">
                            Kembalikan ke Draft
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- [FIX #2] Cover — fallback ke halaman pertama jika cover tidak ada --}}
    <div class="lg:col-span-1 flex justify-center lg:justify-start flex-col">
        @php
            $coverUrl = null;
            if ($buku->path_cover) {
                $coverUrl = Storage::disk(config('filesystems.default'))->url($buku->path_cover);
            }
            if (!$coverUrl) {
                $firstPage = $buku->halaman->sortBy('nomor_halaman')->first();
                if ($firstPage && $firstPage->path_gambar) {
                    $coverUrl = Storage::disk(config('filesystems.default'))->url($firstPage->path_gambar);
                }
            }
        @endphp

        @if($coverUrl)
            <img src="{{ $coverUrl }}"
                 alt="{{ $buku->judul_idn }}"
                 class="w-full max-w-[280px] lg:max-w-full h-auto rounded-xl shadow-md border border-gray-200">
        @else
            <div class="w-full max-w-[280px] lg:max-w-full aspect-[3/4] bg-gray-100 rounded-xl border border-gray-200 flex items-center justify-center">
                <span class="text-gray-400 text-sm">Tidak ada cover</span>
            </div>
        @endif

        @if($buku->status_publikasi === 'Terbit' && !empty($buku->zip_bundle_path))
            @php
                $zipAbs  = storage_path('app/public/' . $buku->zip_bundle_path);
                $zipSize = file_exists($zipAbs) ? round(filesize($zipAbs) / 1048576, 1) . ' MB' : null;
            @endphp
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
            $isCover = $page->nomor_halaman === 1;
            return [
                'id'        => $page->id_halaman,
                'nomor'     => $page->nomor_halaman,
                'img'       => $page->path_gambar ? Storage::disk(config('filesystems.default'))->url($page->path_gambar) : null,
                'narasi_id' => $page->narasi_indo  ? Storage::disk(config('filesystems.default'))->url($page->narasi_indo)  : null,
                'narasi_su' => $page->narasi_sunda ? Storage::disk(config('filesystems.default'))->url($page->narasi_sunda) : null,
                'backsound' => (!$isCover && $page->audioLatar && $page->audioLatar->path_file) ? Storage::disk(config('filesystems.default'))->url($page->audioLatar->path_file) : null,
                'areas'     => $isCover ? [] : $page->areaInteraktif->map(function($area) {
                    return [
                        'id'       => $area->id_area,
                        'label'    => $area->label,
                        'x_pct'   => $area->x_pct,
                        'y_pct'   => $area->y_pct,
                        'w_pct'   => $area->w_pct,
                        'h_pct'   => $area->h_pct,
                        'audio_id' => $area->audio_indo  ? Storage::disk(config('filesystems.default'))->url($area->audio_indo)  : null,
                        'audio_su' => $area->audio_sunda ? Storage::disk(config('filesystems.default'))->url($area->audio_sunda) : null,
                    ];
                })->values()->toArray(),
            ];
        })->values();
    @endphp

    {{-- ── Flipbook Container ── --}}
    <div id="fb-shell" data-pages="{{ json_encode($pagesData) }}" style="
        --fb-primary: {{ $primaryHex }};
        --fb-secondary: {{ $secondaryHex }};
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
        <div id="fb-top-bar">
            {{-- Lang controls --}}
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="display:flex; background:rgba(255,255,255,0.1); border-radius:7px; overflow:hidden; border:1px solid rgba(255,255,255,0.18);">
                    <button id="fb-lang-id" onclick="fbSetLang('id')" style="
                        padding:5px 11px; font-size:11px; font-weight:700;
                        background: var(--fb-primary); color:#fff;
                        border:none; cursor:pointer;" title="Bahasa Indonesia">ID</button>
                    <button id="fb-lang-su" onclick="fbSetLang('su')" style="
                        padding:5px 11px; font-size:11px; font-weight:700;
                        background:transparent; color:rgba(255,255,255,0.55);
                        border:none; cursor:pointer;" title="Bahasa Sunda">SU</button>
                    <button id="fb-lang-both" onclick="fbSetLang('both')" style="
                        padding:5px 11px; font-size:11px; font-weight:700;
                        background:transparent; color:rgba(255,255,255,0.55);
                        border:none; cursor:pointer;" title="Bahasa Sunda lalu Indonesia">SU & ID</button>
                </div>
            </div>

            {{-- Page counter --}}
            <span id="fb-counter" style="font-size:12px; color:rgba(255,255,255,0.65); min-width:70px; text-align:center; flex-shrink:0;"></span>

            {{-- Narasi & Backsound controls --}}
            <div style="display:flex; align-items:center; gap:5px;">
                {{-- Backsound pause/play toggle --}}
                <button id="fb-btn-backsound-toggle" onclick="fbToggleBacksound()" title="Pause/Play Audio Latar" style="
                    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
                    color: #fff; padding: 5px 10px; border-radius: 7px;
                    font-size: 11px; font-weight: 700; cursor: pointer; display: none; align-items: center; gap: 4px; transition: background 0.2s;">
                    🔊 Musik
                </button>

                {{-- Narasi button group: Pemutaran narasi sesuai bahasa aktif --}}
                <div id="fb-narasi-group" style="display:none; align-items:center; gap:5px;">
                    {{-- Tombol toggle narasi --}}
                    <button id="fb-btn-narasi-toggle" onclick="fbToggleNarasi()" title="Play/Stop Narasi" style="
                        background: var(--fb-primary);
                        border: 1px solid rgba(255,255,255,0.2);
                        color: #fff; padding: 5px 10px; border-radius: 7px;
                        font-size: 11px; font-weight: 700; cursor: pointer;
                        white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; transition: background 0.2s;">
                        🔊 Putar Narasi
                    </button>
                </div>
            </div>
        </div>

        {{-- Stage --}}
        <div id="fb-stage" style="
            flex:1; display:flex; align-items:center; justify-content:center;
            overflow:hidden; position:relative;
        ">
            {{-- Nav arrows --}}
            <button id="fb-btn-prev" onclick="fbGoPage(-1)" style="
                position:absolute; top:50%; transform:translateY(-50%);
                background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.2);
                color:#fff; width:40px; height:40px; border-radius:50%;
                font-size:20px; cursor:pointer; display:flex;
                align-items:center; justify-content:center;
                backdrop-filter:blur(4px); z-index:20; transition:background 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>

            <button id="fb-btn-next" onclick="fbGoPage(1)" style="
                position:absolute; top:50%; transform:translateY(-50%);
                background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.2);
                color:#fff; width:40px; height:40px; border-radius:50%;
                font-size:20px; cursor:pointer; display:flex;
                align-items:center; justify-content:center;
                backdrop-filter:blur(4px); z-index:20; transition:background 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" style="width: 20px; height: 20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>

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
            <div class="fb-thumb" data-idx="{{ $loopIdx }}" onclick="fbJumpTo(parseInt(this.dataset.idx))" style="
                flex-shrink:0; width:40px; height:54px; border-radius:3px;
                overflow:hidden; cursor:pointer;
                border:2px solid transparent;
                transition:border-color 0.2s, transform 0.2s;
                position:relative;
            ">
                <img src="{{ $page->path_gambar ? Storage::disk(config('filesystems.default'))->url($page->path_gambar) : '' }}"
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

        #fb-shell {
            background: #1a1a2e;
            display: flex;
            flex-direction: column;
            height: 620px;
            position: relative;
            overflow: hidden;
            font-family: 'Segoe UI', system-ui, sans-serif;
            user-select: none;
        }

        #fb-top-bar {
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 16px;
            gap: 10px;
            flex-shrink: 0;
            z-index: 10;
        }

        #fb-stage {
            padding: 16px;
        }
        #fb-btn-prev {
            left: 16px;
        }
        #fb-btn-next {
            right: 16px;
        }

        @media (max-width: 768px) {
            #fb-stage {
                padding: 16px 8px;
            }
            #fb-btn-prev {
                left: 8px;
                background: rgba(0, 0, 0, 0.6) !important;
            }
            #fb-btn-next {
                right: 8px;
                background: rgba(0, 0, 0, 0.6) !important;
            }
        }

        @media (max-width: 640px) {
            #fb-shell {
                height: 480px;
            }
            #fb-top-bar {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
                padding: 8px;
            }
        }

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
        const shell         = document.getElementById('fb-shell');
        const PAGES         = JSON.parse(shell.getAttribute('data-pages') || '[]');
        const TOTAL         = PAGES.length;

        let fbIdx         = 0;
        let fbLang        = 'id';
        let fbActiveAudio = null;
        let fbBacksound   = null;
        let fbAnimating   = false;
        let fbNarasiChain = false;
        let fbNarasiPlaying = false;
        let fbAreaChain   = false;
        const loading       = document.getElementById('fb-loading');
        const pageCard      = document.getElementById('fb-page-card');
        const pageImg       = document.getElementById('fb-page-img');
        const areasEl       = document.getElementById('fb-areas');
        const counter       = document.getElementById('fb-counter');
        const btnPrev       = document.getElementById('fb-btn-prev');
        const btnNext       = document.getElementById('fb-btn-next');
        const narasiGroup   = document.getElementById('fb-narasi-group');
        const btnNarasiToggle = document.getElementById('fb-btn-narasi-toggle');
        const audioBar      = document.getElementById('fb-audio-bar');
        const stage         = document.getElementById('fb-stage');

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
            const src = page && page.img ? page.img : '';
            console.log('fbRender', { idx: fbIdx, pageId: page && page.id, src });
            pageImg.src = src;
            pageImg.alt = page ? `Halaman ${page.nomor}` : '';
            fbRenderAreas(page);

            counter.textContent = `${page ? page.nomor : fbIdx + 1} / ${TOTAL}`;

            // Tampilkan narasi group jika halaman punya narasi untuk bahasa aktif
            const hasNarasiId = page && !!page.narasi_id;
            const hasNarasiSu = page && !!page.narasi_su;
            const hasActiveNarasi = (fbLang === 'id' && hasNarasiId) || 
                                    (fbLang === 'su' && hasNarasiSu) || 
                                    (fbLang === 'both' && (hasNarasiId || hasNarasiSu));
            narasiGroup.style.display = hasActiveNarasi ? 'flex' : 'none';
            fbUpdateNarasiButton();

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
                    let sources = [];
                    if (fbLang === 'id') {
                        if (area.audio_id) sources.push(area.audio_id);
                        else if (area.audio_su) sources.push(area.audio_su); // fallback
                    } else if (fbLang === 'su') {
                        if (area.audio_su) sources.push(area.audio_su);
                        else if (area.audio_id) sources.push(area.audio_id); // fallback
                    } else if (fbLang === 'both') {
                        if (area.audio_su) sources.push(area.audio_su);
                        if (area.audio_id) sources.push(area.audio_id);
                    }
                    fbPlayAreaAudio(sources, lbl.textContent, area.id, box);
                });

                areasEl.appendChild(box);
            });
        }



        function fbPlayAreaAudio(sources, label, areaId, boxEl) {
            if (!sources || !sources.length) return;
            fbStopAudio(false);

            fbAreaChain = true;
            boxEl.classList.add('fb-playing');
            if (audioBar) {
                audioBar.style.opacity = '1';
                audioBar.style.pointerEvents = 'all';
            }

            fbDuckBacksound(); // kurangi volume backsound saat audio interaktif mulai

            let curIdx = 0;

            function playNext() {
                if (!fbAreaChain || curIdx >= sources.length) {
                    boxEl.classList.remove('fb-playing');
                    if (audioBar) {
                        audioBar.style.opacity = '0';
                        audioBar.style.pointerEvents = 'none';
                    }
                    fbActiveAudio = null;
                    fbAreaChain = false;
                    fbRestoreBacksound(); // kembalikan volume backsound setelah selesai
                    return;
                }

                const src = sources[curIdx];
                if (!src) {
                    curIdx++;
                    playNext();
                    return;
                }

                // Tambahkan akhiran bahasa jika memutar SU & ID
                let suffix = '';
                if (sources.length > 1) {
                    suffix = (curIdx === 0) ? ' (Sunda)' : ' (Indonesia)';
                }

                fbActiveAudio = new Audio(src);
                fbActiveAudio.play().catch(()=>{});
                fbActiveAudio.addEventListener('ended', () => {
                    curIdx++;
                    playNext();
                });
            }

            playNext();
        }

        /* ── Audio Ducking Helpers ── */
        const FB_DUCK_RATIO   = 0.5;  // backsound dikurangi menjadi 50% saat foreground audio aktif
        let   fbBaseBsVolume  = 0.35; // volume dasar backsound (disinkronkan saat fbPlayBacksound dipanggil)
        let   fbDuckInterval  = null;

        function fbDuckBacksound() {
            if (!fbBacksound || fbBacksoundPaused) return;
            const target = fbBaseBsVolume * FB_DUCK_RATIO;
            fbAnimateVolume(fbBacksound, target);
        }

        function fbRestoreBacksound() {
            if (!fbBacksound || fbBacksoundPaused) return;
            fbAnimateVolume(fbBacksound, fbBaseBsVolume);
        }

        function fbAnimateVolume(audioEl, targetVol, durationMs = 350) {
            if (!audioEl) return;
            clearInterval(fbDuckInterval);
            const startVol = audioEl.volume;
            const diff     = targetVol - startVol;
            const steps    = 20;
            const stepMs   = durationMs / steps;
            let   step     = 0;
            fbDuckInterval = setInterval(() => {
                step++;
                audioEl.volume = Math.min(1, Math.max(0, startVol + diff * (step / steps)));
                if (step >= steps) clearInterval(fbDuckInterval);
            }, stepMs);
        }

        window.fbStopAudio = function(stopBack = false) {
            fbNarasiChain = false;
            fbNarasiPlaying = false;
            fbAreaChain = false;
            if (fbActiveAudio) { fbActiveAudio.pause(); fbActiveAudio = null; }
            document.querySelectorAll('.fb-area-box.fb-playing').forEach(b => b.classList.remove('fb-playing'));
            if (audioBar) {
                audioBar.style.opacity = '0';
                audioBar.style.pointerEvents = 'none';
            }
            fbUpdateNarasiButton();
            fbRestoreBacksound(); // kembalikan volume backsound
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
                btn.innerHTML = '🔇 Latar';
                btn.style.background = 'rgba(220,38,38,0.3)';
            } else {
                btn.innerHTML = '🔊 Latar';
                btn.style.background = 'var(--fb-primary)';
            }
        }

        function fbPlayBacksound(page) {
            if (fbBacksound) { fbBacksound.pause(); fbBacksound = null; }
            if (page && page.backsound) {
                fbBaseBsVolume = 0.35; // reset volume dasar
                fbBacksound = new Audio(page.backsound);
                fbBacksound.loop = true;
                fbBacksound.volume = fbBaseBsVolume;
                if (!fbBacksoundPaused) {
                    fbBacksound.play().catch(()=>{});
                }
            }
            fbUpdateBacksoundButton();
        }



        function fbUpdateNarasiButton() {
            const btn = document.getElementById('fb-btn-narasi-toggle');
            if (!btn) return;
            if (fbNarasiPlaying) {
                btn.innerHTML = '🔊 Narasi';
                btn.style.background = 'var(--fb-primary)';
            } else {
                btn.innerHTML = '🔇 Narasi';
                btn.style.background = 'rgba(220,38,38,0.3)';
            }
        }

        window.fbToggleNarasi = function() {
            if (fbNarasiPlaying) {
                fbStopAudio(false);
            } else {
                fbPlayActiveNarasi();
            }
        };

        window.fbPlayNarasi = function(lang) {
            const page = PAGES[fbIdx];
            if (!page) return;

            fbStopAudio(false);

            if (lang === 'both') {
                const srcSu = page.narasi_su;
                const srcId = page.narasi_id;

                if (!srcSu && !srcId) return;
                if (!srcSu) { fbPlayNarasi('id'); return; }
                if (!srcId) { fbPlayNarasi('su'); return; }

                fbNarasiChain = true;
                fbNarasiPlaying = true;
                fbUpdateNarasiButton();
                fbDuckBacksound(); // kurangi volume backsound saat narasi mulai

                fbActiveAudio = new Audio(srcSu);
                if (audioBar) {
                    audioBar.style.opacity = '1';
                    audioBar.style.pointerEvents = 'all';
                }
                fbActiveAudio.play().catch(()=>{});

                fbActiveAudio.addEventListener('ended', () => {
                    if (!fbNarasiChain) return;
                    fbActiveAudio = new Audio(srcId);
                    fbActiveAudio.play().catch(()=>{});
                    fbActiveAudio.addEventListener('ended', () => {
                        fbNarasiChain = false;
                        fbNarasiPlaying = false;
                        fbUpdateNarasiButton();
                        if (audioBar) {
                            audioBar.style.opacity = '0';
                            audioBar.style.pointerEvents = 'none';
                        }
                        fbActiveAudio = null;
                        fbRestoreBacksound(); // kembalikan volume backsound setelah narasi selesai
                    });
                });
            } else {
                const src = lang === 'su' ? page.narasi_su : page.narasi_id;
                if (!src) return;

                fbNarasiPlaying = true;
                fbUpdateNarasiButton();
                fbDuckBacksound(); // kurangi volume backsound saat narasi mulai

                fbActiveAudio = new Audio(src);
                if (audioBar) {
                    audioBar.style.opacity = '1';
                    audioBar.style.pointerEvents = 'all';
                }
                fbActiveAudio.play().catch(()=>{});

                fbActiveAudio.addEventListener('ended', () => {
                    fbNarasiPlaying = false;
                    fbUpdateNarasiButton();
                    if (audioBar) {
                        audioBar.style.opacity = '0';
                        audioBar.style.pointerEvents = 'none';
                    }
                    fbActiveAudio = null;
                    fbRestoreBacksound(); // kembalikan volume backsound setelah narasi selesai
                });
            }
        };

        window.fbPlayActiveNarasi = function() {
            fbPlayNarasi(fbLang);
        };

        /* ── Lang ── */
        window.fbSetLang = function(lang) {
            fbLang = lang;
            document.getElementById('fb-lang-id').style.background = lang === 'id' ? 'var(--fb-primary)' : 'transparent';
            document.getElementById('fb-lang-id').style.color      = lang === 'id' ? '#fff' : 'rgba(255,255,255,0.55)';
            document.getElementById('fb-lang-su').style.background = lang === 'su' ? 'var(--fb-primary)' : 'transparent';
            document.getElementById('fb-lang-su').style.color      = lang === 'su' ? '#fff' : 'rgba(255,255,255,0.55)';
            document.getElementById('fb-lang-both').style.background = lang === 'both' ? 'var(--fb-primary)' : 'transparent';
            document.getElementById('fb-lang-both').style.color      = lang === 'both' ? '#fff' : 'rgba(255,255,255,0.55)';
            fbStopAudio(false);
            fbRender();
            fbPlayActiveNarasi(); // Auto play narration!
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

        /* ── Scroll wheel navigation ── */
        let fbWheelLock = false;
        stage.addEventListener('wheel', e => {
            const dir = e.deltaY > 0 ? 1 : -1;
            const newIdx = fbIdx + dir;
            if (newIdx < 0 || newIdx >= TOTAL) {
                // Let the browser handle standard scroll if we cannot turn the page
                return;
            }
            e.preventDefault();
            if (fbWheelLock) return;
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
        function initFb() {
            fbSize();
            fbRender();
            setTimeout(() => {
                if (loading) loading.style.display = 'none';
            }, 350);
        }

        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            initFb();
        } else {
            window.addEventListener('load', initFb);
        }
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
<div id="modal-unpublish" class="fixed inset-0 z-50 hidden items-center justify-center" aria-modal="true" role="dialog">
    {{-- Backdrop --}}
    <div class="modal-backdrop absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-300 opacity-0"
         onclick="closePublishModal('modal-unpublish')"></div>

    {{-- Card --}}
    <div class="modal-card relative bg-white rounded-2xl shadow-2xl px-10 py-10 flex flex-col items-center gap-4
                min-w-[320px] max-w-[90vw] scale-90 opacity-0 transition-all duration-300" onclick="event.stopPropagation()">

        {{-- Icon --}}
        <div class="w-20 h-20 rounded-full bg-yellow-100 flex items-center justify-center text-3xl">
            ⚠️
        </div>

        {{-- Text --}}
        <div class="text-center">
            <p class="text-xl font-bold text-gray-900 mt-1">Kembalikan ke Draft?</p>
            <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                Buku <strong>{{ $buku->judul_idn }}</strong> akan disembunyikan dari aplikasi mobile Kaco Ceria dan tidak bisa diunduh pengguna hingga dipublikasikan kembali.
            </p>
        </div>

        {{-- Confirm buttons --}}
        <div class="flex gap-3 mt-2 w-full">
            <button type="button"
                    onclick="closePublishModal('modal-unpublish')"
                    class="flex-1 px-6 py-3 rounded-xl border-2 border-yellow-400 text-yellow-600 font-bold text-base hover:bg-yellow-50 transition-colors">
                Batal
            </button>
            <form action="{{ route('buku.updateStatus', $buku) }}" method="POST" class="flex-1">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status_publikasi" value="Draft">
                <input type="hidden" name="confirm_unpublish" value="yes">
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl bg-yellow-600 hover:bg-yellow-700 text-white font-bold text-base transition-colors shadow-md">
                    Ya, Konfirmasi
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Publikasikan --}}
<div id="modal-publish" class="fixed inset-0 z-50 hidden items-center justify-center" aria-modal="true" role="dialog">
    {{-- Backdrop --}}
    <div class="modal-backdrop absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-300 opacity-0"
         onclick="closePublishModal('modal-publish')"></div>

    {{-- Card --}}
    <div class="modal-card relative bg-white rounded-2xl shadow-2xl px-10 py-10 flex flex-col items-center gap-4
                min-w-[320px] max-w-[90vw] scale-90 opacity-0 transition-all duration-300" onclick="event.stopPropagation()">

        {{-- Icon --}}
        <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center text-3xl">
            🚀
        </div>

        {{-- Text --}}
        <div class="text-center">
            <p class="text-xl font-bold text-gray-900 mt-1">Publikasikan Buku?</p>
            <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                Buku <strong>{{ $buku->judul_idn }}</strong> akan diterbitkan. Buku akan dapat diunduh dan dibaca oleh pengguna di aplikasi mobile Kaco Ceria.
            </p>
        </div>

        {{-- Confirm buttons --}}
        <div class="flex gap-3 mt-2 w-full">
            <button type="button"
                    onclick="closePublishModal('modal-publish')"
                    class="flex-1 px-6 py-3 rounded-xl border-2 border-green-400 text-green-600 font-bold text-base hover:bg-green-50 transition-colors">
                Batal
            </button>
            <form action="{{ route('buku.updateStatus', $buku) }}" method="POST" class="flex-1">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status_publikasi" value="Terbit">
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-base transition-colors shadow-md">
                    Ya, Publikasikan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openPublishModal(id) {
        const modal = document.getElementById(id);
        const backdrop = modal.querySelector('.modal-backdrop');
        const card     = modal.querySelector('.modal-card');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            backdrop.classList.remove('opacity-0');
            card.classList.remove('scale-90', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    }

    function closePublishModal(id) {
        const modal = document.getElementById(id);
        const backdrop = modal.querySelector('.modal-backdrop');
        const card     = modal.querySelector('.modal-card');
        backdrop.classList.add('opacity-0');
        card.classList.add('scale-90', 'opacity-0');
        card.classList.remove('scale-100', 'opacity-100');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const flashData = document.getElementById('flash-data');
        if (flashData) {
            const err = flashData.getAttribute('data-error');
            const success = flashData.getAttribute('data-success');

            if (err) {
                ModalAlert.show('alertModal', {
                    title: 'Gagal Proses',
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

{{-- Komponen dan Script untuk mendeteksi SQS Loading --}}
    @if($buku->is_processing)
        <x-modal-loading id="sqsProcessModal" message="Buku berhasil diunggah! Sistem sedang mengonversi PDF Anda. Mohon tunggu..." />
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Tampilkan popup loading (mengunci layar agar tidak bisa di-klik)
                ModalAlert.loading('sqsProcessModal');
                
                // Lakukan pengecekan berkala dengan me-refresh halaman setiap 4 detik
                // Proses refresh tidak akan terasa mengganggu karena tertutup oleh popup loading
                setTimeout(() => {
                    window.location.reload();
                }, 4000);
            });
        </script>
    @endif

{{-- Komponen dan Script untuk mendeteksi Job Loading --}}
    @if($buku->is_processing)
        <x-modal-loading id="syncProcessModal" message="Proses perubahan informasi buku sedang dilakukan untuk menyesuaikan file multimedia. Mohon tunggu..." />
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Tampilkan popup loading (mengunci layar agar form form edit tidak bisa di-klik)
                ModalAlert.loading('syncProcessModal');
                
                // Lakukan pengecekan berkala dengan me-refresh halaman setiap 4 detik
                setTimeout(() => {
                    window.location.reload();
                }, 4000);
            });
        </script>
    @endif
@endsection

@endsection