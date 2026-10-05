<?php

namespace App\Services\Pontuacao;

use App\Models\ConfiguracaoPontuacao;

/**
 * O que um palpite acertou no resultado de uma luta. Método e round só contam
 * quando o vencedor está certo.
 */
final readonly class AcertosDoPalpite
{
    public function __construct(
        public bool $vencedor,
        public bool $metodo,
        public bool $round,
    ) {}

    public static function nenhum(): self
    {
        return new self(false, false, false);
    }

    /**
     * Vencedor + método + round.
     */
    public function perfeito(): bool
    {
        return $this->vencedor && $this->metodo && $this->round;
    }

    /**
     * Pontuação antes do peso por momento da troca.
     */
    public function pontosBase(ConfiguracaoPontuacao $configuracao): int
    {
        return match (true) {
            ! $this->vencedor => 0,
            $this->metodo && $this->round => $configuracao->pontos_perfeito,
            $this->metodo => $configuracao->pontos_vencedor_metodo,
            $this->round => $configuracao->pontos_vencedor_round,
            default => $configuracao->pontos_vencedor,
        };
    }
}
