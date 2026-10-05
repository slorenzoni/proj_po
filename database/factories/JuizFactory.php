<?php

namespace Database\Factories;

use App\Models\Juiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Juiz>
 */
class JuizFactory extends Factory
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
            'pais' => fake()->country(),
        ];
    }
}
