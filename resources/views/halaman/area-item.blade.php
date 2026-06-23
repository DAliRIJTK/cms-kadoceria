<div class="rounded-xl border border-gray-200 p-4" id="area-card-{{ $area->id_area }}">
    <div class="flex justify-between items-start mb-3">
        <div>
            <p class="font-bold text-gray-900 text-sm">{{ $area->label ?? 'Area ' . $loop->iteration }}</p>
            <p class="text-xs text-gray-400">
                Posisi: ({{ $area->x }}, {{ $area->y }}) – Ukuran: {{ $area->lebar_area }}×{{ $area->panjang_area }}px
            </p>
        </div>
        <button type="button"
                class="btn-delete-area w-9 h-9 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm"
                data-id="{{ $area->id_area }}"
                data-route="{{ route('halaman.deleteAreaInteraktif', $area->id_area) }}"
                data-csrf="{{ csrf_token() }}">
            🗑️
        </button>
    </div>

    <div class="bg-blue-50 rounded-lg p-3 mb-2 border border-blue-100">
        <p class="text-xs font-bold text-blue-800 mb-2">Audio Objek - Bahasa Indonesia</p>
        @if($area->audio_indo)
            <audio controls class="w-full h-8 mb-2" src="{{ asset('storage/' . $area->audio_indo) }}"></audio>
        @endif
        <form action="{{ route('halaman.storeAreaAudio', $area->id_area) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="audio_type" value="indo">
            <div class="flex gap-2">
                <label class="flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium cursor-pointer hover:bg-gray-50 transition-colors">
                    Pilih File
                    <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden">
                </label>
                <span class="flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate self-center file-name-display">
                    Belum ada file dipilih
                </span>
                <button type="submit"
                        class="px-3 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded-lg text-xs font-semibold transition-colors">
                    Unggah
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A • Suara saat objek dipilih</p>
        </form>
    </div>

    <div class="bg-purple-50 rounded-lg p-3 border border-purple-100">
        <p class="text-xs font-bold text-purple-800 mb-2">Audio Objek - Bahasa Sunda</p>
        @if($area->audio_sunda)
            <audio controls class="w-full h-8 mb-2" src="{{ asset('storage/' . $area->audio_sunda) }}"></audio>
        @endif
        <form action="{{ route('halaman.storeAreaAudio', $area->id_area) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="audio_type" value="sunda">
            <div class="flex gap-2">
                <label class="flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium cursor-pointer hover:bg-gray-50 transition-colors">
                    Pilih File
                    <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden">
                </label>
                <span class="flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate self-center file-name-display">
                    Belum ada file dipilih
                </span>
                <button type="submit"
                        class="px-3 py-1.5 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-semibold transition-colors">
                    Unggah
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A • Suara saat objek dipilih</p>
        </form>
    </div>
</div>