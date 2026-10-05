<?php

namespace Database\Factories;

use App\Enums\StatusPatrocinador;
use App\Models\Patrocinador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patrocinador>
 */
class PatrocinadorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->company(),
            'status' => StatusPatrocinador::Ativo,
        ];
    }
}
