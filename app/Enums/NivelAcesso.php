<?php

namespace App\Enums;

/**
 * Define o que um administrador pode fazer dentro do painel.
 * A existência de perfil_administrador responde "é admin?"; o nível responde "o que pode fazer?".
 */
enum NivelAcesso: string
{
    case SuperAdmin = 'super-admin';
    case Moderador = 'moderador';
    case Cadastrador = 'cadastrador';
}
