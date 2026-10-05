<?php

namespace App\Enums;

/**
 * Forma de transmissão do evento.
 */
enum TipoTransmissao: string
{
    case Free = 'free';
    case Ppv = 'ppv';
}
