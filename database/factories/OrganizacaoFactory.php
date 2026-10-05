<?php

namespace Database\Factories;

use App\Models\Organizacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organizacao>
 */
class OrganizacaoFactory extends Factory
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
            'pais_origem' => fake()->country(),
        ];
    }
}
