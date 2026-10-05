<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Situação do patrocinador.
 */
enum StatusPatrocinador: string
{
    use HasOpcoes;

    case Ativo = 'ativo';
    case Inativo = 'inativo';

    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
        };
    }
}
