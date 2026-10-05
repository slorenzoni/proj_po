<?php

namespace App\Enums;

/**
 * Situação da luta. Luta cancelada descarta os palpites sem pontuação.
 */
enum StatusLuta: string
{
    case Agendada = 'agendada';
    case EmAndamento = 'em_andamento';
    case Encerrada = 'encerrada';
    case Cancelada = 'cancelada';
}
