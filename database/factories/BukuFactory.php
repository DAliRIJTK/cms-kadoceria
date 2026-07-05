<?php

namespace Database\Factories;

use App\Models\Buku;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Buku>
 */
class BukuFactory extends Factory
{
    protected $model = Buku::class;

    public function definition(): array
    {
        return [
            'id_pengelola' => User::factory(),
            'judul_idn' => $this->faker->sentence(3),
            'judul_sn' => $this->faker->sentence(3),
            'penulis' => $this->faker->name,
            'ilustrator' => $this->faker->name,
            'deskripsi_idn' => $this->faker->paragraph,
            'deskripsi_sn' => $this->faker->paragraph,
            'status_publikasi' => 'Draft',
            'original_pdf_name' => $this->faker->unique()->word . '.pdf',
        ];
    }
}