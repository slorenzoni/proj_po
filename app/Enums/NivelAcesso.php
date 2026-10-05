<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Define o que um administrador pode fazer dentro do painel.
 * A existência de perfil_administrador responde "é admin?"; o nível responde "o que pode fazer?".
 */
enum NivelAcesso: string
{
    use HasOpcoes;

    case SuperAdmin = 'super-admin';
    case Moderador = 'moderador';
    case Cadastrador = 'cadastrador';

    /**
     * Áreas do painel que o nível enxerga e altera.
     *
     * @return list<AreaAdmin>
     */
    public function areas(): array
    {
        return match ($this) {
            self::SuperAdmin => AreaAdmin::cases(),
            self::Moderador => [AreaAdmin::Verificacoes],
            self::Cadastrador => [AreaAdmin::Cadastros],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super-admin',
            self::Moderador => 'Moderador',
            self::Cadastrador => 'Cadastrador',
        };
    }
}
