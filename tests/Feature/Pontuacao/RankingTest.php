<?php

use App\Enums\EscopoRanking;
use App\Enums\MetodoPalpite;
use App\Enums\MetodoVitoria;
use App\Enums\StatusLuta;
use App\Jobs\ProcessarResultadoDaLuta;
use App\Models\ConfiguracaoPontuacao;
use App\Models\Evento;
use App\Models\Luta;
use App\Models\Palpite;
use App\Models\Ranking;
use App\Models\User;
use App\Services\Pontuacao\AtualizadorDeRanking;

/**
 * Luta encerrada com vitória do participante A por KO/TKO no round 2.
 */
function lutaComResultado(?Evento $evento = null): Luta
{
    $luta = Luta::factory()->for($evento ?? Evento::factory())->create(['numero_rounds' => 3]);

    $luta->update([
        'status' => StatusLuta::Encerrada,
        'vencedor_id' => $luta->participante_a_id,
        'metodo_vitoria' => MetodoVitoria::KoTko,
        'round_fim' => 2,
    ]);

    return $luta;
}

/**
 * Palpite já pontuado. "acerto" define o que o palpite acertou; os pontos são livres,
 * para montar empates no ranking.
 *
 * @param  'perfeito'|'vencedor'|'erro'  $acerto
 */
function palpitePontuado(User $user, Luta $luta, string $acerto, float $pontos, ?string $criadoEm = null): Palpite
{
    return Palpite::factory()->for($luta)->for($user)->create([
        'vencedor_escolhido_id' => $acerto === 'erro' ? $luta->participante_b_id : $luta->participante_a_id,
        'metodo_escolhido' => $acerto === 'perfeito' ? MetodoPalpite::KoTko : null,
        'round_escolhido' => $acerto === 'perfeito' ? 2 : null,
        'pontos_obtidos' => $pontos,
        'created_at' => $criadoEm ?? now(),
    ]);
}

/**
 * Usuários do ranking geral, do primeiro ao último colocado.
 *
 * @return list<int>
 */
function ordemDoRankingGeral(): array
{
    app(AtualizadorDeRanking::class)->recalcular(EscopoRanking::Geral);

    return Ranking::query()->where('escopo', EscopoRanking::Geral)->orderBy('posicao')->pluck('user_id')->all();
}

test('the ranking orders users by total points', function () {
    [$primeiro, $segundo] = User::factory()->count(2)->create()->all();
    $lutaA = lutaComResultado();
    $lutaB = lutaComResultado();

    palpitePontuado($segundo, $lutaA, 'vencedor', 10);
    palpitePontuado($primeiro, $lutaA, 'vencedor', 10);
    palpitePontuado($primeiro, $lutaB, 'vencedor', 10);

    expect(ordemDoRankingGeral())->toBe([$primeiro->id, $segundo->id]);

    expect(Ranking::query()->where('user_id', $primeiro->id)->sole())
        ->pontos->toBe('20.00')
        ->vencedores_corretos->toBe(2)
        ->palpites_perfeitos->toBe(0)
        ->posicao->toBe(1);
});

test('a tie on points goes to whoever has more perfect picks', function () {
    [$comPerfeito, $semPerfeito] = User::factory()->count(2)->create()->all();
    $luta = lutaComResultado();

    palpitePontuado($semPerfeito, $luta, 'vencedor', 22);
    palpitePontuado($comPerfeito, $luta, 'perfeito', 22);

    expect(ordemDoRankingGeral())->toBe([$comPerfeito->id, $semPerfeito->id]);
});

test('a tie on points and perfect picks goes to whoever got more winners right', function () {
    [$doisAcertos, $umAcerto] = User::factory()->count(2)->create()->all();
    $lutaA = lutaComResultado();
    $lutaB = lutaComResultado();

    palpitePontuado($umAcerto, $lutaA, 'vencedor', 20);
    palpitePontuado($umAcerto, $lutaB, 'erro', 0);
    palpitePontuado($doisAcertos, $lutaA, 'vencedor', 10);
    palpitePontuado($doisAcertos, $lutaB, 'vencedor', 10);

    expect(ordemDoRankingGeral())->toBe([$doisAcertos->id, $umAcerto->id]);
});

test('a full tie goes to whoever made the pick first', function () {
    [$madrugador, $atrasado] = User::factory()->count(2)->create()->all();
    $luta = lutaComResultado();

    palpitePontuado($atrasado, $luta, 'vencedor', 10, '2026-10-02 10:00:00');
    palpitePontuado($madrugador, $luta, 'vencedor', 10, '2026-10-01 10:00:00');

    expect(ordemDoRankingGeral())->toBe([$madrugador->id, $atrasado->id]);
});

test('finishing a fight updates the general, event and organization rankings', function () {
    ConfiguracaoPontuacao::factory()->create();
    $luta = lutaComResultado();
    $outroEventoDaOrganizacao = lutaComResultado(Evento::factory()->for($luta->evento->organizacao)->create());
    $outraOrganizacao = lutaComResultado();
    $user = User::factory()->create();

    foreach ([$luta, $outroEventoDaOrganizacao, $outraOrganizacao] as $encerrada) {
        Palpite::factory()->for($encerrada)->for($user)->create();
        ProcessarResultadoDaLuta::dispatchSync($encerrada);
    }

    $pontos = fn (EscopoRanking $escopo, ?int $referencia) => Ranking::query()
        ->where('escopo', $escopo)
        ->where('referencia_id', $referencia)
        ->where('user_id', $user->id)
        ->sole()
        ->pontos;

    expect($pontos(EscopoRanking::Geral, null))->toBe('30.00')
        ->and($pontos(EscopoRanking::Organizacao, $luta->evento->organizacao_id))->toBe('20.00')
        ->and($pontos(EscopoRanking::Evento, $luta->evento_id))->toBe('10.00');
});

test('picks of fights that are not finished or were discarded stay out of the ranking', function () {
    $user = User::factory()->create();
    $descartado = palpitePontuado($user, lutaComResultado(), 'vencedor', 10);
    palpitePontuado($user, Luta::factory()->create(), 'vencedor', 10);

    expect(ordemDoRankingGeral())->toBe([$user->id]);

    $descartado->delete();

    expect(ordemDoRankingGeral())->toBe([])
        ->and(Ranking::withTrashed()->count())->toBe(1);
});
