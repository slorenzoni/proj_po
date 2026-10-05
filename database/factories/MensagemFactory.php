<?php

namespace Database\Factories;

use App\Models\Luta;
use App\Models\Mensagem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mensagem>
 */
class MensagemFactory extends Factory
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
            'mensagem' => fake()->sentence(),
        ];
    }
}
