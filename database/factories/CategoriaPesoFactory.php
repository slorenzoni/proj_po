<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\CategoriaPeso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaPeso>
 */
class CategoriaPesoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria_id' => Categoria::factory(),
            'nome' => fake()->unique()->words(2, true),
            'peso_minimo_kg' => 66,
            'peso_maximo_kg' => 70,
        ];
    }
}
