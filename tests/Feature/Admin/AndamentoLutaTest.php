<?php

use App\Enums\FuncaoJuiz;
use App\Enums\MetodoVitoria;
use App\Enums\NivelAcesso;
use App\Enums\StatusLuta;
use App\Jobs\ProcessarResultadoDaLuta;
use App\Models\Atleta;
use App\Models\Juiz;
use App\Models\Luta;
use App\Models\Placar;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // A pontuação dos palpites roda em fila e tem testes próprios (tests/Feature/Pontuacao).
    Queue::fake();
    $this->actingAs(administrador(NivelAcesso::Cadastrador));
});

/**
 * Luta já em andamento, com um juiz lateral escalado.
 *
 * @return array{0: Luta, 1: Juiz}
 */
function lutaEmAndamentoComJuizLateral(): array
{
    $luta = Luta::factory()->create(['status' => StatusLuta::EmAndamento, 'round_atual' => 1, 'numero_rounds' => 3]);
    $juiz = Juiz::factory()->create();
    $luta->juizes()->attach($juiz, ['funcao' => FuncaoJuiz::JuizLateral]);

    return [$luta, $juiz];
}

test('a three round fight runs from the first round to the result', function () {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);

    $this->post(route('admin.lutas.iniciar', $luta));
    expect($luta->refresh())
        ->status->toBe(StatusLuta::EmAndamento)
        ->round_atual->toBe(1)
        ->em_intervalo->toBeFalse();

    $this->post(route('admin.lutas.encerrar-round', $luta));
    expect($luta->refresh())->round_atual->toBe(1)->em_intervalo->toBeTrue();

    $this->post(route('admin.lutas.proximo-round', $luta));
    expect($luta->refresh())->round_atual->toBe(2)->em_intervalo->toBeFalse();

    $this->post(route('admin.lutas.encerrar-round', $luta));
    $this->post(route('admin.lutas.proximo-round', $luta));
    expect($luta->refresh())->round_atual->toBe(3)->em_intervalo->toBeFalse();

    $this->post(route('admin.lutas.encerrar', $luta), [
        'metodo_vitoria' => MetodoVitoria::DecisaoUnanime->value,
        'vencedor_id' => $luta->participante_a_id,
        'round_fim' => 3,
        'tempo_fim' => '05:00',
    ])->assertSessionHasNoErrors();

    expect($luta->refresh())
        ->status->toBe(StatusLuta::Encerrada)
        ->vencedor_id->toBe($luta->participante_a_id)
        ->metodo_vitoria->toBe(MetodoVitoria::DecisaoUnanime)
        ->round_fim->toBe(3)
        ->tempo_fim->toBe('05:00');

    Queue::assertPushed(ProcessarResultadoDaLuta::class, fn (ProcessarResultadoDaLuta $job) => $job->luta->is($luta));
});

test('there is no interval after the last round', function () {
    $luta = Luta::factory()->create(['status' => StatusLuta::EmAndamento, 'round_atual' => 3, 'numero_rounds' => 3]);

    $this->post(route('admin.lutas.encerrar-round', $luta));

    expect($luta->refresh()->em_intervalo)->toBeFalse();
});

test('actions that do not fit the current moment change nothing', function (string $rota, array $estado) {
    $luta = Luta::factory()->create(['numero_rounds' => 3, ...$estado]);

    $this->post(route($rota, $luta))->assertRedirect();

    expect($luta->refresh()->only(array_keys($estado)))->toEqual($estado);
})->with([
    'iniciar luta já iniciada' => ['admin.lutas.iniciar', ['status' => StatusLuta::EmAndamento, 'round_atual' => 2]],
    'encerrar round de luta agendada' => ['admin.lutas.encerrar-round', ['status' => StatusLuta::Agendada, 'em_intervalo' => false]],
    'encerrar round durante o intervalo' => ['admin.lutas.encerrar-round', ['status' => StatusLuta::EmAndamento, 'round_atual' => 1, 'em_intervalo' => true]],
    'próximo round fora do intervalo' => ['admin.lutas.proximo-round', ['status' => StatusLuta::EmAndamento, 'round_atual' => 1, 'em_intervalo' => false]],
    'cancelar luta encerrada' => ['admin.lutas.cancelar', ['status' => StatusLuta::Encerrada]],
]);

test('a fight can only be finished while in progress', function () {
    $luta = Luta::factory()->create(['numero_rounds' => 3]);

    $this->post(route('admin.lutas.encerrar', $luta), [
        'metodo_vitoria' => MetodoVitoria::KoTko->value,
        'vencedor_id' => $luta->participante_a_id,
    ]);

    expect($luta->refresh()->status)->toBe(StatusLuta::Agendada);
});

test('a result with a winner requires one of the two fighters', function (?Closure $vencedor) {
    $luta = Luta::factory()->create(['status' => StatusLuta::EmAndamento, 'round_atual' => 1, 'numero_rounds' => 3]);

    $this->post(route('admin.lutas.encerrar', $luta), [
        'metodo_vitoria' => MetodoVitoria::KoTko->value,
        'vencedor_id' => $vencedor === null ? null : $vencedor(),
    ])->assertSessionHasErrors('vencedor_id');

    expect($luta->refresh()->status)->toBe(StatusLuta::EmAndamento);
})->with([
    'sem vencedor' => [null],
    'atleta de fora da luta' => [fn () => Atleta::factory()->create()->id],
]);

test('a draw finishes the fight without a winner', function () {
    $luta = Luta::factory()->create(['status' => StatusLuta::EmAndamento, 'round_atual' => 3, 'numero_rounds' => 3]);

    $this->post(route('admin.lutas.encerrar', $luta), [
        'metodo_vitoria' => MetodoVitoria::Empate->value,
        'vencedor_id' => $luta->participante_a_id,
    ])->assertSessionHasNoErrors();

    expect($luta->refresh())
        ->status->toBe(StatusLuta::Encerrada)
        ->vencedor_id->toBeNull();
});

test('a fight without rounds goes straight from start to result with its own methods', function () {
    $luta = Luta::factory()->create(['numero_rounds' => null]);

    $this->post(route('admin.lutas.iniciar', $luta));
    expect($luta->refresh())->status->toBe(StatusLuta::EmAndamento)->round_atual->toBeNull();

    $this->post(route('admin.lutas.encerrar', $luta), [
        'metodo_vitoria' => MetodoVitoria::KoTko->value,
        'vencedor_id' => $luta->participante_a_id,
    ])->assertSessionHasErrors('metodo_vitoria');

    $this->post(route('admin.lutas.encerrar', $luta), [
        'metodo_vitoria' => MetodoVitoria::Ippon->value,
        'vencedor_id' => $luta->participante_b_id,
    ])->assertSessionHasNoErrors();

    expect($luta->refresh())
        ->status->toBe(StatusLuta::Encerrada)
        ->metodo_vitoria->toBe(MetodoVitoria::Ippon);
});

test('a scheduled or running fight can be cancelled', function (StatusLuta $status) {
    $luta = Luta::factory()->create(['status' => $status, 'em_intervalo' => $status === StatusLuta::EmAndamento]);

    $this->post(route('admin.lutas.cancelar', $luta));

    expect($luta->refresh())->status->toBe(StatusLuta::Cancelada)->em_intervalo->toBeFalse();
})->with([StatusLuta::Agendada, StatusLuta::EmAndamento]);

test('the progress page offers only the actions valid at the moment', function () {
    $luta = Luta::factory()->create(['status' => StatusLuta::EmAndamento, 'round_atual' => 1, 'em_intervalo' => true, 'numero_rounds' => 3]);

    $this->get(route('admin.lutas.andamento', $luta))
        ->assertInertia(fn ($page) => $page
            ->component('admin/lutas/Andamento')
            ->where('acoes', [
                'iniciar' => false,
                'encerrarRound' => false,
                'iniciarProximoRound' => true,
                'encerrar' => true,
                'cancelar' => true,
            ]));
});

test('a side judge scores a round, and scoring it again corrects the value', function () {
    [$luta, $juiz] = lutaEmAndamentoComJuizLateral();

    $this->post(route('admin.lutas.placares.store', $luta), [
        'juiz_id' => $juiz->id, 'round' => 1, 'pontos_atleta_a' => 10, 'pontos_atleta_b' => 9,
    ])->assertSessionHasNoErrors();

    $this->post(route('admin.lutas.placares.store', $luta), [
        'juiz_id' => $juiz->id, 'round' => 1, 'pontos_atleta_a' => 10, 'pontos_atleta_b' => 8,
    ])->assertSessionHasNoErrors();

    expect(Placar::query()->sole()->pontos_atleta_b)->toBe(8);
});

test('only side judges assigned to the fight can score', function (Closure $juiz) {
    [$luta] = lutaEmAndamentoComJuizLateral();

    $this->post(route('admin.lutas.placares.store', $luta), [
        'juiz_id' => $juiz($luta), 'round' => 1, 'pontos_atleta_a' => 10, 'pontos_atleta_b' => 9,
    ])->assertSessionHasErrors('juiz_id');

    expect(Placar::query()->count())->toBe(0);
})->with([
    'árbitro da luta' => [function (Luta $luta) {
        $arbitro = Juiz::factory()->create();
        $luta->juizes()->attach($arbitro, ['funcao' => FuncaoJuiz::Arbitro]);

        return $arbitro->id;
    }],
    'juiz não escalado' => [fn () => Juiz::factory()->create()->id],
]);

test('a score must be for a round of the fight and within the ten point system', function (array $overrides, string $campo) {
    [$luta, $juiz] = lutaEmAndamentoComJuizLateral();

    $this->post(route('admin.lutas.placares.store', $luta), [
        'juiz_id' => $juiz->id, 'round' => 1, 'pontos_atleta_a' => 10, 'pontos_atleta_b' => 9, ...$overrides,
    ])->assertSessionHasErrors($campo);
})->with([
    'round além da luta' => [['round' => 4], 'round'],
    'pontos acima de dez' => [['pontos_atleta_a' => 11], 'pontos_atleta_a'],
    'pontos negativos' => [['pontos_atleta_b' => -1], 'pontos_atleta_b'],
]);

test('a fight that has not started cannot be scored', function () {
    [$luta, $juiz] = lutaEmAndamentoComJuizLateral();
    $luta->update(['status' => StatusLuta::Agendada]);

    $this->post(route('admin.lutas.placares.store', $luta), [
        'juiz_id' => $juiz->id, 'round' => 1, 'pontos_atleta_a' => 10, 'pontos_atleta_b' => 9,
    ])->assertSessionHasErrors('round');
});
