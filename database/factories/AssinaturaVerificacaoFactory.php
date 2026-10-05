<?php

namespace Database\Factories;

use App\Enums\Periodicidade;
use App\Enums\StatusAssinatura;
use App\Models\AssinaturaVerificacao;
use App\Models\SolicitacaoVerificacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssinaturaVerificacao>
 */
class AssinaturaVerificacaoFactory extends Factory
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
            // A cobrança pertence ao mesmo usuário que fez a solicitação.
            'solicitacao_verificacao_id' => fn (array $attributes) => SolicitacaoVerificacao::factory()->state([
                'user_id' => $attributes['user_id'],
            ]),
            'periodicidade' => Periodicidade::Mensal,
            'gateway' => 'teste',
            'valor' => 9.90,
            'status' => StatusAssinatura::Ativa,
            'data_inicio' => today(),
        ];
    }
}
