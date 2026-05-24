@extends('layouts.dashboard')

@section('content')

@if($errors->has('publication'))
    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h3 class="font-semibold text-red-900 mb-1">Publikasi Tidak Tersedia</h3>
                <p class="text-red-800 text-sm">{{ $errors->first('publication') }}</p>
            </div>
        </div>
    </div>
@endif

@if($errors->has('delete'))
    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200">
        <div class="flex items-start gap-3">
            <span class="text-2xl">❌</span>
            <div>
                <h3 class="font-semibold text-red-900 mb-1">Gagal Menghapus Buku</h3>
                <p class="text-red-800 text-sm">{{ $errors->first('delete') }}</p>
            </div>
        </div>
    </div>
@endif

@if(session('success'))
    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
        <div class="flex items-start gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="text-green-800 text-sm font-medium">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

<div class="mb-8">
    <a href="/books" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Daftar Buku</a>

    <div class="flex items-start justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">📖 {{ $book->title }}</h1>
            <p class="text-gray-600">Kelola halaman dan konten interaktif buku Anda</p>
        </div>
        <div class="text-right">
            <span class="inline-flex items-center px-4 py-2 rounded-full font-semibold {{ $book->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                {{ $book->status === 'published' ? '✅ Published' : '📋 Draft' }}
            </span>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6 mb-8 border border-gray-200">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Penulis</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->author ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Penerbit</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->publisher ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Halaman</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->pages()->count() }}</p>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Dibuat Pada</p>
            <p class="text-lg font-semibold text-gray-800">{{ $book->created_at->format('d M Y') }}</p>
        </div>
    </div>

    @if($book->description)
        <div class="pt-6 border-t border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Deskripsi</p>
            <p class="text-gray-700 leading-relaxed">{{ $book->description }}</p>
        </div>
    @endif

    <div class="mt-6 pt-6 border-t border-gray-200 flex gap-3 flex-wrap">
        <a href="{{ url('/books/' . $book->id . '/edit') }}" class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors font-medium">
            ✏️ Edit Informasi
        </a>
        <a href="{{ url('/books/' . $book->id . '/pages/upload') }}" class="px-4 py-2 bg-purple-50 text-purple-600 hover:bg-purple-100 rounded-lg transition-colors font-medium">
            + Tambah Halaman
        </a>
        <a href="/pages-management?book_id={{ $book->id }}" class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors font-medium">
            📄 Kelola Halaman
        </a>
        <form method="POST" action="{{ url('/books/' . $book->id . '/status') }}" class="inline" id="statusForm" onsubmit="return handleStatusChange(event);">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="{{ $book->status === 'published' ? 'draft' : 'published' }}" id="statusInput">
            <input type="hidden" name="confirm_unpublish" value="no" id="confirmUnpublish">
            <button type="submit" class="px-4 py-2 {{ $book->status === 'published' ? 'bg-orange-50 text-orange-600 hover:bg-orange-100' : 'bg-green-50 text-green-600 hover:bg-green-100' }} rounded-lg transition-colors font-medium">
                {{ $book->status === 'published' ? '📋 Ubah ke Draft' : '✅ Publikasikan' }}
            </button>
        </form>
    </div>
</div>

<!-- FLIPBOOK PREVIEW SECTION -->
<div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">👁️ Pratinjau Flipbook</h2>

    @if($book->pages()->count() === 0)
        <div class="bg-gray-100 rounded-lg p-8 flex items-center justify-center min-h-96 border-2 border-dashed border-gray-300">
            <div class="text-center">
                <p class="text-gray-500 text-lg mb-2">📭 Belum Ada Halaman</p>
                <p class="text-gray-400 text-sm">Tambahkan halaman terlebih dahulu untuk melihat preview flipbook</p>
            </div>
        </div>
    @else
        <!-- Thumbnail strip preview -->
        <div class="flex gap-3 overflow-x-auto pb-3 mb-5">
            @foreach($book->pages->take(6) as $page)
                <div class="flex-shrink-0 w-28 rounded-lg overflow-hidden border border-gray-200 shadow-sm">
                    <img src="{{ asset('storage/' . $page->image_url) }}"
                         alt="Halaman {{ $page->page_number }}"
                         class="w-full h-36 object-cover">
                    <div class="bg-gray-50 text-center py-1">
                        <span class="text-xs text-gray-500 font-medium">Hal. {{ $page->page_number }}</span>
                    </div>
                </div>
            @endforeach
            @if($book->pages->count() > 6)
                <div class="flex-shrink-0 w-28 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center h-44">
                    <span class="text-gray-400 text-xs text-center px-2">+{{ $book->pages->count() - 6 }} halaman lagi</span>
                </div>
            @endif
        </div>

        <div class="flex justify-center">
            <button onclick="openFlipbookModal()"
                class="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-base transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5">
                📖 Buka Pratinjau Penuh
            </button>
        </div>
    @endif
</div>

<!-- ===== FLIPBOOK MODAL ===== -->
@if($book->pages()->count() > 0)
<div id="flipbookModal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background: rgba(0,0,0,0.88);">

    <style>
        #flipbookModal .modal-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            height: 100%;
            padding: 16px;
            box-sizing: border-box;
        }
        /* Top bar */
        #flipbookModal .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1000px;
            margin-bottom: 12px;
            flex-shrink: 0;
        }
        #flipbookModal .topbar h3 {
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }
        #flipbookModal .btn-close {
            background: rgba(255,255,255,0.15);
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 6px 16px;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }
        #flipbookModal .btn-close:hover { background: rgba(255,255,255,0.28); }

        /* Flipbook stage */
        #flipbook-stage {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex: 1;
            min-height: 0;
            width: 100%;
            max-width: 1000px;
        }
        #flipbook-container {
            /* sized dynamically by JS */
            position: relative;
            flex-shrink: 0;
        }
        /* StPageFlip canvas wrapper */
        #flipbook-container .stf__parent {
            border-radius: 4px;
            overflow: hidden;
        }

        /* Nav buttons */
        .fb-nav-btn {
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.3);
            color: #fff;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s, border-color .15s;
            flex-shrink: 0;
        }
        .fb-nav-btn:hover { background: rgba(255,255,255,0.28); border-color: rgba(255,255,255,0.6); }
        .fb-nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }

        /* Page counter */
        #flipbookModal .page-counter {
            color: rgba(255,255,255,0.7);
            font-size: .82rem;
            text-align: center;
            margin-top: 10px;
            flex-shrink: 0;
        }

        /* Loading overlay */
        #fb-loading {
            position: absolute;
            inset: 0;
            background: #1e293b;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 99;
            color: #fff;
        }
        #fb-loading .spinner {
            width: 36px; height: 36px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            margin-bottom: 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>

    <div class="modal-inner">
        <!-- Top bar -->
        <div class="topbar">
            <h3>📖 {{ $book->title }}</h3>
            <button class="btn-close" onclick="closeFlipbookModal()">✕ Tutup</button>
        </div>

        <!-- Flipbook stage -->
        <div id="flipbook-stage">
            <button class="fb-nav-btn" id="fb-prev" onclick="flipPrev()" disabled>‹</button>

            <div id="flipbook-container">
                <div id="fb-loading">
                    <div class="spinner"></div>
                    <span style="font-size:.85rem; color:rgba(255,255,255,0.7);">Memuat halaman...</span>
                </div>
                <!-- StPageFlip renders here -->
            </div>

            <button class="fb-nav-btn" id="fb-next" onclick="flipNext()">›</button>
        </div>

        <div class="page-counter" id="fb-counter">Halaman 1 dari {{ $book->pages->count() }}</div>
    </div>
</div>

<!-- StPageFlip from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js" onerror="loadPageFlipFallback()"></script>

<script>
// Page images array (sorted by page_number)
const BOOK_PAGES = @json(
    $book->pages->sortBy('page_number')->map(fn($p) => [
        'url'    => asset('storage/' . $p->image_url),
        'number' => $p->page_number,
    ])->values()
);

const TOTAL_PAGES = BOOK_PAGES.length;
let pageFlipInstance = null;
let currentPage = 1;
let flipLibLoaded = false;

// Try StPageFlip (modern, no jQuery)
function loadPageFlipFallback() {
    const s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/page-flip/2.0.7/js/page-flip.browser.js';
    s.onload  = () => { flipLibLoaded = 'pageflip'; };
    s.onerror = () => { flipLibLoaded = 'canvas'; };
    document.head.appendChild(s);
}

// Detect which lib loaded
document.addEventListener('DOMContentLoaded', () => {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.turn !== 'undefined') {
        flipLibLoaded = 'turn';
    }
});

// ========== OPEN / CLOSE ==========
function openFlipbookModal() {
    const modal = document.getElementById('flipbookModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';

    setTimeout(() => initFlipbook(), 80);
}

function closeFlipbookModal() {
    const modal = document.getElementById('flipbookModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';

    // Destroy instance
    if (pageFlipInstance) {
        try {
            if (typeof pageFlipInstance.destroy === 'function') pageFlipInstance.destroy();
        } catch(e) {}
        pageFlipInstance = null;
    }

    // Reset container
    const container = document.getElementById('flipbook-container');
    container.innerHTML = `
        <div id="fb-loading">
            <div class="spinner"></div>
            <span style="font-size:.85rem; color:rgba(255,255,255,0.7);">Memuat halaman...</span>
        </div>
    `;
    currentPage = 1;
    updateCounter();
    updateNavBtns();
}

// ========== INIT FLIPBOOK ==========
function initFlipbook() {
    const stage     = document.getElementById('flipbook-stage');
    const container = document.getElementById('flipbook-container');

    // Calculate dimensions to fit viewport
    const navW      = 48 + 16; // button + gap each side
    const availW    = Math.min(stage.clientWidth - navW * 2, 900);
    const availH    = stage.clientHeight - 10;

    // Two-page spread: each page = availW/2, maintain ~4:3 ratio
    const pageW     = Math.floor(Math.min(availW / 2, (availH * 3) / 4));
    const pageH     = Math.floor(pageW * (4 / 3));
    const totalW    = pageW * 2;

    container.style.width  = totalW + 'px';
    container.style.height = pageH + 'px';

    // Use StPageFlip if available, else canvas fallback
    if (typeof St !== 'undefined' && St.PageFlip) {
        initWithPageFlip(container, pageW, pageH);
    } else {
        // Small delay to allow CDN script to execute
        setTimeout(() => {
            if (typeof St !== 'undefined' && St.PageFlip) {
                initWithPageFlip(container, pageW, pageH);
            } else {
                initCanvasFallback(container, pageW, pageH);
            }
        }, 600);
    }
}

// ========== StPageFlip ==========
function initWithPageFlip(container, pageW, pageH) {
    container.innerHTML = ''; // clear loading

    pageFlipInstance = new St.PageFlip(container, {
        width       : pageW,
        height      : pageH,
        size        : 'fixed',
        showCover   : false,
        mobileScrollSupport: false,
        drawShadow  : true,
        flippingTime: 700,
        usePortrait : false,
    });

    const imgs = BOOK_PAGES.map(p => {
        const img = document.createElement('img');
        img.src   = p.url;
        img.style.cssText = `width:${pageW}px; height:${pageH}px; object-fit:cover; display:block;`;
        return img;
    });

    pageFlipInstance.loadFromHTML(imgs);

    pageFlipInstance.on('flip', (e) => {
        currentPage = e.data + 1;
        updateCounter();
        updateNavBtns();
    });

    updateCounter();
    updateNavBtns();
}

// ========== Canvas fallback (no lib) ==========
function initCanvasFallback(container, pageW, pageH) {
    container.innerHTML = '';

    // Simple two-page canvas viewer
    const canvas  = document.createElement('canvas');
    canvas.width  = pageW * 2;
    canvas.height = pageH;
    canvas.style.cssText = `display:block; border-radius:4px; box-shadow: 0 8px 40px rgba(0,0,0,0.6);`;
    container.appendChild(canvas);

    pageFlipInstance = { type: 'canvas', canvas, pageW, pageH };
    renderCanvasSpread(0);
    updateCounter();
    updateNavBtns();
}

function renderCanvasSpread(leftIndex) {
    const { canvas, pageW, pageH } = pageFlipInstance;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#1e293b';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Center divider
    ctx.strokeStyle = 'rgba(255,255,255,0.15)';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(pageW, 0);
    ctx.lineTo(pageW, pageH);
    ctx.stroke();

    const loadAndDraw = (idx, dx) => {
        if (idx < 0 || idx >= BOOK_PAGES.length) return;
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => ctx.drawImage(img, dx, 0, pageW, pageH);
        img.src = BOOK_PAGES[idx].url;
    };

    loadAndDraw(leftIndex, 0);
    loadAndDraw(leftIndex + 1, pageW);
}

// ========== NAVIGATION ==========
function flipNext() {
    if (pageFlipInstance && pageFlipInstance.flipNext) {
        pageFlipInstance.flipNext('bottom');
    } else if (pageFlipInstance && pageFlipInstance.type === 'canvas') {
        const leftIdx = (currentPage - 1) + 2;
        if (leftIdx >= TOTAL_PAGES) return;
        currentPage = Math.min(currentPage + 2, TOTAL_PAGES);
        renderCanvasSpread(currentPage - 1);
        updateCounter();
        updateNavBtns();
    }
}

function flipPrev() {
    if (pageFlipInstance && pageFlipInstance.flipPrev) {
        pageFlipInstance.flipPrev('bottom');
    } else if (pageFlipInstance && pageFlipInstance.type === 'canvas') {
        currentPage = Math.max(currentPage - 2, 1);
        renderCanvasSpread(currentPage - 1);
        updateCounter();
        updateNavBtns();
    }
}

function updateCounter() {
    const el = document.getElementById('fb-counter');
    if (!el) return;
    const right = Math.min(currentPage + 1, TOTAL_PAGES);
    el.textContent = (currentPage === right)
        ? `Halaman ${currentPage} dari ${TOTAL_PAGES}`
        : `Halaman ${currentPage}–${right} dari ${TOTAL_PAGES}`;
}

function updateNavBtns() {
    const prev = document.getElementById('fb-prev');
    const next = document.getElementById('fb-next');
    if (prev) prev.disabled = currentPage <= 1;
    if (next) next.disabled = currentPage >= TOTAL_PAGES - 1;
}

// Close on backdrop click
document.getElementById('flipbookModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeFlipbookModal();
});

// Keyboard nav
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('flipbookModal');
    if (modal && modal.classList.contains('flex')) {
        if (e.key === 'ArrowRight') flipNext();
        if (e.key === 'ArrowLeft')  flipPrev();
        if (e.key === 'Escape')     closeFlipbookModal();
    }
});

function handleStatusChange(e) {
    e.preventDefault();
    const form            = e.target;
    const statusInput     = document.getElementById('statusInput');
    const confirmUnpublish = document.getElementById('confirmUnpublish');
    const currentStatus   = '{{ $book->status }}';

    if (currentStatus === 'published' && statusInput.value === 'draft') {
        if (confirm('⚠️ PERINGATAN: Anda akan menarik buku ini dari peredaran.\n\nApakah Anda yakin ingin melanjutkan?')) {
            confirmUnpublish.value = 'yes';
            form.submit();
        }
        return false;
    } else {
        return confirm('Ubah status publikasi?');
    }
}
</script>

<!-- Load StPageFlip from CDN -->
<script>
(function() {
    const s = document.createElement('script');
    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/page-flip/2.0.7/js/page-flip.browser.js';
    document.head.appendChild(s);
})();
</script>
@endif

@endsection