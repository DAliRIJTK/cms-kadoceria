<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\AudioLatar;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AudioLatarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Storage::fake('public');
    }

    /**
     * Data provider untuk pengujian fungsionalitas store.
     * @return array
     */
    public static function storeAudioLatarProvider()
    {
        return [
            'sukses' => [
                'data' => ['nama_audio' => 'Audio Latar Keren', 'path_file' => fn() => UploadedFile::fake()->create('audio.mp3', 500)],
                'should_pass' => true,
                'error_field' => null
            ],
            'gagal tanpa nama audio' => [
                'data' => ['nama_audio' => '', 'path_file' => fn() => UploadedFile::fake()->create('audio.mp3', 500)],
                'should_pass' => false,
                'error_field' => 'nama_audio'
            ],
            'gagal tanpa file' => [
                'data' => ['nama_audio' => 'Audio Tanpa File', 'path_file' => null],
                'should_pass' => false,
                'error_field' => 'path_file'
            ],
            'gagal karena file terlalu besar' => [
                'data' => ['nama_audio' => 'Audio Gede', 'path_file' => fn() => UploadedFile::fake()->create('audio.mp3', 1025)],
                'should_pass' => false,
                'error_field' => 'path_file'
            ],
            'gagal karena tipe file tidak valid' => [
                'data' => ['nama_audio' => 'File Salah', 'path_file' => fn() => UploadedFile::fake()->create('document.pdf', 500)],
                'should_pass' => false,
                'error_field' => 'path_file'
            ],
        ];
    }

    #[DataProvider('storeAudioLatarProvider')]
    public function test_store_audio_latar_validation(array $data, bool $should_pass, ?string $error_field)
    {
        // Jika path_file adalah sebuah fungsi (closure), jalankan untuk membuat file palsu
        if (isset($data['path_file']) && is_callable($data['path_file'])) {
            $data['path_file'] = $data['path_file']();
        }

        $response = $this->post(route('audio-latar.store'), $data);

        if ($should_pass) {
            $response->assertRedirect();
            $response->assertSessionHas('success');
            $response->assertSessionHasNoErrors();

            $this->assertDatabaseHas('audio_latar', ['nama_audio' => $data['nama_audio']]);

            $audioLatar = AudioLatar::where('nama_audio', $data['nama_audio'])->first();
            $this->assertNotNull($audioLatar);
            Storage::disk('public')->assertExists($audioLatar->path_file);
        } else {
            $response->assertSessionHasErrors($error_field);
            $this->assertDatabaseMissing('audio_latar', ['nama_audio' => $data['nama_audio']]);
        }
    }

    public function test_can_delete_audio_latar_and_its_file()
    {
        $path = UploadedFile::fake()->create('audio.mp3', 500)->store('buku/audio-latar', 'public');
        $audioLatar = AudioLatar::factory()->create(['path_file' => $path]);

        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('audio_latar', ['id_audio_latar' => $audioLatar->id_audio_latar]);

        $response = $this->delete(route('audio-latar.delete', $audioLatar));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('audio_latar', ['id_audio_latar' => $audioLatar->id_audio_latar]);
    }
}