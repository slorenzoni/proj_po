<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Situação do banner.
 */
enum StatusBanner: string
{
    use HasOpcoes;

    case Ativo = 'ativo';
    case Pausado = 'pausado';
    case Expirado = 'expirado';

    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Pausado => 'Pausado',
            self::Expirado => 'Expirado',
        };
    }
}
