<?php

namespace Database\Factories;

use App\Enums\StatusSolicitacaoVerificacao;
use App\Models\SolicitacaoVerificacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitacaoVerificacao>
 */
class SolicitacaoVerificacaoFactory extends Factory
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
            'documento_url' => 'verificacoes/'.fake()->uuid().'.pdf',
            'status' => StatusSolicitacaoVerificacao::Pendente,
        ];
    }
}
