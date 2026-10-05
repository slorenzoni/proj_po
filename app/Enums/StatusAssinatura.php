<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Situação de uma cobrança recorrente (plano ou selo de verificado).
 */
enum StatusAssinatura: string
{
    use HasOpcoes;

    case Ativa = 'ativa';
    case Cancelada = 'cancelada';
    case Inadimplente = 'inadimplente';

    public function label(): string
    {
        return match ($this) {
            self::Ativa => 'Ativa',
            self::Cancelada => 'Cancelada',
            self::Inadimplente => 'Inadimplente',
        };
    }
}
