<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Periodicidade de uma cobrança recorrente.
 */
enum Periodicidade: string
{
    use HasOpcoes;

    case Mensal = 'mensal';

    public function label(): string
    {
        return match ($this) {
            self::Mensal => 'Mensal',
        };
    }
}
