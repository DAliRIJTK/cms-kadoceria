<div class="rounded-xl border border-gray-200 p-4" id="area-card-{{ $area->id_area }}">
    <div class="flex justify-between items-start mb-3">
        <div>
            <p class="font-bold text-gray-900 text-sm">{{ $area->label ?? 'Area ' . $loop->iteration }}</p>
            <p class="text-xs text-gray-400">
                Posisi: ({{ $area->x }}, {{ $area->y }}) – Ukuran: {{ $area->lebar_area }}×{{ $area->panjang_area }}px
            </p>
        </div>
        @if($area->halaman->buku->status_publikasi !== 'Terbit')
            <button type="button"
                    class="btn-delete-area w-9 h-9 flex items-center justify-center bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors text-sm"
                    data-id="{{ $area->id_area }}"
                    data-route="{{ route('halaman.deleteAreaInteraktif', $area->id_area) }}"
                    data-csrf="{{ csrf_token() }}">
                🗑️
            </button>
        @endif
    </div>

    {{-- Audio Indo --}}
    <div class="bg-blue-50 rounded-lg p-3 mb-2 border border-blue-100">
        <p class="text-xs font-bold text-blue-800 mb-2">Audio Objek - Bahasa Indonesia</p>
        
        <div class="flex items-center gap-2 mb-2 {{ !$area->audio_indo ? 'hidden' : '' }}" id="audio-player-area-{{ $area->id_area }}-indo">
            <audio controls class="flex-1 h-8" src="{{ $area->audio_indo ? Storage::disk(config('filesystems.default'))->url($area->audio_indo) : '' }}"></audio>
            @if($area->halaman->buku->status_publikasi !== 'Terbit')
                <form action="{{ route('halaman.deleteAreaAudio', $area->id_area) }}" method="POST" class="flex-shrink-0">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="audio_type" value="indo">
                    <button type="submit"
                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                        Hapus
                    </button>
                </form>
            @endif
        </div>

        @if($area->halaman->buku->status_publikasi !== 'Terbit')
            <div class="audio-upload-zone"
                 data-url="{{ route('halaman.storeAreaAudio', $area->id_area) }}"
                 data-extra='{"audio_type":"indo"}'
                 data-player-target="audio-player-area-{{ $area->id_area }}-indo"
                 data-has-audio="{{ $area->audio_indo ? '1' : '0' }}">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <span class="upload-label flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium group-hover:bg-gray-50 transition-colors">
                        {{ $area->audio_indo ? 'Ganti File' : 'Pilih File' }}
                    </span>
                    <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden auto-upload-input">
                    <span class="upload-filename flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate">
                        Belum ada file dipilih
                    </span>
                </label>
                <div class="upload-status mt-1.5 hidden text-xs font-medium"></div>
                <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A • Suara saat objek dipilih</p>
            </div>
        @endif
    </div>

    {{-- Audio Sunda --}}
    <div class="bg-purple-50 rounded-lg p-3 border border-purple-100">
        <p class="text-xs font-bold text-purple-800 mb-2">Audio Objek - Bahasa Sunda</p>
        
        <div class="flex items-center gap-2 mb-2 {{ !$area->audio_sunda ? 'hidden' : '' }}" id="audio-player-area-{{ $area->id_area }}-sunda">
            <audio controls class="flex-1 h-8" src="{{ $area->audio_sunda ? Storage::disk(config('filesystems.default'))->url($area->audio_sunda) : '' }}"></audio>
            @if($area->halaman->buku->status_publikasi !== 'Terbit')
                <form action="{{ route('halaman.deleteAreaAudio', $area->id_area) }}" method="POST" class="flex-shrink-0">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="audio_type" value="sunda">
                    <button type="submit"
                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg text-xs font-semibold transition-colors">
                        Hapus
                    </button>
                </form>
            @endif
        </div>

        @if($area->halaman->buku->status_publikasi !== 'Terbit')
            <div class="audio-upload-zone"
                 data-url="{{ route('halaman.storeAreaAudio', $area->id_area) }}"
                 data-extra='{"audio_type":"sunda"}'
                 data-player-target="audio-player-area-{{ $area->id_area }}-sunda"
                 data-has-audio="{{ $area->audio_sunda ? '1' : '0' }}">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <span class="upload-label flex-shrink-0 px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium group-hover:bg-gray-50 transition-colors">
                        {{ $area->audio_sunda ? 'Ganti File' : 'Pilih File' }}
                    </span>
                    <input type="file" name="audio_file" accept=".mp3,.m4a" class="hidden auto-upload-input">
                    <span class="upload-filename flex-1 px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs text-gray-400 truncate">
                        Belum ada file dipilih
                    </span>
                </label>
                <div class="upload-status mt-1.5 hidden text-xs font-medium"></div>
                <p class="text-xs text-gray-400 mt-1">Maksimal 1MB • MP3, M4A • Suara saat objek dipilih</p>
            </div>
        @endif
    </div>
</div>