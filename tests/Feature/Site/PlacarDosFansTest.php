<?php

use App\Enums\StatusLuta;
use App\Models\ConfiguracaoPontuacao;
use App\Models\Luta;
use App\Models\PlacarFan;
use App\Models\User;

beforeEach(function () {
    // Prazo de 5 minutos após o fim do round.
    ConfiguracaoPontuacao::factory()->create(['prazo_placar_fans_minutos' => 5]);
});

/**
 * Luta de 3 rounds no intervalo após o round 1, encerrado há $minutos minutos.
 */
function lutaComRoundEncerrado(int $minutos = 1): Luta
{
    return Luta::factory()->create([
        'numero_rounds' => 3,
        'status' => StatusLuta::EmAndamento,
        'round_atual' => 1,
        'em_intervalo' => true,
        'round_encerrado_em' => now()->subMinutes($minutos),
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function pontuarRound(User $user, Luta $luta, array $overrides = [])
{
    return test()->actingAs($user)->post(route('placar-dos-fans.store', $luta), [
        'round' => 1,
        'pontos_atleta_a' => 10,
        'pontos_atleta_b' => 9,
        ...$overrides,
    ]);
}

test('a client scores the round that has just ended, once', function () {
    $luta = lutaComRoundEncerrado();
    $user = cliente();

    pontuarRound($user, $luta)->assertSessionHasNoErrors();
    pontuarRound($user, $luta, ['pontos_atleta_b' => 8])->assertSessionHasErrors('round');

    expect(PlacarFan::query()->sole())
        ->user_id->toBe($user->id)
        ->round->toBe(1)
        ->pontos_atleta_b->toBe(9);
});

test('a round can no longer be scored after the deadline', function () {
    pontuarRound(cliente(), lutaComRoundEncerrado(minutos: 6))->assertSessionHasErrors('round');

    expect(PlacarFan::query()->count())->toBe(0);
});

test('only the round that has just ended is open', function () {
    pontuarRound(cliente(), lutaComRoundEncerrado(), ['round' => 2])->assertSessionHasErrors('round');
});

test('the previous round stays open within the deadline after the next one starts', function () {
    $luta = lutaComRoundEncerrado();
    $luta->update(['round_atual' => 2, 'em_intervalo' => false]);

    pontuarRound(cliente(), $luta)->assertSessionHasNoErrors();
});

test('the last round is scored after the fight is finished', function () {
    $luta = lutaComRoundEncerrado();
    $luta->update(['status' => StatusLuta::Encerrada, 'round_atual' => 3, 'em_intervalo' => false, 'round_fim' => 3]);

    pontuarRound(cliente(), $luta, ['round' => 3])->assertSessionHasNoErrors();
});

test('a fan score follows the ten point must system', function (int $pontosA, int $pontosB, bool $valido) {
    $response = pontuarRound(cliente(), lutaComRoundEncerrado(), [
        'pontos_atleta_a' => $pontosA,
        'pontos_atleta_b' => $pontosB,
    ]);

    $valido ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors();

    expect(PlacarFan::query()->count())->toBe($valido ? 1 : 0);
})->with([
    'round empatado' => [10, 10, true],
    'round dominante' => [7, 10, true],
    'ninguém com dez' => [9, 9, false],
    'abaixo de sete' => [10, 6, false],
    'acima de dez' => [11, 10, false],
]);

test('fights without rounds have no fan score', function () {
    $luta = Luta::factory()->create(['numero_rounds' => null, 'status' => StatusLuta::Encerrada, 'round_encerrado_em' => now()]);

    pontuarRound(cliente(), $luta)->assertSessionHasErrors('round');

    $this->get(route('lutas.show', $luta))->assertInertia(fn ($page) => $page->where('placar', null));
});

test('accounts without a client profile cannot score rounds', function () {
    pontuarRound(administrador(), lutaComRoundEncerrado())->assertForbidden();
});

test('the fight page shows the community average and what the visitor already scored', function () {
    $luta = lutaComRoundEncerrado();
    $user = cliente();
    PlacarFan::factory()->for($luta)->for($user)->create(['round' => 1, 'pontos_atleta_a' => 10, 'pontos_atleta_b' => 9]);
    PlacarFan::factory()->for($luta)->create(['round' => 1, 'pontos_atleta_a' => 9, 'pontos_atleta_b' => 10]);

    $this->actingAs($user)->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page
            ->where('placar.round_aberto', 1)
            ->where('placar.rounds_pontuados', [1])
            ->where('placar.medias.0', ['round' => 1, 'media_a' => 9.5, 'media_b' => 9.5, 'votos' => 2]));
});
