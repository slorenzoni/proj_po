<?php

namespace Database\Factories;

use App\Models\Palpite;
use App\Models\PalpiteHistorico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PalpiteHistorico>
 */
class PalpiteHistoricoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'palpite_id' => Palpite::factory(),
            'vencedor_escolhido_id' => fn (array $attributes) => Palpite::query()
                ->whereKey($attributes['palpite_id'])
                ->valueOrFail('vencedor_escolhido_id'),
            'round_da_troca' => 0,
        ];
    }
}
