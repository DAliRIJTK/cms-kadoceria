@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ url('/books/' . $page->book_id) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali</a>
    <h1 class="text-3xl font-bold text-gray-800">✏️ Edit Halaman {{ $page->page_number }}</h1>
    <p class="text-gray-500 mt-2">Kelola anotasi dan audio pada halaman ini</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">📄 Editor Halaman</h2>
            
            <div id="canvasWrapper"
                class="relative border-2 border-gray-300 rounded-lg overflow-auto bg-gray-50"
                style="max-height: 600px;">

                <img
                    id="pageImage"
                    src="{{ asset('storage/' . $page->image_url) }}"
                    class="w-full block select-none pointer-events-none"
                    draggable="false"
                    alt="Halaman {{ $page->page_number }}"
                >

                <div id="overlay"
                    class="absolute top-0 left-0 w-full h-full cursor-crosshair bg-transparent">
                </div>

            </div>

            <p class="text-xs text-gray-600 mt-3 p-3 bg-blue-50 rounded border border-blue-200">
            </p>
        </div>
    </div>

        <div class="space-y-6">
        
                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">📝 Anotasi ({{ $page->boundingBoxes->count() }})</h3>

                        <div class="space-y-3 mb-4">
                <input type="text" id="annotationLabel"
                    placeholder="Label anotasi..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <textarea id="annotationText"
                    placeholder="Deskripsi anotasi (opsional)..."
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none"></textarea>
                <button onclick="addAnnotation()"
                    class="w-full bg-orange-600 hover:bg-orange-700 text-white py-2 rounded-lg transition-colors font-medium">
                    + Buat Anotasi Baru
                </button>
            </div>

                        <div id="annotationList" class="space-y-2 max-h-64 overflow-y-auto">
                @if($page->boundingBoxes->isEmpty())
                    <p class="text-xs text-gray-500 text-center py-4">Belum ada anotasi</p>
                @else
                    @foreach($page->boundingBoxes as $box)
                        <div class="p-2 bg-orange-50 border border-orange-200 rounded-lg text-xs">
                            <p class="font-semibold text-orange-900">{{ $box->label ?? 'Anotasi ' . $loop->iteration }}</p>
                            <p class="text-orange-700">Pos: ({{ $box->x }}, {{ $box->y }})</p>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

                <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-3 border-b border-gray-200">🔊 Audio Halaman ({{ $page->audios->count() }})</h3>

            <div class="space-y-3 mb-4 text-sm">
                <div class="bg-purple-50 p-3 rounded-lg border border-purple-200">
                    <p class="font-semibold text-purple-900 mb-1">🎤 Narasi</p>
                    <p class="text-purple-700 text-xs mb-2">{{ $page->audios()->where('type', 'narration')->count() }} file</p>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                    <p class="font-semibold text-blue-900 mb-1">🎵 Backsound</p>
                    <p class="text-blue-700 text-xs mb-2">{{ $page->audios()->where('type', 'backsound')->count() }} file</p>
                </div>
                <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                    <p class="font-semibold text-green-900 mb-1">🎯 Audio Objek</p>
                    <p class="text-green-700 text-xs mb-2">{{ $page->audios()->where('type', 'object')->count() }} file</p>
                </div>
            </div>

            <a href="{{ url('/pages/' . $page->id . '/audio') }}" class="w-full block text-center bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition-colors font-medium text-sm">
                ⚙️ Kelola Audio
            </a>
        </div>

        <button onclick="saveAll()"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors font-semibold shadow-md">
            💾 Simpan Perubahan Anotasi
        </button>
    </div>

</div>

@endsection

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    let overlay = document.getElementById('overlay');
    let wrapper = document.getElementById('canvasWrapper');
    let annotationList = document.getElementById('annotationList');
    let audioList = document.getElementById('audioList');

    let annotations = [];
    let audioItems = [];
    let currentBox = null;
    let startX, startY;
    let isDrawing = false;
    let activeType = 'annotation'; // 'annotation' or 'audio'

    document.addEventListener('dragstart', e => e.preventDefault());

    // ================= DRAW (FR-12 - Position annotation/audio) =================
    overlay.addEventListener('mousedown', function(e) {
        const rect = wrapper.getBoundingClientRect();
        startX = e.clientX - rect.left;
        startY = e.clientY - rect.top;
        isDrawing = true;

        currentBox = document.createElement('div');
        currentBox.style.position = 'absolute';
        currentBox.style.border = activeType === 'annotation' ? '2px dashed #ff9800' : '2px dashed #4caf50';
        currentBox.style.backgroundColor = activeType === 'annotation' ? 'rgba(255, 152, 0, 0.1)' : 'rgba(76, 175, 80, 0.1)';
        currentBox.style.left = startX + 'px';
        currentBox.style.top = startY + 'px';
        currentBox.style.width = '0px';
        currentBox.style.height = '0px';
        currentBox.style.pointerEvents = 'none';

        overlay.appendChild(currentBox);
    });

    overlay.addEventListener('mousemove', function(e) {
        if (!isDrawing) return;
        const rect = wrapper.getBoundingClientRect();
        const currentX = e.clientX - rect.left;
        const currentY = e.clientY - rect.top;
        const width = currentX - startX;
        const height = currentY - startY;

        currentBox.style.width = Math.abs(width) + 'px';
        currentBox.style.height = Math.abs(height) + 'px';
        currentBox.style.left = (width < 0 ? currentX : startX) + 'px';
        currentBox.style.top = (height < 0 ? currentY : startY) + 'px';
    });

    overlay.addEventListener('mouseup', () => {
        if (isDrawing && activeType === 'annotation') {
            saveAnnotationPosition();
        } else if (isDrawing && activeType === 'audio') {
            saveAudioPosition();
        }
        isDrawing = false;
    });

    overlay.addEventListener('mouseleave', () => isDrawing = false);

    // ================= ANNOTATION FUNCTIONS (FR-11, FR-12, FR-13, FR-14) =================
    window.addAnnotation = function() {
        const label = document.getElementById('annotationLabel').value;
        const text = document.getElementById('annotationText').value;

        if (!label || !text) {
            alert('Mohon isi label dan teks anotasi');
            return;
        }

        activeType = 'annotation';
        alert('Silakan klik dan drag pada halaman untuk menentukan posisi anotasi');
        
        annotations.push({
            label,
            text,
            x: 0,
            y: 0,
            width: 0,
            height: 0,
            id: Date.now()
        });

        document.getElementById('annotationLabel').value = '';
        document.getElementById('annotationText').value = '';
    }

    function saveAnnotationPosition() {
        if (currentBox && annotations.length > 0) {
            const rect = currentBox.getBoundingClientRect();
            const parentRect = wrapper.getBoundingClientRect();
            const lastAnnotation = annotations[annotations.length - 1];
            
            lastAnnotation.x = rect.left - parentRect.left;
            lastAnnotation.y = rect.top - parentRect.top;
            lastAnnotation.width = rect.width;
            lastAnnotation.height = rect.height;

            currentBox.remove();
            renderAnnotationList();
        }
    }

    function renderAnnotationList() {
        annotationList.innerHTML = '';
        
        if (annotations.length === 0) {
            annotationList.innerHTML = '<p class="text-xs text-gray-500 text-center py-4">Belum ada anotasi</p>';
            return;
        }

        annotations.forEach((ann, index) => {
            const item = document.createElement('div');
            item.className = 'p-3 bg-orange-50 border border-orange-200 rounded-lg';
            item.innerHTML = `
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-orange-900 text-sm truncate">${ann.label}</p>
                        <p class="text-xs text-orange-700 line-clamp-2">${ann.text}</p>
                        <p class="text-xs text-orange-600 mt-1">Posisi: (${Math.round(ann.x)}, ${Math.round(ann.y)})</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="editAnnotation(${index})" class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition">✏️ Edit</button>
                    <button onclick="deleteAnnotation(${index})" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition">🗑️ Hapus</button>
                </div>
            `;
            annotationList.appendChild(item);
        });
    }

    window.editAnnotation = function(index) {
        alert('Edit functionality akan diimplementasikan');
    }

    window.deleteAnnotation = function(index) {
        if (confirm('Hapus anotasi ini?')) {
            annotations.splice(index, 1);
            renderAnnotationList();
        }
    }

    // ================= AUDIO FUNCTIONS (FR-15, FR-16, FR-17, FR-18) =================
    window.addAudio = function() {
        const label = document.getElementById('audioLabel').value;
        const file = document.getElementById('audioFile').files[0];

        if (!label || !file) {
            alert('Mohon isi label dan pilih file audio');
            return;
        }

        activeType = 'audio';
        alert('Silakan klik dan drag pada halaman untuk menentukan posisi audio');
        
        audioItems.push({
            label,
            file,
            x: 0,
            y: 0,
            width: 0,
            height: 0,
            id: Date.now()
        });

        document.getElementById('audioLabel').value = '';
        document.getElementById('audioFile').value = '';
    }

    function saveAudioPosition() {
        if (currentBox && audioItems.length > 0) {
            const rect = currentBox.getBoundingClientRect();
            const parentRect = wrapper.getBoundingClientRect();
            const lastAudio = audioItems[audioItems.length - 1];
            
            lastAudio.x = rect.left - parentRect.left;
            lastAudio.y = rect.top - parentRect.top;
            lastAudio.width = rect.width;
            lastAudio.height = rect.height;

            currentBox.remove();
            renderAudioList();
        }
    }

    function renderAudioList() {
        audioList.innerHTML = '';
        
        if (audioItems.length === 0) {
            audioList.innerHTML = '<p class="text-xs text-gray-500 text-center py-4">Belum ada audio</p>';
            return;
        }

        audioItems.forEach((audio, index) => {
            const item = document.createElement('div');
            item.className = 'p-3 bg-green-50 border border-green-200 rounded-lg';
            item.innerHTML = `
                <div class="flex items-start justify-between mb-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-green-900 text-sm truncate">${audio.label}</p>
                        <p class="text-xs text-green-700">${audio.file.name}</p>
                        <p class="text-xs text-green-600 mt-1">Posisi: (${Math.round(audio.x)}, ${Math.round(audio.y)})</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="editAudio(${index})" class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition">✏️ Edit</button>
                    <button onclick="deleteAudio(${index})" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition">🗑️ Hapus</button>
                </div>
            `;
            audioList.appendChild(item);
        });
    }

    window.editAudio = function(index) {
        alert('Edit audio functionality akan diimplementasikan');
    }

    window.deleteAudio = function(index) {
        if (confirm('Hapus audio ini?')) {
            audioItems.splice(index, 1);
            renderAudioList();
        }
    }

    // ================= SAVE ALL (FR-20) =================
    window.saveAll = function() {
        const pageId = {{ $page->id }};
        const formData = new FormData();
        formData.append('page_id', pageId);
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_method', 'PATCH');

        // Add annotations
        annotations.forEach((ann, index) => {
            formData.append(`annotations[${index}][label]`, ann.label);
            formData.append(`annotations[${index}][text]`, ann.text);
            formData.append(`annotations[${index}][x]`, ann.x);
            formData.append(`annotations[${index}][y]`, ann.y);
            formData.append(`annotations[${index}][width]`, ann.width);
            formData.append(`annotations[${index}][height]`, ann.height);
        });

        // Add audio
        audioItems.forEach((audio, index) => {
            formData.append(`audio[${index}][label]`, audio.label);
            formData.append(`audio[${index}][file]`, audio.file);
            formData.append(`audio[${index}][x]`, audio.x);
            formData.append(`audio[${index}][y]`, audio.y);
            formData.append(`audio[${index}][width]`, audio.width);
            formData.append(`audio[${index}][height]`, audio.height);
        });

        fetch(`/pages/${pageId}`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ Semua perubahan disimpan!');
            } else {
                alert('❌ Gagal menyimpan: ' + (data.message || 'Kesalahan tidak diketahui'));
            }
        })
        .catch(e => alert('❌ Error: ' + e.message));
    }

});

</script>

@endpush