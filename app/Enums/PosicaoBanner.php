<?php

namespace App\Enums;

/**
 * Área do site em que o banner é exibido.
 */
enum PosicaoBanner: string
{
    case Home = 'home';
    case Evento = 'evento';
    case Luta = 'luta';
    case Chat = 'chat';
    case Categoria = 'categoria';
    case Blog = 'blog';
}
