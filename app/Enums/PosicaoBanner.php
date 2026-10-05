<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Área do site em que o banner é exibido.
 */
enum PosicaoBanner: string
{
    use HasOpcoes;

    case Home = 'home';
    case Evento = 'evento';
    case Luta = 'luta';
    case Chat = 'chat';
    case Categoria = 'categoria';
    case Blog = 'blog';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Evento => 'Evento',
            self::Luta => 'Luta',
            self::Chat => 'Chat',
            self::Categoria => 'Categoria',
            self::Blog => 'Blog',
        };
    }
}
