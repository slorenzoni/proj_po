<?php

namespace Database\Factories;

use App\Enums\Periodicidade;
use App\Enums\PlanoAssinatura;
use App\Enums\StatusAssinatura;
use App\Models\Assinatura;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assinatura>
 */
class AssinaturaFactory extends Factory
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
            'plano' => PlanoAssinatura::Free,
            'status' => StatusAssinatura::Ativa,
            'valor' => 0,
            'data_inicio' => today(),
        ];
    }

    public function membro(): static
    {
        return $this->state(fn (array $attributes) => [
            'plano' => PlanoAssinatura::Membro,
            'periodicidade' => Periodicidade::Mensal,
            'gateway' => 'teste',
            'valor' => 19.90,
            'proxima_cobranca' => today()->addMonth(),
        ]);
    }
}
