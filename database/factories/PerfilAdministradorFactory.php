<?php

namespace Database\Factories;

use App\Enums\NivelAcesso;
use App\Models\PerfilAdministrador;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerfilAdministrador>
 */
class PerfilAdministradorFactory extends Factory
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
            'nivel_acesso' => NivelAcesso::Cadastrador,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'nivel_acesso' => NivelAcesso::SuperAdmin,
        ]);
    }
}
