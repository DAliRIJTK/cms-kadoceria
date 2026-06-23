@extends('layouts.dashboard')

@section('content')

{{-- ══ MODAL: Loading ══ --}}
<div id="loadingModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl px-16 py-12 flex flex-col items-center gap-6 min-w-[340px] max-w-[90vw]">
        <div class="w-20 h-20">
            <svg class="animate-spin w-20 h-20" viewBox="0 0 80 80" fill="none">
                <circle cx="40" cy="40" r="34" stroke="#e5e7eb" stroke-width="8"/>
                <path d="M40 6 A34 34 0 0 1 74 40" stroke="#1d4ed8" stroke-width="8" stroke-linecap="round"/>
            </svg>
        </div>
        <p class="text-gray-700 text-base font-medium text-center">Sedang mengonversi PDF dan menyimpan buku...</p>
    </div>
</div>

{{-- ══ MODAL: Alert ══ --}}
<style>
@keyframes pop-in {
    0%   { transform: scale(0.5); opacity: 0; }
    70%  { transform: scale(1.1); }
    100% { transform: scale(1);   opacity: 1; }
}
.animate-pop { animation: pop-in .45s cubic-bezier(.34,1.56,.64,1) both; }
@keyframes draw-check {
    from { stroke-dashoffset: 40; }
    to   { stroke-dashoffset: 0; }
}
.animate-draw { stroke-dasharray: 40; stroke-dashoffset: 40; animation: draw-check .4s .3s ease forwards; }
</style>

<div id="alertModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div id="alertBackdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity duration-300 opacity-0"></div>
    <div id="alertCard"
         class="relative bg-white rounded-2xl shadow-2xl px-10 py-10 flex flex-col items-center gap-4
                min-w-[320px] max-w-[90vw] scale-90 opacity-0 transition-all duration-300">
        <div id="alertIconWrap" class="w-20 h-20 rounded-full flex items-center justify-center"></div>
        <div class="text-center">
            <p id="alertTitle"    class="text-xl font-bold text-gray-900 mt-1"></p>
            <p id="alertSubtitle" class="text-sm text-gray-500 mt-1 hidden"></p>
        </div>
        <div class="w-full h-1 bg-gray-100 rounded-full overflow-hidden mt-1">
            <div id="alertBar" class="h-full rounded-full" style="width:100%"></div>
        </div>
    </div>
</div>

{{-- ══ HEADER ══ --}}
<div class="mb-8">
    <a href="/buku" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Daftar Buku</a>
    <h1 class="text-3xl font-bold text-gray-800">Tambah Buku Cerita Baru</h1>
    <p class="text-gray-500 mt-2">Unggah dan kelola buku dwibahasa interaktif Anda</p>
</div>

{{-- ══ FORM ══ --}}
<form id="createBukuForm" method="POST" action="{{ route('buku.store') }}"
      enctype="multipart/form-data" class="space-y-6" novalidate>
    @csrf

    {{-- Informasi Buku --}}
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">📖 Informasi Buku</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label for="judul_idn" class="block text-sm font-semibold text-gray-700 mb-2">
                    Judul Buku Indonesia <span class="text-red-500">*</span>
                </label>
                <input type="text" id="judul_idn" name="judul_idn"
                       value="{{ old('judul_idn') }}"
                       placeholder="Masukkan judul buku dalam bahasa Indonesia..."
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition {{ $errors->has('judul_idn') ? 'border-red-500' : '' }}">
                @error('judul_idn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="judul_sn" class="block text-sm font-semibold text-gray-700 mb-2">Judul Buku Sunda</label>
                <input type="text" id="judul_sn" name="judul_sn"
                       value="{{ old('judul_sn') }}"
                       placeholder="Masukkan judul buku dalam bahasa Sunda..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                <label for="penulis" class="block text-sm font-semibold text-gray-700 mb-2">Penulis</label>
                <input type="text" id="penulis" name="penulis"
                       value="{{ old('penulis') }}" placeholder="Nama penulis..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div>
                <label for="ilustrator" class="block text-sm font-semibold text-gray-700 mb-2">Ilustrator</label>
                <input type="text" id="ilustrator" name="ilustrator"
                       value="{{ old('ilustrator') }}" placeholder="Nama ilustrator..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
            </div>
        </div>

        <div class="mt-6">
            <label for="deskripsi_idn" class="block text-sm font-semibold text-gray-700 mb-2">Sinopsis Buku Indonesia</label>
            <textarea id="deskripsi_idn" name="deskripsi_idn" rows="4"
                      placeholder="Tulis sinopsis singkat tentang buku ini dalam bahasa Indonesia..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ old('deskripsi_idn') }}</textarea>
        </div>

        <div class="mt-6">
            <label for="deskripsi_sn" class="block text-sm font-semibold text-gray-700 mb-2">Sinopsis Buku Sunda</label>
            <textarea id="deskripsi_sn" name="deskripsi_sn" rows="4"
                      placeholder="Tulis sinopsis singkat tentang buku ini dalam bahasa Sunda..."
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none">{{ old('deskripsi_sn') }}</textarea>
        </div>
    </div>

    {{-- Warna Buku --}}
    @php
        $defPrimer   = old('warna_primer',   '99,102,241');
        $defSekunder = old('warna_sekunder',  '168,85,247');
        $pArr = array_map('trim', explode(',', $defPrimer));
        $sArr = array_map('trim', explode(',', $defSekunder));
        $primerHex   = '#' . implode('', array_map(fn($v) => str_pad(dechex(intval($v)), 2, '0', STR_PAD_LEFT), $pArr));
        $sekunderHex = '#' . implode('', array_map(fn($v) => str_pad(dechex(intval($v)), 2, '0', STR_PAD_LEFT), $sArr));
    @endphp

    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">🎨 Warna Buku</h2>
        <p class="text-gray-400 text-xs mb-5">Warna primer dan sekunder digunakan untuk tampilan UI di aplikasi mobile</p>

        <div id="colorPreview"
             class="w-full h-14 rounded-xl mb-5 border border-gray-100"
             style="background: linear-gradient(135deg, rgb({{ $defPrimer }}) 0%, rgb({{ $defSekunder }}) 100%);">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Warna Primer --}}
            <div>
                <label for="primerPicker" class="block text-sm font-semibold text-gray-700 mb-2">Warna Primer</label>
                <div class="flex items-center gap-2 mb-2">
                    <input type="color" id="primerPicker" value="{{ $primerHex }}"
                           class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 bg-white"
                           oninput="onPickerChange('primer', this.value)">
                    <span class="text-xs text-gray-500">Pilih dari palet warna</span>
                </div>
                <div class="flex items-center gap-1">
                    <label for="primerR" class="sr-only">R Primer</label>
                    <input type="number" id="primerR" min="0" max="255"
                           value="{{ $pArr[0] ?? 99 }}" placeholder="R"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           oninput="onRgbChange('primer')">
                    <span class="text-gray-400 text-xs font-bold shrink-0">,</span>
                    <label for="primerG" class="sr-only">G Primer</label>
                    <input type="number" id="primerG" min="0" max="255"
                           value="{{ $pArr[1] ?? 102 }}" placeholder="G"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           oninput="onRgbChange('primer')">
                    <span class="text-gray-400 text-xs font-bold shrink-0">,</span>
                    <label for="primerB" class="sr-only">B Primer</label>
                    <input type="number" id="primerB" min="0" max="255"
                           value="{{ $pArr[2] ?? 241 }}" placeholder="B"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           oninput="onRgbChange('primer')">
                </div>
                <p class="text-[10px] text-gray-400 mt-1">R · G · B (0–255)</p>
                <input type="hidden" name="warna_primer" id="warnaPrimerHidden" value="{{ $defPrimer }}">
            </div>

            {{-- Warna Sekunder --}}
            <div>
                <label for="sekunderPicker" class="block text-sm font-semibold text-gray-700 mb-2">Warna Sekunder</label>
                <div class="flex items-center gap-2 mb-2">
                    <input type="color" id="sekunderPicker" value="{{ $sekunderHex }}"
                           class="w-10 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 bg-white"
                           oninput="onPickerChange('sekunder', this.value)">
                    <span class="text-xs text-gray-500">Pilih dari palet warna</span>
                </div>
                <div class="flex items-center gap-1">
                    <label for="sekunderR" class="sr-only">R Sekunder</label>
                    <input type="number" id="sekunderR" min="0" max="255"
                           value="{{ $sArr[0] ?? 168 }}" placeholder="R"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-purple-400"
                           oninput="onRgbChange('sekunder')">
                    <span class="text-gray-400 text-xs font-bold shrink-0">,</span>
                    <label for="sekunderG" class="sr-only">G Sekunder</label>
                    <input type="number" id="sekunderG" min="0" max="255"
                           value="{{ $sArr[1] ?? 85 }}" placeholder="G"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-purple-400"
                           oninput="onRgbChange('sekunder')">
                    <span class="text-gray-400 text-xs font-bold shrink-0">,</span>
                    <label for="sekunderB" class="sr-only">B Sekunder</label>
                    <input type="number" id="sekunderB" min="0" max="255"
                           value="{{ $sArr[2] ?? 247 }}" placeholder="B"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-purple-400"
                           oninput="onRgbChange('sekunder')">
                </div>
                <p class="text-[10px] text-gray-400 mt-1">R · G · B (0–255)</p>
                <input type="hidden" name="warna_sekunder" id="warnaSekunderHidden" value="{{ $defSekunder }}">
            </div>
        </div>
    </div>

    {{-- Upload PDF --}}
    <div class="bg-blue-50 rounded-lg shadow-sm p-6 border border-blue-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-blue-200">📄 Unggah Buku Cerita PDF</h2>
        <div class="mb-4 p-4 bg-blue-100 rounded border border-blue-300">
            <p class="text-sm text-blue-900">
                <strong>Catatan:</strong> Sistem akan secara otomatis mengkonversi setiap halaman PDF menjadi gambar yang dapat dikelola.
            </p>
        </div>
        <div>
            <label for="pdf_file" class="block text-sm font-semibold text-gray-700 mb-1">
                File Buku (.pdf) <span class="text-red-500">*</span>
            </label>
            <p class="text-xs text-gray-500 mb-3">Maksimal 50MB • Format PDF • Nama file tidak boleh sama dengan yang sudah ada</p>

            <div class="flex flex-wrap gap-2 items-center">
                <label for="pdf_file"
                       class="flex-shrink-0 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium cursor-pointer hover:bg-gray-50 transition-colors shadow-sm">
                    Pilih File
                    <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" required class="hidden">
                </label>
                <span id="pdf-file-name"
                      class="flex-1 min-w-[160px] px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-400 truncate">
                    Belum ada file dipilih
                </span>
                <a href="/buku"
                   class="flex-shrink-0 px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors">
                    Batalkan
                </a>
                <button type="submit" id="submitBtn"
                        class="flex-shrink-0 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-sm transition-colors shadow-md">
                    Buat Buku
                </button>
            </div>

            <div id="fileSizeInfo" class="hidden mt-2 flex items-center gap-2">
                <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div id="fileSizeBar" class="h-full rounded-full transition-all duration-300 bg-blue-500" style="width:0%"></div>
                </div>
                <span id="fileSizeText" class="text-xs text-gray-500 whitespace-nowrap"></span>
            </div>

            @error('pdf_file')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
        </div>
    </div>
</form>

<script>
// ── Modal helpers (global) ────────────────────────────────────────────────────
const DISMISS_MS = 3000;
const MAX_BYTES  = 50 * 1024 * 1024;
const _icons = {
    error: {
        bg:  'bg-red-100',
        svg: '<svg class="w-10 h-10 text-red-500 animate-pop" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="currentColor" fill-opacity=".15"/><path d="M14 14l12 12M26 14L14 26" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',
        bar: 'bg-red-400',
    },
    success: {
        bg:  'bg-green-100',
        svg: '<svg class="w-10 h-10 text-green-500 animate-pop" viewBox="0 0 40 40" fill="none"><circle cx="20" cy="20" r="20" fill="currentColor" fill-opacity=".15"/><path d="M12 20l6 6 10-12" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="animate-draw"/></svg>',
        bar: 'bg-green-400',
    },
};
let _dismissTimer = null;

function showAlert(type, title, subtitle) {
    const cfg = _icons[type] || _icons.error;
    document.getElementById('alertIconWrap').className = 'w-20 h-20 rounded-full flex items-center justify-center ' + cfg.bg;
    document.getElementById('alertIconWrap').innerHTML  = cfg.svg;
    document.getElementById('alertBar').className       = 'h-full rounded-full ' + cfg.bar;
    document.getElementById('alertTitle').textContent   = title || '';
    const sub = document.getElementById('alertSubtitle');
    if (subtitle) { sub.textContent = subtitle; sub.classList.remove('hidden'); }
    else           { sub.classList.add('hidden'); }

    const modal    = document.getElementById('alertModal');
    const backdrop = document.getElementById('alertBackdrop');
    const card     = document.getElementById('alertCard');
    modal.classList.remove('hidden'); modal.classList.add('flex');
    requestAnimationFrame(() => {
        backdrop.classList.remove('opacity-0');
        card.classList.remove('scale-90','opacity-0');
        card.classList.add('scale-100','opacity-100');
    });

    const bar = document.getElementById('alertBar');
    bar.style.transition = 'none'; bar.style.width = '100%';
    requestAnimationFrame(() => requestAnimationFrame(() => {
        bar.style.transition = 'width ' + DISMISS_MS + 'ms linear';
        bar.style.width = '0%';
    }));
    clearTimeout(_dismissTimer);
    _dismissTimer = setTimeout(() => {
        backdrop.classList.add('opacity-0');
        card.classList.add('scale-90','opacity-0');
        card.classList.remove('scale-100','opacity-100');
        setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 300);
    }, DISMISS_MS);
}

function showLoading() {
    const m = document.getElementById('loadingModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}

// ── Warna picker helpers ──────────────────────────────────────────────────────
function hexToRgb(hex) {
    return { r: parseInt(hex.slice(1,3),16), g: parseInt(hex.slice(3,5),16), b: parseInt(hex.slice(5,7),16) };
}
function rgbToHex(r,g,b) {
    return '#' + [r,g,b].map(v => Math.max(0,Math.min(255,parseInt(v)||0)).toString(16).padStart(2,'0')).join('');
}
function updatePreview() {
    const pr = document.getElementById('primerR').value||0, pg = document.getElementById('primerG').value||0, pb = document.getElementById('primerB').value||0;
    const sr = document.getElementById('sekunderR').value||0, sg = document.getElementById('sekunderG').value||0, sb = document.getElementById('sekunderB').value||0;
    document.getElementById('colorPreview').style.background =
        `linear-gradient(135deg, rgb(${pr},${pg},${pb}) 0%, rgb(${sr},${sg},${sb}) 100%)`;
}
function onPickerChange(type, hex) {
    const {r,g,b} = hexToRgb(hex);
    document.getElementById(type+'R').value = r;
    document.getElementById(type+'G').value = g;
    document.getElementById(type+'B').value = b;
    document.getElementById('warna'+(type==='primer'?'Primer':'Sekunder')+'Hidden').value = `${r},${g},${b}`;
    updatePreview();
}
function onRgbChange(type) {
    const r = document.getElementById(type+'R').value||0;
    const g = document.getElementById(type+'G').value||0;
    const b = document.getElementById(type+'B').value||0;
    document.getElementById('warna'+(type==='primer'?'Primer':'Sekunder')+'Hidden').value = `${r},${g},${b}`;
    document.getElementById(type+'Picker').value = rgbToHex(r,g,b);
    updatePreview();
}

// ── Form submit & file picker ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const fileInput    = document.getElementById('pdf_file');
    const fileNameSpan = document.getElementById('pdf-file-name');
    const fileSizeInfo = document.getElementById('fileSizeInfo');
    const fileSizeBar  = document.getElementById('fileSizeBar');
    const fileSizeText = document.getElementById('fileSizeText');
    const form         = document.getElementById('createBukuForm');
    const submitBtn    = document.getElementById('submitBtn');

    fileInput.addEventListener('change', function () {
        if (!this.files.length) return;
        const file = this.files[0];
        const sizeMB = (file.size/1024/1024).toFixed(2);
        const pct    = Math.min(file.size/MAX_BYTES*100, 100).toFixed(1);
        fileNameSpan.textContent = file.name;
        fileNameSpan.classList.replace('text-gray-400','text-gray-700');
        fileSizeInfo.classList.remove('hidden');
        fileSizeBar.style.width  = pct + '%';
        fileSizeText.textContent = sizeMB + ' MB / 50 MB';
        if (file.size > MAX_BYTES) {
            fileSizeBar.classList.replace('bg-blue-500','bg-red-500');
            fileSizeText.classList.add('text-red-500');
        } else {
            fileSizeBar.classList.replace('bg-red-500','bg-blue-500');
            fileSizeText.classList.remove('text-red-500');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const judulInput = document.getElementById('judul_idn');
        if (!judulInput.value.trim()) {
            judulInput.focus();
            judulInput.classList.add('ring-2','ring-red-400','border-red-400');
            setTimeout(() => judulInput.classList.remove('ring-2','ring-red-400','border-red-400'), 2000);
            showAlert('error','Judul buku wajib diisi','Silakan isi judul buku terlebih dahulu.');
            return;
        }
        if (!fileInput.files.length) {
            showAlert('error','File PDF wajib dipilih','Silakan pilih file PDF buku terlebih dahulu.');
            return;
        }
        if (fileInput.files[0].size > MAX_BYTES) {
            showAlert('error','File terlalu besar','Ukuran file PDF maksimal 50MB.');
            fileInput.value = ''; fileNameSpan.textContent = 'Belum ada file dipilih';
            fileSizeInfo.classList.add('hidden'); return;
        }
        showLoading(); submitBtn.disabled = true; form.submit();
    });

    @if ($errors->has('duplicate_title'))
        showAlert('error', 'Nama file PDF sudah ada', @json($errors->first('duplicate_title')));
    @endif
    @if ($errors->has('error'))
        showAlert('error', 'Terjadi kesalahan', @json($errors->first('error')));
    @endif
    @if ($errors->has('pdf_file'))
        showAlert('error', 'File tidak valid', @json($errors->first('pdf_file')));
    @endif
    @if (session('success'))
        showAlert('success', 'Berhasil!', @json(session('success')));
    @endif
});
</script>

@endsection