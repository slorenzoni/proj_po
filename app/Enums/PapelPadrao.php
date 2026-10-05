<?php

namespace App\Enums;

/**
 * Apenas dica de UX (tela inicial pós-login) para quem tem mais de um perfil.
 * Nunca deve ser usado para autorização.
 */
enum PapelPadrao: string
{
    case Cliente = 'cliente';
    case Administrador = 'administrador';
}
