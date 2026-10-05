<?php

use App\Enums\MetodoPalpite;
use App\Enums\MetodoVitoria;
use App\Enums\StatusLuta;
use App\Jobs\ProcessarResultadoDaLuta;
use App\Models\ConfiguracaoPontuacao;
use App\Models\Luta;
use App\Models\Palpite;
use App\Models\Ranking;

beforeEach(function () {
    // Padrão geral aprovado: 10 / 15 / 15 / 22.
    ConfiguracaoPontuacao::factory()->create();
});

/**
 * Luta de 3 rounds já encerrada: o participante A vence por KO/TKO no round 2,
 * salvo o que for sobrescrito.
 *
 * @param  array<string, mixed>  $overrides
 */
function lutaEncerrada(array $overrides = []): Luta
{
    $luta = Luta::factory()->create(['numero_rounds' => 3]);

    $luta->update([
        'status' => StatusLuta::Encerrada,
        'vencedor_id' => $luta->participante_a_id,
        'metodo_vitoria' => MetodoVitoria::KoTko,
        'round_fim' => 2,
        ...$overrides,
    ]);

    return $luta;
}

/**
 * Pontos obtidos por um palpite no participante A com o método, round e peso informados.
 *
 * @param  array<string, mixed>  $palpite
 */
function pontosDoPalpite(Luta $luta, array $palpite = []): ?string
{
    $registro = Palpite::factory()->for($luta)->create($palpite);

    ProcessarResultadoDaLuta::dispatchSync($luta);

    return $registro->refresh()->pontos_obtidos;
}

test('a pick scores according to what it got right', function (array $palpite, string $pontos) {
    expect(pontosDoPalpite(lutaEncerrada(), $palpite))->toBe($pontos);
})->with([
    'só o vencedor' => [[], '10.00'],
    'vencedor e método' => [['metodo_escolhido' => MetodoPalpite::KoTko], '15.00'],
    'vencedor e round' => [['round_escolhido' => 2], '15.00'],
    'vencedor, método e round' => [['metodo_escolhido' => MetodoPalpite::KoTko, 'round_escolhido' => 2], '22.00'],
    'método errado e round certo' => [['metodo_escolhido' => MetodoPalpite::Finalizacao, 'round_escolhido' => 2], '15.00'],
    'método certo e round errado' => [['metodo_escolhido' => MetodoPalpite::KoTko, 'round_escolhido' => 1], '15.00'],
    'método e round errados' => [['metodo_escolhido' => MetodoPalpite::Decisao, 'round_escolhido' => 3], '10.00'],
]);

test('a pick on the loser scores nothing, even with the right method and round', function () {
    $luta = lutaEncerrada();

    expect(pontosDoPalpite($luta, [
        'vencedor_escolhido_id' => $luta->participante_b_id,
        'metodo_escolhido' => MetodoPalpite::KoTko,
        'round_escolhido' => 2,
    ]))->toBe('0.00');
});

test('the weight of the last trade is applied to the base score', function () {
    expect(pontosDoPalpite(lutaEncerrada(), [
        'metodo_escolhido' => MetodoPalpite::KoTko,
        'round_escolhido' => 2,
        'peso_aplicado' => 40,
    ]))->toBe('8.80');
});

test('every kind of decision matches a decision pick', function (MetodoVitoria $resultado) {
    $luta = lutaEncerrada(['metodo_vitoria' => $resultado, 'round_fim' => 3]);

    expect(pontosDoPalpite($luta, ['metodo_escolhido' => MetodoPalpite::Decisao]))->toBe('15.00');
})->with([MetodoVitoria::DecisaoUnanime, MetodoVitoria::DecisaoDividida, MetodoVitoria::DecisaoMajoritaria]);

test('a submission matches a finish pick', function () {
    $luta = lutaEncerrada(['metodo_vitoria' => MetodoVitoria::Submissao]);

    expect(pontosDoPalpite($luta, ['metodo_escolhido' => MetodoPalpite::Finalizacao, 'round_escolhido' => 2]))->toBe('22.00');
});

test('results without a scoring outcome give zero to everyone', function (array $resultado) {
    $luta = lutaEncerrada($resultado);

    expect(pontosDoPalpite($luta, ['round_escolhido' => 2]))->toBe('0.00');
})->with([
    'empate' => [['metodo_vitoria' => MetodoVitoria::Empate, 'vencedor_id' => null]],
    'sem resultado' => [['metodo_vitoria' => MetodoVitoria::SemResultado, 'vencedor_id' => null]],
    'desqualificação em modalidade com rounds' => [['metodo_vitoria' => MetodoVitoria::Desqualificacao]],
]);

test('a fight without rounds scores winner and method, never the round', function (MetodoVitoria $resultado, MetodoPalpite $palpite) {
    $luta = lutaEncerrada(['numero_rounds' => null, 'round_fim' => null, 'metodo_vitoria' => $resultado]);

    expect(pontosDoPalpite($luta, ['metodo_escolhido' => $palpite, 'round_escolhido' => 1]))->toBe('15.00');
})->with([
    'ippon' => [MetodoVitoria::Ippon, MetodoPalpite::Ippon],
    'waza-ari' => [MetodoVitoria::WazaAri, MetodoPalpite::WazaAri],
    'golden score' => [MetodoVitoria::DecisaoGoldenScore, MetodoPalpite::Decisao],
    'desclassificação' => [MetodoVitoria::Desqualificacao, MetodoPalpite::Desclassificacao],
]);

test('a category with its own scoring is scored by it', function () {
    $luta = lutaEncerrada();
    ConfiguracaoPontuacao::factory()->paraCategoria($luta->categoria)->create(['pontos_vencedor' => 12]);

    expect(pontosDoPalpite($luta))->toBe('12.00')
        ->and(pontosDoPalpite(lutaEncerrada()))->toBe('10.00');
});

test('a fight that has not finished leaves its picks without points', function () {
    $luta = Luta::factory()->create(['status' => StatusLuta::EmAndamento]);

    expect(pontosDoPalpite($luta))->toBeNull()
        ->and(Ranking::query()->count())->toBe(0);
});

test('processing the same result twice does not duplicate points or ranking rows', function () {
    $luta = lutaEncerrada();
    $palpite = Palpite::factory()->for($luta)->create();

    ProcessarResultadoDaLuta::dispatchSync($luta);
    ProcessarResultadoDaLuta::dispatchSync($luta);

    expect($palpite->refresh()->pontos_obtidos)->toBe('10.00')
        ->and(Ranking::query()->where('user_id', $palpite->user_id)->count())->toBe(3)
        ->and(Ranking::query()->where('user_id', $palpite->user_id)->pluck('pontos')->unique()->all())->toBe(['10.00']);
});
