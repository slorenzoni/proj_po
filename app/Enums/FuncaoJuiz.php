<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Função do juiz numa luta. Só o juiz lateral pontua no placar.
 */
enum FuncaoJuiz: string
{
    use HasOpcoes;

    case Arbitro = 'arbitro';
    case JuizLateral = 'juiz_lateral';

    public function label(): string
    {
        return match ($this) {
            self::Arbitro => 'Árbitro',
            self::JuizLateral => 'Juiz lateral',
        };
    }
}
