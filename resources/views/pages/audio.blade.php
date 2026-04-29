@extends('layouts.dashboard')

@section('content')

<div class="mb-8">
    <a href="{{ url('/pages/' . $page->id) }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium mb-4 inline-block">← Kembali ke Halaman</a>
    
    <h1 class="text-3xl font-bold text-gray-800 mb-2">🔊 Kelola Audio - Halaman {{ $page->page_number }}</h1>
    <p class="text-gray-600">Buku: <span class="font-semibold">{{ $page->book->title }}</span></p>
</div>

@if (session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex gap-3">
            <span class="text-2xl">✅</span>
            <p class="text-green-800 font-medium">{{ session('success') }}</p>
        </div>
    </div>
@endif

<div class="flex gap-4 mb-6 border-b border-gray-300">
    <button class="audio-tab px-4 py-2 font-medium text-blue-600 border-b-2 border-blue-600" data-type="all">
        📢 Semua Audio ({{ $page->audios->count() }})
    </button>
    <button class="audio-tab px-4 py-2 font-medium text-gray-600 hover:text-gray-800" data-type="narration">
        🎤 Narasi ({{ $page->audios->where('type', 'narration')->count() }})
    </button>
    <button class="audio-tab px-4 py-2 font-medium text-gray-600 hover:text-gray-800" data-type="backsound">
        🎵 Backsound ({{ $page->audios->where('type', 'backsound')->count() }})
    </button>
    <button class="audio-tab px-4 py-2 font-medium text-gray-600 hover:text-gray-800" data-type="object">
        🎯 Audio Objek ({{ $page->audios->where('type', 'object')->count() }})
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 h-fit">
        <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200">➕ Tambah Audio Baru</h2>

        <form action="{{ url('/pages/' . $page->id . '/audio') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Jenis Audio <span class="text-red-500">*</span></label>
                <select name="type" id="audio_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required onchange="updateTypeDescription()">
                    <option value="">-- Pilih Jenis Audio --</option>
                    <option value="narration">🎤 Narasi - Suara Narator (Seluruh Halaman)</option>
                    <option value="backsound">🎵 Backsound - Musik Latar (Seluruh Halaman)</option>
                    <option value="object">🎯 Audio Objek - Suara untuk Anotasi Tertentu</option>
                </select>
                <p id="type-description" class="text-xs text-gray-600 mt-2 p-2 bg-blue-50 rounded border border-blue-200"></p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Label <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    name="label"
                    placeholder="Contoh: Pengantar, BGM Ceria, Suara Karakter"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                >
            </div>

            <div id="bounding-box-field" style="display: none;">
                <label class="block text-sm font-semibold text-gray-700 mb-2">📌 Anotasi Terkait <span class="text-red-500">*</span></label>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-2">
                    <p class="text-xs text-yellow-800">Pilih anotasi untuk menambahkan suara ke objek spesifik. Audio ini akan hanya dimainkan untuk anotasi yang dipilih.</p>
                </div>
                <select name="bounding_box_id" id="bounding_box_select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Pilih Anotasi --</option>
                    @foreach($page->boundingBoxes as $box)
                        <option value="{{ $box->id }}">📝 {{ $box->label ?? 'Anotasi ' . $loop->iteration }} (ID: {{ $box->id }})</option>
                    @endforeach
                </select>
                @if($page->boundingBoxes->isEmpty())
                    <p class="text-xs text-red-600 mt-2">⚠️ Belum ada anotasi. Buat anotasi terlebih dahulu di halaman editor.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">File Audio <span class="text-red-500">*</span></label>
                <input 
                    type="file" 
                    name="audio_file"
                    accept="audio/*"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                >
                <p class="text-xs text-gray-500 mt-2">Format: MP3, WAV, OGG, M4A | Max 10MB</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition-colors font-medium">
                ✅ Simpan Audio
            </button>
        </form>
    </div>

        <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">📚 Daftar Audio</h2>
            </div>

            @if($page->audios->isEmpty())
                <div class="p-8 text-center">
                    <p class="text-gray-500 text-lg mb-2">🎵</p>
                    <p class="text-gray-600 font-medium">Belum ada audio</p>
                    <p class="text-gray-500 text-sm">Mulai dengan menambahkan audio di form samping</p>
                </div>
            @else
                <div class="divide-y divide-gray-200" id="audio-list">
                    @foreach($page->audios as $audio)
                        <div class="p-6 hover:bg-gray-50 transition-colors audio-item" data-audio-type="{{ $audio->type }}">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-lg">
                                            @if($audio->type === 'narration')
                                                🎤
                                            @elseif($audio->type === 'backsound')
                                                🎵
                                            @else
                                                🎯
                                            @endif
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold
                                            @if($audio->type === 'narration')
                                                bg-purple-100 text-purple-800
                                            @elseif($audio->type === 'backsound')
                                                bg-blue-100 text-blue-800
                                            @else
                                                bg-green-100 text-green-800
                                            @endif
                                        ">
                                            @if($audio->type === 'narration')
                                                Narasi
                                            @elseif($audio->type === 'backsound')
                                                Backsound
                                            @else
                                                Audio Objek
                                            @endif
                                        </span>
                                        @if($audio->bounding_box_id)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                                📌 Per Anotasi
                                            </span>
                                        @endif
                                    </div>
                                    <p class="font-semibold text-gray-900 text-base mb-1">{{ $audio->label }}</p>
                                    @if($audio->bounding_box_id)
                                        <p class="text-sm text-gray-600 bg-yellow-50 px-3 py-2 rounded border border-yellow-200">
                                            📌 <strong>Terkait Anotasi:</strong> {{ $page->boundingBoxes->where('id', $audio->bounding_box_id)->first()?->label ?? 'Anotasi tidak ditemukan' }}
                                        </p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ url('/audio/' . $audio->id) }}" class="inline" onsubmit="return confirm('Hapus audio ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 font-medium text-xl">🗑️</button>
                                </form>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                                <div>
                                    <p class="text-gray-500 text-xs font-medium">File</p>
                                    <p class="font-mono text-gray-800 text-xs">{{ basename($audio->file_url) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs font-medium">Tipe</p>
                                    <p class="font-medium text-gray-800 text-xs">
                                        @if($audio->type === 'narration')
                                            Narasi Halaman
                                        @elseif($audio->type === 'backsound')
                                            Musik Latar
                                        @else
                                            Suara Anotasi
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <audio controls class="w-full h-8 rounded bg-gray-100 border border-gray-300">
                                <source src="{{ asset('storage/' . $audio->file_url) }}" type="audio/mpeg">
                                Browser Anda tidak mendukung audio element.
                            </audio>

                            <p class="text-xs text-gray-500 mt-2">ID: {{ $audio->id }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function updateTypeDescription() {
    const type = document.getElementById('audio_type').value;
    const descriptions = {
        'narration': '🎤 Suara narator untuk keseluruhan halaman. Semua pengguna akan mendengar audio ini saat membaca halaman.',
        'backsound': '🎵 Musik latar atau efek suara yang dimainkan untuk keseluruhan halaman sebagai background.',
        'object': '🎯 Suara khusus untuk anotasi/objek tertentu. Pilih anotasi di bawah untuk menentukan ke mana audio ini diterapkan.'
    };
    
    const descEl = document.getElementById('type-description');
    descEl.textContent = descriptions[type] || '';
    
    const boxField = document.getElementById('bounding-box-field');
    const boxSelect = document.getElementById('bounding_box_select');
    
    if (type === 'object') {
        boxField.style.display = 'block';
        boxSelect.required = true;
    } else {
        boxField.style.display = 'none';
        boxSelect.required = false;
        boxSelect.value = '';
    }
}

document.querySelectorAll('.audio-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const type = this.dataset.type;
        
        document.querySelectorAll('.audio-tab').forEach(t => {
            t.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
            t.classList.add('text-gray-600', 'hover:text-gray-800');
        });
        this.classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
        this.classList.remove('text-gray-600', 'hover:text-gray-800');
        
        const items = document.querySelectorAll('.audio-item');
        items.forEach(item => {
            if (type === 'all') {
                item.style.display = 'block';
            } else {
                item.style.display = item.dataset.audioType === type ? 'block' : 'none';
            }
        });
    });
});
</script>

@endsection
