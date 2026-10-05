<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Abrangência de uma linha do ranking.
 */
enum EscopoRanking: string
{
    use HasOpcoes;

    case Geral = 'geral';
    case Evento = 'evento';
    case Organizacao = 'organizacao';

    public function label(): string
    {
        return match ($this) {
            self::Geral => 'Geral',
            self::Evento => 'Por evento',
            self::Organizacao => 'Por organização',
        };
    }
}
