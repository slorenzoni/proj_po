<?php

namespace App\Enums;

/**
 * Situação do evento.
 */
enum StatusEvento: string
{
    case Agendado = 'agendado';
    case AoVivo = 'ao_vivo';
    case Encerrado = 'encerrado';
}
