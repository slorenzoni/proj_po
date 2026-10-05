<?php

namespace App\Services\Pontuacao;

use App\Enums\StatusLuta;
use App\Models\ConfiguracaoPontuacao;
use App\Models\Luta;
use App\Models\Palpite;

/**
 * Pontua os palpites de uma luta encerrada (regras aprovadas em 30/09/2026):
 *
 * - errou o vencedor → 0;
 * - empate, sem resultado e (nas modalidades com rounds) desqualificação → 0 para todos;
 * - acertou o vencedor → pontos conforme acertou também método e/ou round, multiplicados
 *   pelo peso do momento da última troca ("peso_aplicado", gravado no próprio palpite).
 *
 * Pode ser executado mais de uma vez: sempre recalcula a partir do resultado da luta.
 */
final class PontuadorDeLuta
{
    public function pontuar(Luta $luta): void
    {
        if ($luta->status !== StatusLuta::Encerrada) {
            return;
        }

        $luta->loadMissing('categoria');

        $configuracao = ConfiguracaoPontuacao::paraCategoria($luta->categoria);

        $luta->palpites()->lazyById()->each(function (Palpite $palpite) use ($luta, $configuracao): void {
            $base = $this->acertos($palpite, $luta)->pontosBase($configuracao);

            $palpite->update([
                'pontos_obtidos' => round($base * (float) $palpite->peso_aplicado / 100, 2),
            ]);
        });
    }

    /**
     * Compara o palpite com o resultado oficial da luta.
     */
    public function acertos(Palpite $palpite, Luta $luta): AcertosDoPalpite
    {
        $usaRounds = $luta->numero_rounds !== null;
        $metodoDoResultado = $luta->metodo_vitoria?->paraPalpite($usaRounds);

        // Sem vencedor ou sem método equivalente: resultado que não pontua para ninguém.
        if ($luta->vencedor_id === null || $metodoDoResultado === null) {
            return AcertosDoPalpite::nenhum();
        }

        if ($palpite->vencedor_escolhido_id !== $luta->vencedor_id) {
            return AcertosDoPalpite::nenhum();
        }

        return new AcertosDoPalpite(
            vencedor: true,
            metodo: $palpite->metodo_escolhido === $metodoDoResultado,
            round: $usaRounds
                && $palpite->round_escolhido !== null
                && $palpite->round_escolhido === $luta->round_fim,
        );
    }
}
