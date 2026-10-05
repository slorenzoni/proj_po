<?php

namespace App\Enums;

/**
 * Situação da postagem do blog.
 */
enum StatusPostagem: string
{
    case Rascunho = 'rascunho';
    case Publicado = 'publicado';
    case Arquivado = 'arquivado';
}
