<?php

use App\Enums\MetodoPalpite;
use App\Enums\StatusLuta;
use App\Models\Atleta;
use App\Models\Luta;
use App\Models\Palpite;
use App\Models\PalpiteHistorico;
use App\Models\User;
use Database\Seeders\ConfiguracaoPontuacaoSeeder;

beforeEach(function () {
    // Grades aprovadas: 3 rounds → 100 / 70 / 40; 5 rounds → 100 / 80 / 60 / 40 / 20.
    $this->seed(ConfiguracaoPontuacaoSeeder::class);
});

/**
 * Envia o palpite do usuário para a luta: participante A, salvo o que for sobrescrito.
 *
 * @param  array<string, mixed>  $overrides
 */
function palpitar(User $user, Luta $luta, array $overrides = [])
{
    return test()->actingAs($user)->post(route('palpites.store', $luta), [
        'vencedor_id' => $luta->participante_a_id,
        ...$overrides,
    ]);
}

/**
 * Luta de 3 rounds no intervalo após o round informado.
 */
function lutaNoIntervalo(int $round = 1): Luta
{
    return Luta::factory()->create([
        'numero_rounds' => 3,
        'status' => StatusLuta::EmAndamento,
        'round_atual' => $round,
        'em_intervalo' => true,
    ]);
}

test('guests must log in to make a pick', function () {
    $luta = Luta::factory()->create();

    $this->post(route('palpites.store', $luta), ['vencedor_id' => $luta->participante_a_id])
        ->assertRedirect(route('login'));
});

test('a free client picks winner, method and round before the fight at full weight', function () {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);
    $user = cliente();

    palpitar($user, $luta, ['metodo' => MetodoPalpite::KoTko->value, 'round' => 2])->assertSessionHasNoErrors();

    expect(Palpite::query()->sole())
        ->user_id->toBe($user->id)
        ->vencedor_escolhido_id->toBe($luta->participante_a_id)
        ->metodo_escolhido->toBe(MetodoPalpite::KoTko)
        ->round_escolhido->toBe(2)
        ->round_da_troca->toBe(0)
        ->peso_aplicado->toBe('100.00')
        ->and(PalpiteHistorico::query()->count())->toBe(1);
});

test('changing a pick before the fight keeps a single pick at full weight and logs the change', function () {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);
    $user = cliente();

    palpitar($user, $luta);
    palpitar($user, $luta, ['vencedor_id' => $luta->participante_b_id])->assertSessionHasNoErrors();

    expect(Palpite::query()->sole())
        ->vencedor_escolhido_id->toBe($luta->participante_b_id)
        ->peso_aplicado->toBe('100.00')
        ->and(PalpiteHistorico::query()->count())->toBe(2);
});

test('sending the same pick again is not a change', function () {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);
    $user = cliente();

    palpitar($user, $luta);
    palpitar($user, $luta)->assertSessionHasNoErrors();

    expect(PalpiteHistorico::query()->count())->toBe(1);
});

test('an account without a client profile cannot make picks', function () {
    $luta = Luta::factory()->create();

    palpitar(administrador(), $luta)->assertSessionHasErrors('vencedor_id');

    expect(Palpite::query()->count())->toBe(0);
});

test('a free client cannot pick once the fight has started, not even in the interval', function (bool $emIntervalo) {
    $luta = lutaNoIntervalo();
    $luta->update(['em_intervalo' => $emIntervalo]);

    palpitar(cliente(), $luta)->assertSessionHasErrors('vencedor_id');

    expect(Palpite::query()->count())->toBe(0);
})->with(['durante o round' => false, 'no intervalo' => true]);

test('a member cannot change the pick while a round is running', function () {
    $luta = lutaNoIntervalo();
    $luta->update(['em_intervalo' => false]);

    palpitar(membro(), $luta)->assertSessionHasErrors('vencedor_id');
});

test('a member changing the pick in an interval gets the weight of that moment', function (int $round, string $peso) {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);
    $user = membro();
    palpitar($user, $luta);

    $luta->update(['status' => StatusLuta::EmAndamento, 'round_atual' => $round, 'em_intervalo' => true]);
    palpitar($user, $luta, ['vencedor_id' => $luta->participante_b_id])->assertSessionHasNoErrors();

    expect(Palpite::query()->sole())
        ->vencedor_escolhido_id->toBe($luta->participante_b_id)
        ->round_da_troca->toBe($round)
        ->peso_aplicado->toBe($peso);
})->with([
    'após o round 1' => [1, '70.00'],
    'após o round 2' => [2, '40.00'],
]);

test('going back to the original pick does not restore the full weight', function () {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);
    $user = membro();
    palpitar($user, $luta);

    $luta->update(['status' => StatusLuta::EmAndamento, 'round_atual' => 1, 'em_intervalo' => true]);
    palpitar($user, $luta, ['vencedor_id' => $luta->participante_b_id]);
    palpitar($user, $luta);

    expect(Palpite::query()->sole())
        ->vencedor_escolhido_id->toBe($luta->participante_a_id)
        ->peso_aplicado->toBe('70.00')
        ->and(PalpiteHistorico::query()->count())->toBe(3);
});

test('a member who keeps the same pick in the interval keeps the full weight', function () {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);
    $user = membro();
    palpitar($user, $luta);

    $luta->update(['status' => StatusLuta::EmAndamento, 'round_atual' => 1, 'em_intervalo' => true]);
    palpitar($user, $luta)->assertSessionHasNoErrors();

    expect(Palpite::query()->sole()->peso_aplicado)->toBe('100.00');
});

test('a member can make the first pick in an interval, already at the reduced weight', function () {
    palpitar(membro(), lutaNoIntervalo(2))->assertSessionHasNoErrors();

    expect(Palpite::query()->sole()->peso_aplicado)->toBe('40.00');
});

test('picks are closed for finished and cancelled fights', function (StatusLuta $status) {
    $luta = Luta::factory()->create(['status' => $status]);

    palpitar(membro(), $luta)->assertSessionHasErrors('vencedor_id');
})->with([StatusLuta::Encerrada, StatusLuta::Cancelada]);

test('a pick must follow the options of the fight', function (Closure $overrides, string $campo) {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);

    palpitar(cliente(), $luta, $overrides($luta))->assertSessionHasErrors($campo);

    expect(Palpite::query()->count())->toBe(0);
})->with([
    'vencedor de fora da luta' => [fn () => ['vencedor_id' => Atleta::factory()->create()->id], 'vencedor_id'],
    'método de outra modalidade' => [fn () => ['metodo' => MetodoPalpite::Ippon->value], 'metodo'],
    'round além da luta' => [fn () => ['round' => 4], 'round'],
    'round com decisão' => [fn () => ['metodo' => MetodoPalpite::Decisao->value, 'round' => 3], 'round'],
]);

test('a fight without rounds accepts its own methods and no round', function () {
    $luta = Luta::factory()->create(['numero_rounds' => null]);
    $user = cliente();

    palpitar($user, $luta, ['metodo' => MetodoPalpite::KoTko->value])->assertSessionHasErrors('metodo');
    palpitar($user, $luta, ['metodo' => MetodoPalpite::Ippon->value, 'round' => 1])->assertSessionHasErrors('round');
    palpitar($user, $luta, ['metodo' => MetodoPalpite::Ippon->value])->assertSessionHasNoErrors();

    expect(Palpite::query()->sole())
        ->metodo_escolhido->toBe(MetodoPalpite::Ippon)
        ->peso_aplicado->toBe('100.00');
});

test('the fight page tells each visitor whether they can pick now', function () {
    $luta = lutaNoIntervalo();
    $free = cliente();
    Palpite::factory()->for($luta)->for($free)->create();

    $this->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page
            ->component('site/lutas/Show')
            ->where('palpite.janela', null)
            ->where('palpite.meu', null)
            ->where('palpite.distribuicao', ['a' => 1, 'b' => 0]));

    $this->actingAs($free)->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page
            ->where('palpite.janela.aberta', false)
            ->where('palpite.meu.vencedor_id', $luta->participante_a_id));

    $this->actingAs(membro())->get(route('lutas.show', $luta))
        ->assertInertia(fn ($page) => $page
            ->where('palpite.janela.aberta', true)
            ->where('palpite.janela.ao_vivo', true)
            ->where('palpite.janela.peso', '70.00'));
});
