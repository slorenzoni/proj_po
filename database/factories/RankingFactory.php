<?php

namespace Database\Factories;

use App\Enums\EscopoRanking;
use App\Models\Ranking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ranking>
 */
class RankingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'escopo' => EscopoRanking::Geral,
            'user_id' => User::factory(),
        ];
    }
}
