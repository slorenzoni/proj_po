<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Parte do card em que a luta acontece.
 */
enum TipoCard: string
{
    use HasOpcoes;

    case MainCard = 'main_card';
    case Prelims = 'prelims';

    public function label(): string
    {
        return match ($this) {
            self::MainCard => 'Card principal',
            self::Prelims => 'Preliminares',
        };
    }
}
