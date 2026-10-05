<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Plano do cliente.
 */
enum PlanoAssinatura: string
{
    use HasOpcoes;

    case Free = 'free';
    case Membro = 'membro';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Membro => 'Membro',
        };
    }
}
