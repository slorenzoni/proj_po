<?php

namespace Database\Factories;

use App\Models\Luta;
use App\Models\PlacarFan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacarFan>
 */
class PlacarFanFactory extends Factory
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
            'user_id' => User::factory(),
            'round' => 1,
            'pontos_atleta_a' => 10,
            'pontos_atleta_b' => 9,
        ];
    }
}
