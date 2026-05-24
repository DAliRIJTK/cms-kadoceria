@extends('layouts.dashboard')

@section('content')

<style>
    .panel-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    .section-header {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        padding-bottom: 12px;
        margin-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .box-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 12px;
        transition: box-shadow .15s;
    }
    .box-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .audio-block {
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 8px;
    }
    .audio-block.indo { background: #eff6ff; border: 1px solid #bfdbfe; }
    .audio-block.sunda { background: #f5f3ff; border: 1px solid #ddd6fe; }
    .audio-block.backsound { background: #fff7ed; border: 1px solid #fed7aa; }
    .audio-block-title { font-size: .75rem; font-weight: 600; margin-bottom: 6px; }
    .audio-block.indo .audio-block-title { color: #1e40af; }
    .audio-block.sunda .audio-block-title { color: #6d28d9; }
    .audio-block.backsound .audio-block-title { color: #c2410c; }
    .btn-upload-indo { background: #1e3a8a; }
    .btn-upload-indo:hover { background: #1e40af; }
    .btn-upload-sunda { background: #7c3aed; }
    .btn-upload-sunda:hover { background: #6d28d9; }
    .btn-upload-backsound { background: #d97706; }
    .btn-upload-backsound:hover { background: #b45309; }
    .btn-upload-base {
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: .75rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: background .15s;
    }
    .btn-delete {
        background: #dc2626;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: .75rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-delete:hover { background: #b91c1c; }
    .file-input-row { display: flex; gap: 6px; align-items: center; }
    .file-input-row input[type="file"] {
        flex: 1;
        font-size: .75rem;
        padding: 5px 8px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        background: #fff;
        color: #374151;
    }
    .existing-audio-row {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border-radius: 6px;
        padding: 6px 8px;
        margin-bottom: 6px;
    }
    .existing-audio-row audio { flex: 1; height: 28px; }
    #canvasWrapper {
        position: relative;
        border: 2px solid #cbd5e1;
        border-radius: 10px;
        overflow: auto;
        background: #f1f5f9;
        max-height: 520px;
    }
    #overlay {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        cursor: crosshair;
        background: transparent;
    }
    .pending-form {
        background: #fefce8;
        border: 2px solid #fde047;
        border-radius: 10px;
        padding: 16px;
        margin-top: 12px;
        display: none;
    }
    .pending-form input, .pending-form textarea {
        width: 100%;
        border: 1px solid #fde047;
        border-radius: 6px;
        padding: 7px 10px;
        font-size: .85rem;
        outline: none;
        transition: box-shadow .15s;
    }
    .pending-form input:focus, .pending-form textarea:focus {
        box-shadow: 0 0 0 2px #fbbf24;
    }
    .badge-new {
        display: inline-block;
        background: #fef9c3;
        color: #92400e;
        border-radius: 4px;
        font-size: .7rem;
        font-weight: 600;
        padding: 1px 7px;
        margin-left: 6px;
    }
    .right-panel { display: flex; flex-direction: column; gap: 16px; }
    .save-btn {
        background: #16a34a;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 22px;
        font-size: .9rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background .15s, transform .1s;
        box-shadow: 0 2px 8px rgba(22,163,74,.25);
    }
    .save-btn:hover { background: #15803d; transform: translateY(-1px); }
    .save-btn:disabled { background: #86efac; cursor: not-allowed; transform: none; box-shadow: none; }
</style>

<!-- HEADER -->
<div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
    <div>
        <a href="{{ url('/books/' . $page->book_id) }}" class="text-blue-900 hover:text-blue-800 text-sm font-medium">← Kembali ke Daftar Halaman</a>
        <h1 class="text-3xl font-bold text-gray-900 mt-1">Halaman {{ $page->page_number }}</h1>
        <p class="text-gray-500 text-sm mt-0.5">Kelola anotasi dan audio halaman</p>
    </div>
    <div class="flex items-center gap-3 flex-wrap">
        @if(isset($page->book->title))
            <span class="px-4 py-2 bg-blue-100 text-blue-900 rounded-full font-semibold text-sm border border-blue-200">
                {{ $page->book->title }}
            </span>
        @endif
        <button onclick="saveAll()" id="mainSaveBtn" class="save-btn" disabled>
            💾 Simpan Perubahan
        </button>
    </div>
</div>

<!-- ALERTS -->
@if (session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-5 flex gap-3">
        <span class="text-xl">✅</span>
        <p class="text-green-800 font-medium">{{ session('success') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-5 flex gap-3">
        <span class="text-xl">⚠️</span>
        <div>
            <h3 class="font-semibold text-red-900 mb-1">Terjadi Kesalahan</h3>
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li class="text-red-800 text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<!-- MAIN 2-COLUMN LAYOUT -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- ===== LEFT COLUMN: Canvas ===== -->
    <div class="panel-card p-5">
        <p class="section-header">Area Interaktif</p>
        <p class="text-xs text-gray-500 mb-3">Klik dan drag di halaman untuk membuat area interaktif lalu isi label dan audio</p>

        <div id="canvasWrapper">
            <img
                id="pageImage"
                src="{{ asset('storage/' . $page->image_url) }}"
                class="w-full block select-none pointer-events-none"
                draggable="false"
                alt="Halaman {{ $page->page_number }}"
            >
            <div id="overlay"></div>
        </div>

        <!-- Pending Box Form -->
        <div id="newBoxForm" class="pending-form">
            <p class="text-sm font-bold text-yellow-900 mb-3">➕ Area Interaktif Baru Terdeteksi</p>
            <div class="mb-3">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Label <span class="text-red-500">*</span></label>
                <input type="text" id="boxLabel" placeholder="Contoh: Mata, Telinga, Nama Karakter...">
            </div>
            <div class="mb-3">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Deskripsi (Opsional)</label>
                <textarea id="boxDesc" rows="2" placeholder="Keterangan tambahan..." style="resize:none;"></textarea>
            </div>
            <div class="flex gap-2 mt-2">
                <button onclick="confirmNewBox()" class="flex-1 py-2 rounded-lg text-white text-sm font-semibold transition" style="background:#1e3a8a;">✅ Tambahkan Area</button>
                <button onclick="cancelNewBox()" class="flex-1 py-2 rounded-lg text-white text-sm font-semibold transition" style="background:#6b7280;">❌ Batal</button>
            </div>
        </div>
    </div>

    <!-- ===== RIGHT COLUMN ===== -->
    <div class="right-panel">

        <!-- AREA INTERAKTIF LIST -->
        <div class="panel-card p-5">
            <div class="flex items-center justify-between mb-1 pb-3 border-b border-gray-100">
                <p class="section-header mb-0 pb-0 border-0">Area Interaktif (<span id="boxCount">{{ count($page->boundingBoxes) }}</span>)</p>
            </div>
            <div id="boxesList" class="mt-3">
                @if($page->boundingBoxes->isEmpty())
                    <p class="text-center text-gray-400 py-8 text-sm">Belum ada area interaktif.<br>Buat dengan drag di halaman kiri!</p>
                @endif
            </div>
        </div>

        <!-- AUDIO HALAMAN -->
        <div class="panel-card p-5">
            <p class="section-header">Audio Halaman</p>

            <!-- NARASI INDONESIA -->
            @php $narasiIndo = $page->audios->where('type', 'narration')->whereNull('bounding_box_id')->first(); @endphp
            <div class="audio-block indo mb-3">
                <p class="audio-block-title">🇮🇩 Narasi - Bahasa Indonesia</p>
                @if($narasiIndo)
                    <div class="existing-audio-row mb-2">
                        <audio controls><source src="{{ asset('storage/' . $narasiIndo->file_url) }}" type="audio/mpeg"></audio>
                        <form method="POST" action="{{ url('/audio/' . $narasiIndo->id) }}" style="margin:0;" onsubmit="return confirm('Hapus audio ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-delete">🗑️ Hapus</button>
                        </form>
                    </div>
                @endif
                <form action="{{ url('/pages/' . $page->id . '/audio') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="narration">
                    <div class="file-input-row">
                        <input type="file" name="audio_file" accept="audio/*">
                        <button type="submit" class="btn-upload-base btn-upload-indo">Unggah</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Max 10MB • WAV, M4A</p>
                </form>
            </div>

            <!-- NARASI SUNDA -->
            @php $narasiSunda = $page->audios->where('type', 'narration_sunda')->whereNull('bounding_box_id')->first(); @endphp
            <div class="audio-block sunda mb-3">
                <p class="audio-block-title">🇮🇩 Narasi - Bahasa Sunda</p>
                @if($narasiSunda)
                    <div class="existing-audio-row mb-2">
                        <audio controls><source src="{{ asset('storage/' . $narasiSunda->file_url) }}" type="audio/mpeg"></audio>
                        <form method="POST" action="{{ url('/audio/' . $narasiSunda->id) }}" style="margin:0;" onsubmit="return confirm('Hapus audio ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-delete">🗑️ Hapus</button>
                        </form>
                    </div>
                @endif
                <form action="{{ url('/pages/' . $page->id . '/audio') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="narration_sunda">
                    <div class="file-input-row">
                        <input type="file" name="audio_file" accept="audio/*">
                        <button type="submit" class="btn-upload-base btn-upload-sunda">Unggah</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Max 10MB • WAV, M4A</p>
                </form>
            </div>

            <!-- BACKSOUND -->
            @php $backsound = $page->audios->where('type', 'backsound')->whereNull('bounding_box_id')->first(); @endphp
            <div class="audio-block backsound">
                <p class="audio-block-title">🎵 Backsound Halaman</p>
                @if($backsound)
                    <div class="existing-audio-row mb-2">
                        <audio controls><source src="{{ asset('storage/' . $backsound->file_url) }}" type="audio/mpeg"></audio>
                        <form method="POST" action="{{ url('/audio/' . $backsound->id) }}" style="margin:0;" onsubmit="return confirm('Hapus audio ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-delete">🗑️ Hapus</button>
                        </form>
                    </div>
                @endif
                <form action="{{ url('/pages/' . $page->id . '/audio') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="backsound">
                    <div class="file-input-row">
                        <input type="file" name="audio_file" accept="audio/*">
                        <button type="submit" class="btn-upload-base btn-upload-backsound">Unggah</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Max 10MB • WAV, M4A</p>
                </form>
            </div>
        </div>

    </div><!-- /right-panel -->
</div><!-- /grid -->

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    let overlay     = document.getElementById('overlay');
    let wrapper     = document.getElementById('canvasWrapper');
    let boxesList   = document.getElementById('boxesList');
    let newBoxForm  = document.getElementById('newBoxForm');

    let boxes       = [];
    let currentBox  = null;
    let startX, startY;
    let isDrawing   = false;
    let pendingBox  = null;

    document.addEventListener('dragstart', e => e.preventDefault());

    // ========== LOAD EXISTING BOXES ==========
    function loadExistingBoxes() {
        const existingBoxes  = @json($page->boundingBoxes);
        const existingAudios = @json($page->audios);

        boxes = existingBoxes.map(box => ({
            id          : box.id,
            label       : box.label,
            description : box.description || '',
            x           : box.x,
            y           : box.y,
            width       : box.width,
            height      : box.height,
            isNew       : false,
            audios      : existingAudios.filter(a => a.bounding_box_id === box.id)
        }));
        renderBoxesList();
        renderOverlayBoxes();
    }

    // ========== RENDER OVERLAY MARKERS ON CANVAS ==========
    function renderOverlayBoxes() {
        // Remove old markers (keep only the drawing-div)
        overlay.querySelectorAll('.existing-marker').forEach(el => el.remove());

        boxes.forEach(box => {
            const marker = document.createElement('div');
            marker.className = 'existing-marker';
            marker.style.cssText = `
                position:absolute;
                left:${box.x}px; top:${box.y}px;
                width:${box.width}px; height:${box.height}px;
                border:2px solid #ef4444;
                background:rgba(239,68,68,0.08);
                border-radius:3px;
                pointer-events:none;
                z-index:5;
            `;
            overlay.appendChild(marker);
        });
    }

    // ========== DRAW NEW AREA ==========
    overlay.addEventListener('mousedown', function(e) {
        if (e.target !== overlay) return;
        const rect = wrapper.getBoundingClientRect();
        startX = e.clientX - rect.left + wrapper.scrollLeft;
        startY = e.clientY - rect.top  + wrapper.scrollTop;
        isDrawing = true;

        currentBox = document.createElement('div');
        currentBox.style.cssText = `
            position:absolute;
            border:2px dashed #1e3a8a;
            background:rgba(30,58,138,0.1);
            left:${startX}px; top:${startY}px;
            width:0; height:0;
            pointer-events:none;
            z-index:10;
            border-radius:4px;
        `;
        overlay.appendChild(currentBox);
    });

    overlay.addEventListener('mousemove', function(e) {
        if (!isDrawing) return;
        const rect    = wrapper.getBoundingClientRect();
        const currentX = e.clientX - rect.left + wrapper.scrollLeft;
        const currentY = e.clientY - rect.top  + wrapper.scrollTop;
        const w = currentX - startX;
        const h = currentY - startY;

        currentBox.style.width  = Math.abs(w) + 'px';
        currentBox.style.height = Math.abs(h) + 'px';
        currentBox.style.left   = (w < 0 ? currentX : startX) + 'px';
        currentBox.style.top    = (h < 0 ? currentY : startY) + 'px';
    });

    overlay.addEventListener('mouseup', () => {
        if (isDrawing) saveAreaPosition();
        isDrawing = false;
    });

    overlay.addEventListener('mouseleave', () => { isDrawing = false; });

    // ========== SAVE AREA POSITION ==========
    function saveAreaPosition() {
        if (currentBox && currentBox.offsetWidth > 10 && currentBox.offsetHeight > 10) {
            pendingBox = {
                label       : '',
                description : '',
                x           : parseFloat(currentBox.style.left),
                y           : parseFloat(currentBox.style.top),
                width       : currentBox.offsetWidth,
                height      : currentBox.offsetHeight,
                id          : 'new_' + Date.now()
            };
            showBoxForm();
        } else {
            if (currentBox && currentBox.parentNode) currentBox.remove();
        }
    }

    function showBoxForm() {
        document.getElementById('boxLabel').value = '';
        document.getElementById('boxDesc').value  = '';
        newBoxForm.style.display = 'block';
        document.getElementById('boxLabel').focus();
        newBoxForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    window.confirmNewBox = function() {
        const label = document.getElementById('boxLabel').value.trim();
        if (!label) { alert('Label harus diisi!'); return; }

        if (pendingBox) {
            pendingBox.label       = label;
            pendingBox.description = document.getElementById('boxDesc').value.trim();
            pendingBox.isNew       = true;
            pendingBox.audios      = [];
            boxes.push(pendingBox);
            pendingBox = null;

            renderBoxesList();
            renderOverlayBoxes();
            newBoxForm.style.display = 'none';
        }
    };

    window.cancelNewBox = function() {
        if (currentBox && currentBox.parentNode) currentBox.remove();
        pendingBox = null;
        newBoxForm.style.display = 'none';
    };

    window.deleteBox = function(id) {
        if (confirm('Hapus area ini?')) {
            boxes = boxes.filter(b => String(b.id) !== String(id));
            renderBoxesList();
            renderOverlayBoxes();
        }
    };

    // ========== RENDER BOX LIST ==========
    function renderBoxesList() {
        boxesList.innerHTML = '';
        const pageId    = {{ $page->id }};
        const csrfToken = '{{ csrf_token() }}';

        document.getElementById('boxCount').textContent = boxes.length;

        const saveBtn     = document.getElementById('mainSaveBtn');
        const newBoxCount = boxes.filter(b => b.isNew).length;
        saveBtn.disabled  = newBoxCount === 0;

        if (boxes.length === 0) {
            boxesList.innerHTML = '<p class="text-center text-gray-400 py-8 text-sm">Belum ada area interaktif.<br>Buat dengan drag di halaman kiri!</p>';
            return;
        }

        boxes.forEach(box => {
            const audioObjIndo  = box.audios ? box.audios.find(a => a.type === 'narration_object')       : null;
            const audioObjSunda = box.audios ? box.audios.find(a => a.type === 'narration_sunda_object') : null;

            const indoAudioHTML = `
                <div class="audio-block indo">
                    <p class="audio-block-title">🇮🇩 Audio Objek - Bahasa Indonesia</p>
                    ${audioObjIndo ? `
                        <div class="existing-audio-row">
                            <audio controls><source src="/storage/${audioObjIndo.file_url}" type="audio/mpeg"></audio>
                            <form action="/audio/${audioObjIndo.id}" method="POST" style="margin:0;" onsubmit="return confirm('Hapus?')">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn-delete">🗑️ Hapus</button>
                            </form>
                        </div>
                    ` : ''}
                    <form action="/pages/${pageId}/audio" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="type" value="narration_object">
                        <input type="hidden" name="box_id" value="${box.id}">
                        <div class="file-input-row">
                            <input type="file" name="audio_file" accept="audio/*" ${audioObjIndo ? 'disabled' : 'required'}>
                            <button type="submit" class="btn-upload-base btn-upload-indo" ${audioObjIndo ? 'disabled' : ''}>Unggah</button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Max 10MB - Suara saat objek dipilih</p>
                    </form>
                </div>
            `;

            const sundaAudioHTML = `
                <div class="audio-block sunda">
                    <p class="audio-block-title">🇮🇩 Audio Objek - Bahasa Sunda</p>
                    ${audioObjSunda ? `
                        <div class="existing-audio-row">
                            <audio controls><source src="/storage/${audioObjSunda.file_url}" type="audio/mpeg"></audio>
                            <form action="/audio/${audioObjSunda.id}" method="POST" style="margin:0;" onsubmit="return confirm('Hapus?')">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn-delete">🗑️ Hapus</button>
                            </form>
                        </div>
                    ` : ''}
                    <form action="/pages/${pageId}/audio" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="type" value="narration_sunda_object">
                        <input type="hidden" name="box_id" value="${box.id}">
                        <div class="file-input-row">
                            <input type="file" name="audio_file" accept="audio/*" ${audioObjSunda ? 'disabled' : 'required'}>
                            <button type="submit" class="btn-upload-base btn-upload-sunda" ${audioObjSunda ? 'disabled' : ''}>Unggah</button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Max 10MB - Suara saat objek dipilih</p>
                    </form>
                </div>
            `;

            const item = document.createElement('div');
            item.className = 'box-item';
            item.innerHTML = `
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span class="font-bold text-gray-900 text-sm">${box.label}</span>
                        ${box.isNew ? '<span class="badge-new">Baru</span>' : ''}
                        ${box.description ? `<p class="text-xs text-gray-500 mt-0.5">${box.description}</p>` : ''}
                        <p class="text-xs text-gray-400 mt-1">📍 Posisi: (${Math.round(box.x)}, ${Math.round(box.y)}) – Ukuran: ${Math.round(box.width)}×${Math.round(box.height)}px</p>
                    </div>
                    <button onclick="deleteBox('${box.id}')" class="btn-delete" style="flex-shrink:0; margin-left:8px; padding:6px 10px;">🗑️</button>
                </div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    ${indoAudioHTML}
                    ${sundaAudioHTML}
                </div>
            `;
            boxesList.appendChild(item);
        });
    }

    // ========== SAVE ALL NEW BOXES ==========
    window.saveAll = function() {
        const newBoxes = boxes.filter(b => b.isNew);
        if (newBoxes.length === 0) { alert('Tidak ada area baru untuk disimpan'); return; }

        const formData = new FormData();
        formData.append('_method', 'PATCH');
        formData.append('_token', '{{ csrf_token() }}');

        newBoxes.forEach((box, index) => {
            formData.append(`annotations[${index}][label]`,       box.label);
            formData.append(`annotations[${index}][description]`, box.description || '');
            formData.append(`annotations[${index}][x]`,           Math.round(box.x));
            formData.append(`annotations[${index}][y]`,           Math.round(box.y));
            formData.append(`annotations[${index}][width]`,       Math.round(box.width));
            formData.append(`annotations[${index}][height]`,      Math.round(box.height));
        });

        fetch(`/pages/{{ $page->id }}`, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Area berhasil disimpan!');
                    setTimeout(() => location.reload(), 300);
                } else {
                    alert('❌ Gagal: ' + (data.message || 'Error'));
                }
            })
            .catch(err => { console.error(err); alert('❌ Error: ' + err.message); });
    };

    loadExistingBoxes();
});
</script>
@endpush