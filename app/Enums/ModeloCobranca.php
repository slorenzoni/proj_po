<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Como o banner é cobrado do patrocinador.
 */
enum ModeloCobranca: string
{
    use HasOpcoes;

    case Cpm = 'cpm';
    case Cpc = 'cpc';
    case Fixo = 'fixo';

    public function label(): string
    {
        return match ($this) {
            self::Cpm => 'CPM (por mil impressões)',
            self::Cpc => 'CPC (por clique)',
            self::Fixo => 'Fixo',
        };
    }
}
