<?php

namespace Database\Factories;

use App\Enums\StatusEvento;
use App\Models\Evento;
use App\Models\Organizacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evento>
 */
class EventoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizacao_id' => Organizacao::factory(),
            'nome' => fake()->unique()->words(3, true),
            'data' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'cidade' => fake()->city(),
            'pais' => fake()->country(),
            'status' => StatusEvento::Agendado,
        ];
    }
}
