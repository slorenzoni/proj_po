<?php

namespace Database\Factories;

use App\Models\Luta;
use App\Models\Palpite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Palpite>
 */
class PalpiteFactory extends Factory
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
            // Por padrão o palpite é no participante A da própria luta.
            'vencedor_escolhido_id' => fn (array $attributes) => Luta::query()
                ->whereKey($attributes['luta_id'])
                ->valueOrFail('participante_a_id'),
        ];
    }
}
