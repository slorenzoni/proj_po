<?php

namespace Database\Factories;

use App\Models\EstiloDeLuta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstiloDeLuta>
 */
class EstiloDeLutaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->words(2, true),
        ];
    }
}
