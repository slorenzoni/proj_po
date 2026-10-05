<?php

namespace Database\Factories;

use App\Models\Juiz;
use App\Models\Luta;
use App\Models\Placar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Placar>
 */
class PlacarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'luta_id' => Luta::factory(),
            'juiz_id' => Juiz::factory(),
            'round' => 1,
            'pontos_atleta_a' => 10,
            'pontos_atleta_b' => 9,
        ];
    }
}
