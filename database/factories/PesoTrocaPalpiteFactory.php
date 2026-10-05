<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\PesoTrocaPalpite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PesoTrocaPalpite>
 */
class PesoTrocaPalpiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categoria_id' => null,
            'numero_rounds' => 3,
            'round_da_troca' => 0,
            'peso' => 100,
        ];
    }

    public function paraCategoria(Categoria $categoria): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria_id' => $categoria->getKey(),
        ]);
    }
}
