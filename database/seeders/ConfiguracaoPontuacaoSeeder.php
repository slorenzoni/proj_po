<?php

namespace Database\Seeders;

use App\Models\ConfiguracaoPontuacao;
use App\Models\PesoTrocaPalpite;
use Illuminate\Database\Seeder;

/**
 * Padrão geral (sem categoria) de pontuação e de pesos por momento da troca, com os
 * valores de partida aprovados em 30/09/2026. Idempotente: pode rodar em produção e
 * não sobrescreve valores já alterados pelo administrador.
 *
 * Não usa WithoutModelEvents: o UUID e a auditoria são gerados por eventos do model.
 */
class ConfiguracaoPontuacaoSeeder extends Seeder
{
    /**
     * Peso (%) por número de rounds da luta e momento da troca (0 = pré-luta).
     *
     * @var array<int, array<int, int>>
     */
    private const PESOS = [
        3 => [0 => 100, 1 => 70, 2 => 40],
        5 => [0 => 100, 1 => 80, 2 => 60, 3 => 40, 4 => 20],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConfiguracaoPontuacao::query()->firstOrCreate(
            ['categoria_id' => null],
            [
                'pontos_vencedor' => 10,
                'pontos_vencedor_metodo' => 15,
                'pontos_vencedor_round' => 15,
                'pontos_perfeito' => 22,
                'prazo_placar_fans_minutos' => 5,
            ],
        );

        foreach (self::PESOS as $numeroRounds => $pesos) {
            foreach ($pesos as $roundDaTroca => $peso) {
                PesoTrocaPalpite::query()->firstOrCreate(
                    ['categoria_id' => null, 'numero_rounds' => $numeroRounds, 'round_da_troca' => $roundDaTroca],
                    ['peso' => $peso],
                );
            }
        }
    }
}
