<?php

namespace Database\Factories;

use App\Models\AreaInteraktif;
use App\Models\Halaman;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AreaInteraktif>
 */
class AreaInteraktifFactory extends Factory
{
    protected $model = AreaInteraktif::class;

    public function definition(): array
    {
        return [
            'id_halaman' => Halaman::factory(),
            'label' => $this->faker->word,
            'x_pct' => $this->faker->randomFloat(2, 10, 40),
            'y_pct' => $this->faker->randomFloat(2, 10, 40),
            'w_pct' => $this->faker->randomFloat(2, 5, 25),
            'h_pct' => $this->faker->randomFloat(2, 5, 25),
            'x' => $this->faker->numberBetween(0, 100),
            'y' => $this->faker->numberBetween(0, 100),
            'lebar_area' => $this->faker->numberBetween(10, 50),
            'panjang_area' => $this->faker->numberBetween(10, 50),
        ];
    }
}
