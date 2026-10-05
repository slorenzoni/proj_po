<?php

namespace Database\Factories;

use App\Models\PerfilCliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerfilCliente>
 */
class PerfilClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'foto_perfil_url' => null,
            'email_secundario' => null,
            'telefone' => fake()->numerify('(##) #####-####'),
            'endereco' => fake()->streetAddress(),
            'maior_de_18' => true,
            'tipo' => null,
            'data_cadastro' => now()->toDateString(),
        ];
    }
}
