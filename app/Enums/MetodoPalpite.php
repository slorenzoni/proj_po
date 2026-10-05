<?php

namespace App\Enums;

/**
 * Método que o usuário pode escolher no palpite. O palpite não detalha o tipo de decisão.
 * Ippon, Waza-ari e Desclassificação são exclusivos do Judô.
 */
enum MetodoPalpite: string
{
    case KoTko = 'ko_tko';
    case Finalizacao = 'finalizacao';
    case Decisao = 'decisao';
    case Ippon = 'ippon';
    case WazaAri = 'waza_ari';
    case Desclassificacao = 'desclassificacao';
}
