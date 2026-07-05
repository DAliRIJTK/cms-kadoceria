<?php

namespace Database\Factories;

use App\Models\Halaman;
use App\Models\Buku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Halaman>
 */
class HalamanFactory extends Factory
{
    protected $model = Halaman::class;

    public function definition(): array
    {
        return [
            'id_buku' => Buku::factory(),
            'nomor_halaman' => $this->faker->unique()->numberBetween(1, 50),
            'path_gambar' => 'images/fake/' . $this->faker->word . '.jpg',
        ];
    }
}