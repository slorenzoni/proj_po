<?php

namespace App\Enums;

use App\Concerns\HasOpcoes;

/**
 * Resultado oficial da luta. Os três últimos casos são exclusivos do Judô.
 */
enum MetodoVitoria: string
{
    use HasOpcoes;

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

    /**
     * Empate e "sem resultado" encerram a luta sem vencedor.
     */
    public function temVencedor(): bool
    {
        return ! in_array($this, [self::Empate, self::SemResultado], true);
    }

    /**
     * Métodos válidos para a modalidade: com rounds (MMA, Boxe) ou sem (Judô).
     *
     * @return list<self>
     */
    public static function paraModalidade(bool $usaRounds): array
    {
        return $usaRounds
            ? [
                self::KoTko,
                self::Submissao,
                self::DecisaoUnanime,
                self::DecisaoDividida,
                self::DecisaoMajoritaria,
                self::Empate,
                self::SemResultado,
                self::Desqualificacao,
            ]
            : [self::Ippon, self::WazaAri, self::DecisaoGoldenScore, self::Desqualificacao, self::SemResultado];
    }

    /**
     * Método do palpite que corresponde a este resultado, ou nulo quando o resultado
     * não pontua para ninguém: empate, sem resultado e, nas modalidades com rounds,
     * desqualificação. O palpite não distingue os tipos de decisão.
     */
    public function paraPalpite(bool $usaRounds): ?MetodoPalpite
    {
        return match ($this) {
            self::KoTko => MetodoPalpite::KoTko,
            self::Submissao => MetodoPalpite::Finalizacao,
            self::DecisaoUnanime, self::DecisaoDividida, self::DecisaoMajoritaria, self::DecisaoGoldenScore => MetodoPalpite::Decisao,
            self::Ippon => MetodoPalpite::Ippon,
            self::WazaAri => MetodoPalpite::WazaAri,
            self::Desqualificacao => $usaRounds ? null : MetodoPalpite::Desclassificacao,
            self::Empate, self::SemResultado => null,
        };
    }

    /**
     * Em qual detalhamento do cartel do atleta o resultado entra: "ko", "submissao" ou
     * "decisao". O cartel só tem esses três; desqualificação e os métodos do Judô caem
     * em "decisao" para o total de lutas fechar. Nulo quando não há vencedor.
     */
    public function tipoNoCartel(): ?string
    {
        return match ($this) {
            self::KoTko => 'ko',
            self::Submissao => 'submissao',
            self::Empate, self::SemResultado => null,
            default => 'decisao',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::KoTko => 'KO/TKO',
            self::Submissao => 'Submissão',
            self::DecisaoUnanime => 'Decisão unânime',
            self::DecisaoDividida => 'Decisão dividida',
            self::DecisaoMajoritaria => 'Decisão majoritária',
            self::Empate => 'Empate',
            self::SemResultado => 'Sem resultado (No Contest)',
            self::Desqualificacao => 'Desqualificação',
            self::Ippon => 'Ippon',
            self::WazaAri => 'Waza-ari',
            self::DecisaoGoldenScore => 'Decisão / Golden Score',
        };
    }
}
