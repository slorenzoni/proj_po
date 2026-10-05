<?php

namespace Database\Factories;

use App\Models\Papel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Papel>
 */
class PapelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->jobTitle(),
            'descricao' => fake()->sentence(),
        ];
    }
}
