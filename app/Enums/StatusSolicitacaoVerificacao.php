<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Situação do pedido de selo de verificado.
 */
enum StatusSolicitacaoVerificacao: string
{
    use HasOpcoes;

    case Pendente = 'pendente';
    case Aprovada = 'aprovada';
    case Rejeitada = 'rejeitada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Aprovada => 'Aprovada',
            self::Rejeitada => 'Rejeitada',
        };
    }
}
