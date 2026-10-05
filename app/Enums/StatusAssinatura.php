<?php

namespace App\Enums;

/**
 * Situação de uma cobrança recorrente (plano ou selo de verificado).
 */
enum StatusAssinatura: string
{
    case Ativa = 'ativa';
    case Cancelada = 'cancelada';
    case Inadimplente = 'inadimplente';
}
