@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ route('buku.show', $buku->id_buku) }}"
       class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Detail Buku</a>
    <h1 class="text-3xl font-bold text-gray-800">Edit Buku</h1>
    <p class="text-gray-500 mt-2">Perbarui informasi buku Anda</p>
</div>

<x-modal-alert id="alertModal" type="error" />
<x-modal-alert id="successModal" type="success" />

@php
    $primerRgb   = old('warna_primer',   $buku->warna_primer   ?? '99,102,241');
    $sekunderRgb = old('warna_sekunder', $buku->warna_sekunder ?? '168,85,247');
    $pArr = array_map('trim', explode(',', $primerRgb));
    $sArr = array_map('trim', explode(',', $sekunderRgb));
    $primerHex   = '#' . implode('', array_map(fn($v) => str_pad(dechex(intval($v)), 2, '0', STR_PAD_LEFT), $pArr));
    $sekunderHex = '#' . implode('', array_map(fn($v) => str_pad(dechex(intval($v)), 2, '0', STR_PAD_LEFT), $sArr));
@endphp

<form id="editBukuForm" method="POST" action="{{ route('buku.update', $buku->id_buku) }}" class="space-y-6" novalidate>
    @csrf
    @method('PATCH')

    {{-- Informasi Buku --}}
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">📖 Informasi Buku</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label for="judul_idn" class="block text-sm font-semibold text-gray-700 mb-2">
                    Judul Buku Indonesia <span class="text-red-500">*</span>
                </label>
                <input type="text" id="judul_idn" name="judul_idn"
                       value="{{ old('judul_idn', $buku->judul_idn) }}"
                       placeholder="Masukkan judul buku dalam bahasa Indonesia..."
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition {{ $errors->has('judul_idn') ? 'border-red-500' : '' }}">
                @error('judul_idn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="judul_sn" class="block text-sm font-semibold text-gray-700 mb-2">
                    Judul Buku Sunda <span class="text-red-500">*</span>
                </label>
                <input type="text" id="judul_sn" name="judul_sn"
                       value="{{ old('judul_sn', $buku->judul_sn) }}"
                       placeholder="Masukkan judul buku dalam bahasa Sunda..."
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition {{ $errors->has('judul_sn') ? 'border-red-500' : '' }}">
                @error('judul_sn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="penulis" class="block text-sm font-semibold text-gray-700 mb-2">
                    Penulis <span class="text-red-500">*</span>
                </label>
                <input type="text" id="penulis" name="penulis"
                       value="{{ old('penulis', $buku->penulis) }}" placeholder="Nama penulis..."
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition {{ $errors->has('penulis') ? 'border-red-500' : '' }}">
                @error('penulis')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="ilustrator" class="block text-sm font-semibold text-gray-700 mb-2">
                    Ilustrator <span class="text-red-500">*</span>
                </label>
                <input type="text" id="ilustrator" name="ilustrator"
                       value="{{ old('ilustrator', $buku->ilustrator) }}" placeholder="Nama ilustrator..."
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition {{ $errors->has('ilustrator') ? 'border-red-500' : '' }}">
                @error('ilustrator')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-6">
            <label for="deskripsi_idn" class="block text-sm font-semibold text-gray-700 mb-2">
                Sinopsis Buku Indonesia <span class="text-red-500">*</span>
            </label>
            <textarea id="deskripsi_idn" name="deskripsi_idn" rows="4"
                      placeholder="Tulis sinopsis singkat tentang buku ini dalam bahasa Indonesia..."
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none {{ $errors->has('deskripsi_idn') ? 'border-red-500' : '' }}">{{ old('deskripsi_idn', $buku->deskripsi_idn) }}</textarea>
            @error('deskripsi_idn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="mt-6">
            <label for="deskripsi_sn" class="block text-sm font-semibold text-gray-700 mb-2">
                Sinopsis Buku Sunda <span class="text-red-500">*</span>
            </label>
            <textarea id="deskripsi_sn" name="deskripsi_sn" rows="4"
                      placeholder="Tulis sinopsis singkat tentang buku ini dalam bahasa Sunda..."
                      required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none {{ $errors->has('deskripsi_sn') ? 'border-red-500' : '' }}">{{ old('deskripsi_sn', $buku->deskripsi_sn) }}</textarea>
            @error('deskripsi_sn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Warna Buku --}}
    <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-1">🎨 Warna Buku</h2>
        <p class="text-gray-400 text-xs mb-5">Warna primer dan sekunder digunakan untuk tampilan UI di aplikasi mobile</p>

        <div id="colorPreview"
             class="w-full h-14 rounded-xl mb-5 border border-gray-100 transition-all duration-300"
             style="background: linear-gradient(135deg, rgb({{ $primerRgb }}) 0%, rgb({{ $sekunderRgb }}) 100%);">
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
                    <span class="text-gray-400 text-xs font-bold shrink-0" aria-hidden="true">,</span>
                    <label for="primerG" class="sr-only">G Primer</label>
                    <input type="number" id="primerG" min="0" max="255"
                           value="{{ $pArr[1] ?? 102 }}" placeholder="G"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           oninput="onRgbChange('primer')">
                    <span class="text-gray-400 text-xs font-bold shrink-0" aria-hidden="true">,</span>
                    <label for="primerB" class="sr-only">B Primer</label>
                    <input type="number" id="primerB" min="0" max="255"
                           value="{{ $pArr[2] ?? 241 }}" placeholder="B"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-indigo-400"
                           oninput="onRgbChange('primer')">
                </div>
                <p class="text-[10px] text-gray-400 mt-1">R · G · B (0–255)</p>
                <input type="hidden" name="warna_primer" id="warnaPrimerHidden" value="{{ $primerRgb }}">
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
                    <span class="text-gray-400 text-xs font-bold shrink-0" aria-hidden="true">,</span>
                    <label for="sekunderG" class="sr-only">G Sekunder</label>
                    <input type="number" id="sekunderG" min="0" max="255"
                           value="{{ $sArr[1] ?? 85 }}" placeholder="G"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-purple-400"
                           oninput="onRgbChange('sekunder')">
                    <span class="text-gray-400 text-xs font-bold shrink-0" aria-hidden="true">,</span>
                    <label for="sekunderB" class="sr-only">B Sekunder</label>
                    <input type="number" id="sekunderB" min="0" max="255"
                           value="{{ $sArr[2] ?? 247 }}" placeholder="B"
                           class="w-full px-2 py-2 border border-gray-300 rounded-lg text-xs text-center focus:outline-none focus:ring-2 focus:ring-purple-400"
                           oninput="onRgbChange('sekunder')">
                </div>
                <p class="text-[10px] text-gray-400 mt-1">R · G · B (0–255)</p>
                <input type="hidden" name="warna_sekunder" id="warnaSekunderHidden" value="{{ $sekunderRgb }}">
            </div>
        </div>
    </div>

    <div class="flex gap-3 justify-end">
        <a href="{{ route('buku.show', $buku->id_buku) }}"
           class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium">
            Batal
        </a>
        <button type="submit"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium shadow-md hover:shadow-lg">
            💾 Simpan Perubahan
        </button>
    </div>
</form>

<script>
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
    const rEl = document.getElementById(type+'R');
    const gEl = document.getElementById(type+'G');
    const bEl = document.getElementById(type+'B');
    
    let r = rEl.value === '' ? '' : parseInt(rEl.value);
    let g = gEl.value === '' ? '' : parseInt(gEl.value);
    let b = bEl.value === '' ? '' : parseInt(bEl.value);
    
    if (r !== '' && !isNaN(r)) {
        if (r < 0) { r = 0; rEl.value = 0; }
        if (r > 255) { r = 255; rEl.value = 255; }
    }
    if (g !== '' && !isNaN(g)) {
        if (g < 0) { g = 0; gEl.value = 0; }
        if (g > 255) { g = 255; gEl.value = 255; }
    }
    if (b !== '' && !isNaN(b)) {
        if (b < 0) { b = 0; bEl.value = 0; }
        if (b > 255) { b = 255; bEl.value = 255; }
    }
    
    const rVal = r === '' || isNaN(r) ? 0 : r;
    const gVal = g === '' || isNaN(g) ? 0 : g;
    const bVal = b === '' || isNaN(b) ? 0 : b;
    
    document.getElementById('warna'+(type==='primer'?'Primer':'Sekunder')+'Hidden').value = `${rVal},${gVal},${bVal}`;
    document.getElementById(type+'Picker').value = rgbToHex(rVal,gVal,bVal);
    updatePreview();
}
</script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('editBukuForm');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            
            const fields = [
                { id: 'judul_idn', name: 'Judul buku Indonesia' },
                { id: 'judul_sn', name: 'Judul buku Sunda' },
                { id: 'penulis', name: 'Penulis' },
                { id: 'ilustrator', name: 'Ilustrator' },
                { id: 'deskripsi_idn', name: 'Sinopsis Indonesia' },
                { id: 'deskripsi_sn', name: 'Sinopsis Sunda' }
            ];

            for (const field of fields) {
                const input = document.getElementById(field.id);
                if (!input.value.trim()) {
                    input.focus();
                    input.classList.add('ring-2','ring-red-400','border-red-400');
                    setTimeout(() => input.classList.remove('ring-2','ring-red-400','border-red-400'), 2000);
                    ModalAlert.show('alertModal', { 
                        title: `${field.name} wajib diisi`, 
                        subtitle: `Silakan isi ${field.name.toLowerCase()} terlebih dahulu.` 
                    });
                    return;
                }
            }

            form.submit();
        });

        @if ($errors->has('duplicate_title'))
            ModalAlert.show('alertModal', { title: 'Judul Buku Sudah Ada', subtitle: @json($errors->first('duplicate_title')) });
        @elseif ($errors->any())
            ModalAlert.show('alertModal', {
                title: 'Gagal Menyimpan Perubahan',
                subtitle: '{{ $errors->first() }}'
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