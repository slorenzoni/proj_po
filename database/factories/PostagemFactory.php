<?php

namespace Database\Factories;

use App\Enums\StatusPostagem;
use App\Models\Postagem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Postagem>
 */
class PostagemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => fake()->sentence(4),
            'slug' => fake()->unique()->slug(),
            'conteudo' => fake()->paragraph(),
            'user_id' => User::factory(),
            'status' => StatusPostagem::Rascunho,
        ];
    }
}
