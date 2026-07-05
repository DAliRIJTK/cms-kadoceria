<?php

namespace Database\Factories;

use App\Models\AudioLatar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AudioLatar>
 */
class AudioLatarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AudioLatar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_audio' => $this->faker->words(3, true),
            'path_file' => 'buku/audio-latar/' . $this->faker->word . '.mp3',
        ];
    }
}