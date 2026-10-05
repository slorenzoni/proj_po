<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Situação da postagem do blog.
 */
enum StatusPostagem: string
{
    use HasOpcoes;

    case Rascunho = 'rascunho';
    case Publicado = 'publicado';
    case Arquivado = 'arquivado';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Publicado => 'Publicado',
            self::Arquivado => 'Arquivado',
        };
    }
}
