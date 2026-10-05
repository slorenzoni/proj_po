<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Forma de transmissão do evento.
 */
enum TipoTransmissao: string
{
    use HasOpcoes;

    case Free = 'free';
    case Ppv = 'ppv';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Gratuita',
            self::Ppv => 'Pay-per-view',
        };
    }
}
