<?php

namespace App\Enums;

/**
 * Abrangência de uma linha do ranking.
 */
enum EscopoRanking: string
{
    case Geral = 'geral';
    case Evento = 'evento';
    case Organizacao = 'organizacao';
}
