<?php

namespace App\Enums;

/**
 * Situação do banner.
 */
enum StatusBanner: string
{
    case Ativo = 'ativo';
    case Pausado = 'pausado';
    case Expirado = 'expirado';
}
