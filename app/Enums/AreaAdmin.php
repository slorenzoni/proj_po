<?php

namespace App\Enums;

/**
 * Áreas do painel administrativo. Cada nível de acesso enxerga um conjunto delas
 * (ver NivelAcesso::areas()); o super-admin enxerga todas.
 */
enum AreaAdmin: string
{
    /** Cadastros básicos, atletas, eventos, lutas, luta ao vivo e patrocínio. */
    case Cadastros = 'cadastros';

    /** Solicitações de selo de verificado. */
    case Verificacoes = 'verificacoes';

    /** Usuários, papéis e perfis de administrador. */
    case Usuarios = 'usuarios';

    /** Pontuação dos palpites e pesos por momento da troca. */
    case Configuracoes = 'configuracoes';

    /**
     * Nome do gate que protege a área (usado em rotas e no front-end).
     */
    public function gate(): string
    {
        return 'admin.'.$this->value;
    }
}
