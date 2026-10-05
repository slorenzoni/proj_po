<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Método que o usuário pode escolher no palpite. O palpite não detalha o tipo de decisão.
 * Ippon, Waza-ari e Desclassificação são exclusivos do Judô.
 */
enum MetodoPalpite: string
{
    use HasOpcoes;

    case KoTko = 'ko_tko';
    case Finalizacao = 'finalizacao';
    case Decisao = 'decisao';
    case Ippon = 'ippon';
    case WazaAri = 'waza_ari';
    case Desclassificacao = 'desclassificacao';

    /**
     * Métodos que o usuário pode escolher: modalidades com rounds (MMA, Boxe) ou sem (Judô).
     *
     * @return list<self>
     */
    public static function paraModalidade(bool $usaRounds): array
    {
        return $usaRounds
            ? [self::KoTko, self::Finalizacao, self::Decisao]
            : [self::Ippon, self::WazaAri, self::Decisao, self::Desclassificacao];
    }

    public function label(): string
    {
        return match ($this) {
            self::KoTko => 'KO/TKO',
            self::Finalizacao => 'Finalização',
            self::Decisao => 'Decisão',
            self::Ippon => 'Ippon',
            self::WazaAri => 'Waza-ari',
            self::Desclassificacao => 'Desclassificação',
        };
    }
}
