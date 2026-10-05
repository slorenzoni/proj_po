<?php

namespace Database\Factories;

use App\Models\Atleta;
use App\Models\AtletaFoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtletaFoto>
 */
class AtletaFotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'atleta_id' => Atleta::factory(),
            'foto_url' => 'atletas/'.fake()->uuid().'.jpg',
            'ordem' => 1,
            'principal' => false,
        ];
    }
}
