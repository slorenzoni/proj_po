<?php

namespace Database\Factories;

use App\Models\Dica;
use App\Models\Luta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dica>
 */
class DicaFactory extends Factory
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
            'texto' => fake()->paragraph(),
        ];
    }
}
