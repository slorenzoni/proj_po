<?php

namespace App\Enums;

/**
 * Situação do patrocinador.
 */
enum StatusPatrocinador: string
{
    case Ativo = 'ativo';
    case Inativo = 'inativo';
}
