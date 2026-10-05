<?php

namespace App\Services\Palpites;

/**
 * Situação do palpite de um usuário numa luta, neste momento.
 */
final readonly class JanelaDePalpite
{
    /**
     * @param  bool  $aberta  O usuário pode criar ou trocar o palpite agora.
     * @param  bool  $aoVivo  Troca feita num intervalo (só Membro), com peso reduzido.
     * @param  int  $roundDaTroca  0 = pré-luta; N = intervalo após o round N.
     * @param  string|null  $peso  Percentual que passa a valer se o palpite for trocado agora.
     * @param  string|null  $motivo  Por que está fechada, em texto para o usuário.
     */
    private function __construct(
        public bool $aberta,
        public bool $aoVivo = false,
        public int $roundDaTroca = 0,
        public ?string $peso = null,
        public ?string $motivo = null,
    ) {}

    public static function preLuta(string $peso): self
    {
        return new self(aberta: true, peso: $peso);
    }

    public static function aoVivo(int $roundDaTroca, string $peso): self
    {
        return new self(aberta: true, aoVivo: true, roundDaTroca: $roundDaTroca, peso: $peso);
    }

    public static function fechada(string $motivo): self
    {
        return new self(aberta: false, motivo: $motivo);
    }
}
