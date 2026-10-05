<?php

namespace App\Jobs;

use App\Models\Luta;
use App\Services\Pontuacao\AtualizadorDeRanking;
use App\Services\Pontuacao\PontuadorDeLuta;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Disparado quando uma luta é encerrada: pontua os palpites e atualiza os rankings
 * geral, do evento e da organização.
 *
 * É idempotente — as duas etapas recalculam tudo a partir do resultado da luta —,
 * então pode ser reexecutado com segurança após uma falha.
 */
class ProcessarResultadoDaLuta implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60];

    public function __construct(public Luta $luta) {}

    public function handle(PontuadorDeLuta $pontuador, AtualizadorDeRanking $ranking): void
    {
        $pontuador->pontuar($this->luta);
        $ranking->recalcularParaLuta($this->luta);
    }
}
