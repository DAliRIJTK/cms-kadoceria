<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Buku;
use App\Models\Halaman;
use App\Models\AreaInteraktif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AudioControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $buku;
    protected $halaman;
    protected $area;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create mock data
        $this->buku = Buku::factory()->create();
        $this->halaman = Halaman::factory()->create(['id_buku' => $this->buku->id_buku]);
        $this->area = AreaInteraktif::factory()->create(['id_halaman' => $this->halaman->id_halaman]);

        // Mock the storage disk
        Storage::fake('public');
    }

    /**
     * Data provider untuk ukuran file dan tipe bahasa.
     * @return array
     */
    public static function audioFileProvider()
    {
        return [
            // Indonesian Audio
            'indo: file di bawah batas (512KB) dengan format mp3' => [512, true, 'indo', 'mp3'],
            'indo: file tepat di batas (1024KB) dengan format mp3' => [1024, true, 'indo', 'mp3'],
            'indo: file sedikit di atas batas (1025KB) dengan format mp3' => [1025, false, 'indo', 'mp3'],
            'indo: file kosong (0KB) akan gagal dengan format mp3' => [0, false, 'indo', 'mp3'],

            // Sundanese Audio
            'sunda: file di bawah batas (512KB) dengan format mp3' => [512, true, 'sunda', 'mp3'],
            'sunda: file tepat di batas (1024KB) dengan format mp3' => [1024, true, 'sunda', 'mp3'],
            'sunda: file sedikit di atas batas (1025KB) dengan format mp3' => [1025, false, 'sunda', 'mp3'],
            'sunda: file kosong (0KB) akan gagal dengan format mp3' => [0, false, 'sunda', 'mp3'],

            // Indonesian Audio
            'indo: file di bawah batas (512KB) dengan format m4a' => [512, true, 'indo', 'm4a'],
            'indo: file tepat di batas (1024KB) dengan format m4a' => [1024, true, 'indo', 'm4a'],
            'indo: file sedikit di atas batas (1025KB) dengan format m4a' => [1025, false, 'indo', 'm4a'],
            'indo: file kosong (0KB) akan gagal dengan format m4a' => [0, false, 'indo', 'm4a'],

            // Sundanese Audio
            'sunda: file di bawah batas (512KB) dengan format m4a' => [512, true, 'sunda', 'm4a'],
            'sunda: file tepat di batas (1024KB) dengan format m4a' => [1024, true, 'sunda', 'm4a'],
            'sunda: file sedikit di atas batas (1025KB) dengan format m4a' => [1025, false, 'sunda', 'm4a'],
            'sunda: file kosong (0KB) akan gagal dengan format m4a' => [0, false, 'sunda', 'm4a'],
        ];
    }

    /**
     * Data provider untuk tipe file yang tidak valid.
     * @return array
     */
    public static function invalidFileTypeProvider()
    {
        return [
            'PDF file' => ['document.pdf', 500],
            'Text file' => ['notes.txt', 10],
            'Image file' => ['photo.jpg', 200],
        ];
    }

    #[DataProvider('audioFileProvider')]
    public function test_store_area_audio_with_different_file_sizes_and_types($file_size, $should_pass, $type, $format)
    {
        $file = UploadedFile::fake()->create("audio.{$format}", $file_size);

        $response = $this->post(route('halaman.storeAreaAudio', $this->area), [
            'audio_type' => $type, // Menggunakan tipe dari data provider
            'audio_file' => $file,
        ]);

        if ($should_pass) {
            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            $this->area->refresh();

            $field = 'audio_' . $type;
            $this->assertNotNull($this->area->$field, "Kolom {$field} tidak boleh null.");
            Storage::disk('public')->assertExists($this->area->$field);
        } else {
            $response->assertSessionHasErrors('audio_file');
        }
    }

    #[DataProvider('audioFileProvider')]
    public function test_store_narasi_audio_with_different_file_sizes_and_types($file_size, $should_pass, $type, $format)
    {
        $file = UploadedFile::fake()->create("audio.{$format}", $file_size);

        $response = $this->post(route('halaman.storeNarasi', $this->halaman), [
            'narasi_type' => $type, // Menggunakan tipe dari data provider
            'audio_file' => $file,
        ]);

        if ($should_pass) {
            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            $this->halaman->refresh();

            $field = 'narasi_' . $type;
            $this->assertNotNull($this->halaman->$field, "Kolom {$field} tidak boleh null.");
            Storage::disk('public')->assertExists($this->halaman->$field);
        } else {
            $response->assertSessionHasErrors('audio_file');
        }
    }

    // public function test_store_area_audio_without_file_fails()
    // {
    //     $response = $this->post(route('halaman.storeAreaAudio', $this->area), [
    //         'audio_type' => 'indo',
    //         'audio_file' => null,
    //     ]);

    //     $response->assertSessionHasErrors('audio_file');
    // }

    // public function test_store_narasi_audio_without_file_fails()
    // {
    //     $response = $this->post(route('halaman.storeNarasi', $this->halaman), [
    //         'narasi_type' => 'indo',
    //         'audio_file' => null,
    //     ]);

    //     $response->assertSessionHasErrors('audio_file');
    // }

    #[DataProvider('invalidFileTypeProvider')]
    public function test_store_area_audio_with_invalid_file_type_fails($filename, $filesize)
    {
        $file = UploadedFile::fake()->create($filename, $filesize);

        $response = $this->post(route('halaman.storeAreaAudio', $this->area), [
            'audio_type' => 'indo',
            'audio_file' => $file,
        ]);

        $response->assertSessionHasErrors('audio_file');
        $this->area->refresh();
        $this->assertNull($this->area->audio_indo);
    }

    #[DataProvider('invalidFileTypeProvider')]
    public function test_store_narasi_audio_with_invalid_file_type_fails($filename, $filesize)
    {
        $file = UploadedFile::fake()->create($filename, $filesize);

        $response = $this->post(route('halaman.storeNarasi', $this->halaman), [
            'narasi_type' => 'indo',
            'audio_file' => $file,
        ]);

        $response->assertSessionHasErrors('audio_file');
        $this->halaman->refresh();
        $this->assertNull($this->halaman->narasi_indo);
    }

    public function test_store_area_audio_fails_with_mismatched_mime_type()
    {
        // Buat file teks biasa, tapi beri nama dengan ekstensi .mp3.
        // Ini untuk memastikan validasi memeriksa isi file (MIME type), bukan hanya ekstensi.
        $file = UploadedFile::fake()->createWithContent('fake_audio.mp3', 'this is not a real mp3 file');

        $response = $this->post(route('halaman.storeAreaAudio', $this->area), [
            'audio_type' => 'indo',
            'audio_file' => $file,
        ]);

        // Harapkan ada error validasi karena tipe MIME tidak cocok (text/plain vs audio/mpeg)
        $response->assertSessionHasErrors('audio_file');
        $this->area->refresh();
        $this->assertNull($this->area->audio_indo);
    }

    public function test_store_narasi_audio_fails_with_mismatched_mime_type()
    {
        // Buat file teks biasa, tapi beri nama dengan ekstensi .mp3
        $file = UploadedFile::fake()->createWithContent('fake_audio.mp3', 'this is not a real mp3 file');

        $response = $this->post(route('halaman.storeNarasi', $this->halaman), [
            'narasi_type' => 'indo',
            'audio_file' => $file,
        ]);

        // Harapkan ada error validasi karena tipe MIME tidak cocok
        $response->assertSessionHasErrors('audio_file');
        $this->halaman->refresh();
        $this->assertNull($this->halaman->narasi_indo);
    }
}
