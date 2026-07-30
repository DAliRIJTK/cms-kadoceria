<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Buku;
use App\Models\Halaman;
use App\Models\AreaInteraktif;

class AreaInteraktifTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Buat user untuk otentikasi
        $this->user = User::factory()->create();
    }

    /**
     * POSITIVE TEST: Berhasil menambahkan area interaktif.
     */
    public function test_berhasil_menyimpan_area_interaktif()
    {
        $buku = Buku::factory()->create();
        $halaman = Halaman::factory()->create([
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 2, // Bukan cover
        ]);

        $payload = [
            'id_halaman' => $halaman->id_halaman,
            'label'      => 'Area Mata',
            'x_pct'      => 10,
            'y_pct'      => 10,
            'w_pct'      => 20,
            'h_pct'      => 20,
        ];

        $response = $this->actingAs($this->user)
                         ->postJson(route('halaman.storeAreaInteraktif'), $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Area interaktif berhasil disimpan',
                 ]);

        $this->assertDatabaseHas('area_interaktif', [
            'id_halaman' => $halaman->id_halaman,
            'label'      => 'Area Mata',
        ]);
    }

    /**
     * NEGATIVE TEST: Gagal karena validasi input Laravel.
     */
    public function test_gagal_menyimpan_karena_validasi_input_kosong()
    {
        $response = $this->actingAs($this->user)
                         ->postJson(route('halaman.storeAreaInteraktif'), []);

        // Memastikan Laravel mengembalikan error 422 Unprocessable Entity
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_halaman', 'x_pct', 'y_pct', 'w_pct', 'h_pct']);
    }

    /**
     * NEGATIVE TEST: Gagal menambahkan area di halaman cover.
     */
    public function test_gagal_menyimpan_di_halaman_cover()
    {
        $buku = Buku::factory()->create();
        $halaman = Halaman::factory()->create([
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 1, // Halaman Cover
        ]);

        $payload = [
            'id_halaman' => $halaman->id_halaman,
            'x_pct'      => 10,
            'y_pct'      => 10,
            'w_pct'      => 20,
            'h_pct'      => 20,
        ];

        $response = $this->actingAs($this->user)
                         ->postJson(route('halaman.storeAreaInteraktif'), $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Halaman cover tidak boleh memiliki area interaktif.',
                 ]);
    }

    /**
     * NEGATIVE TEST: Gagal karena area melebihi ukuran halaman (out of bounds).
     */
    public function test_gagal_menyimpan_karena_melebihi_ukuran_halaman()
    {
        $buku = Buku::factory()->create();
        $halaman = Halaman::factory()->create([
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 2,
        ]);

        $payload = [
            'id_halaman' => $halaman->id_halaman,
            'x_pct'      => 90,
            'w_pct'      => 20, // 90 + 20 = 110 (> 100)
            'y_pct'      => 10,
            'h_pct'      => 20,
        ];

        $response = $this->actingAs($this->user)
                         ->postJson(route('halaman.storeAreaInteraktif'), $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Area interaktif tidak boleh melebihi ukuran halaman buku.',
                 ]);
    }

    /**
     * NEGATIVE TEST: Gagal karena area tumpang tindih (overlap) dengan area lain.
     */
    public function test_gagal_menyimpan_karena_overlap()
    {
        $buku = Buku::factory()->create();
        $halaman = Halaman::factory()->create([
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 2,
        ]);

        // Buat area pertama
        AreaInteraktif::factory()->create([
            'id_halaman' => $halaman->id_halaman,
            'x_pct'      => 10,
            'y_pct'      => 10,
            'w_pct'      => 30,
            'h_pct'      => 30,
        ]);

        // Payload area kedua yang tumpang tindih dengan area pertama
        $payload = [
            'id_halaman' => $halaman->id_halaman,
            'x_pct'      => 20, // Overlap terjadi di rentang X: 10-40 dan Y: 10-40
            'y_pct'      => 20,
            'w_pct'      => 30,
            'h_pct'      => 30,
        ];

        $response = $this->actingAs($this->user)
                         ->postJson(route('halaman.storeAreaInteraktif'), $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Area interaktif tidak boleh menimpa area interaktif lainnya pada halaman yang sama.',
                 ]);
    }

    /**
     * NEGATIVE TEST: Gagal karena id_halaman tidak terdaftar di database.
     */
    public function test_gagal_menyimpan_karena_id_halaman_fiktif()
    {
        $payload = [
            'id_halaman' => 99999, // ID yang diasumsikan tidak ada
            'x_pct'      => 10,
            'y_pct'      => 10,
            'w_pct'      => 20,
            'h_pct'      => 20,
        ];

        $response = $this->actingAs($this->user)
                         ->postJson(route('halaman.storeAreaInteraktif'), $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_halaman']);
    }

    /**
     * NEGATIVE TEST: Gagal karena boundary input angka tidak sesuai validasi.
     */
    public function test_gagal_menyimpan_karena_batas_nilai_persentase_dan_dimensi_tidak_valid()
    {
        $buku = Buku::factory()->create();
        $halaman = Halaman::factory()->create([
            'id_buku' => $buku->id_buku,
            'nomor_halaman' => 2,
        ]);

        $payload = [
            'id_halaman'   => $halaman->id_halaman,
            'x_pct'        => -5,   // Invalid: min 0
            'y_pct'        => 105,  // Invalid: max 100
            'w_pct'        => 20,
            'h_pct'        => 20,
            'lebar_area'   => 0,    // Invalid: min 1
            'panjang_area' => 0,    // Invalid: min 1
        ];

        $response = $this->actingAs($this->user)
                         ->postJson(route('halaman.storeAreaInteraktif'), $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['x_pct', 'y_pct', 'lebar_area', 'panjang_area']);
    }
}