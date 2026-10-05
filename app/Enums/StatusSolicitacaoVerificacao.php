<?php

namespace App\Enums;

/**
 * Situação do pedido de selo de verificado.
 */
enum StatusSolicitacaoVerificacao: string
{
    case Pendente = 'pendente';
    case Aprovada = 'aprovada';
    case Rejeitada = 'rejeitada';
}
