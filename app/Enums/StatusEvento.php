<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Situação do evento.
 */
enum StatusEvento: string
{
    use HasOpcoes;

    case Agendado = 'agendado';
    case AoVivo = 'ao_vivo';
    case Encerrado = 'encerrado';

    public function label(): string
    {
        return match ($this) {
            self::Agendado => 'Agendado',
            self::AoVivo => 'Ao vivo',
            self::Encerrado => 'Encerrado',
        };
    }
}
