<?php

namespace App\Enums;

/**
 * Resultado oficial da luta. Os três últimos casos são exclusivos do Judô.
 */
enum MetodoVitoria: string
{
    case KoTko = 'ko_tko';
    case Submissao = 'submissao';
    case DecisaoUnanime = 'decisao_unanime';
    case DecisaoDividida = 'decisao_dividida';
    case DecisaoMajoritaria = 'decisao_majoritaria';
    case Empate = 'empate';
    case SemResultado = 'sem_resultado';
    case Desqualificacao = 'desqualificacao';
    case Ippon = 'ippon';
    case WazaAri = 'waza_ari';
    case DecisaoGoldenScore = 'decisao_golden_score';
}
