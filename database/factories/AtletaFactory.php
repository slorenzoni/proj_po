<?php

namespace Database\Factories;

use App\Models\Atleta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Atleta>
 */
class AtletaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'apelido' => fake()->optional()->firstName(),
            'pais' => fake()->country(),
        ];
    }
}
