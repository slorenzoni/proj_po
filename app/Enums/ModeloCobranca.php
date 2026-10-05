<?php

namespace App\Enums;

/**
 * Como o banner é cobrado do patrocinador.
 */
enum ModeloCobranca: string
{
    case Cpm = 'cpm';
    case Cpc = 'cpc';
    case Fixo = 'fixo';
}
