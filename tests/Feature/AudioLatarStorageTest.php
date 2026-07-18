<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AudioLatarStorageTest extends TestCase
{
    public function test_audio_latar_index_uses_storage_disk_url_for_audio_player(): void
    {
        Storage::fake('s3', ['url' => 'https://cdn.example.com']);

        $audio = new \stdClass();
        $audio->id_audio_latar = 1;
        $audio->nama_audio = 'Background Musik';
        $audio->path_file = 'buku/audio-latar/test.mp3';
        $audio->halaman_count = 0;
        $audio->halaman = collect();

        $html = view('audio-latar.index', [
            'audioLatar' => collect([$audio]),
            'ref' => null,
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('https://cdn.example.com/buku/audio-latar/test.mp3', $html);
        $this->assertStringNotContainsString('http://localhost/storage/buku/audio-latar/test.mp3', $html);
    }
}
