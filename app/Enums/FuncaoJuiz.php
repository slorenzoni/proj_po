<?php

namespace App\Enums;

/**
 * Função do juiz numa luta. Só o juiz lateral pontua no placar.
 */
enum FuncaoJuiz: string
{
    case Arbitro = 'arbitro';
    case JuizLateral = 'juiz_lateral';
}
