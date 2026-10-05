<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Situação da luta. Luta cancelada descarta os palpites sem pontuação.
 */
enum StatusLuta: string
{
    use HasOpcoes;

    case Agendada = 'agendada';
    case EmAndamento = 'em_andamento';
    case Encerrada = 'encerrada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Agendada => 'Agendada',
            self::EmAndamento => 'Em andamento',
            self::Encerrada => 'Encerrada',
            self::Cancelada => 'Cancelada',
        };
    }
}
