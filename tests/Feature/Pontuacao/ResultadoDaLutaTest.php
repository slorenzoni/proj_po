<?php

use App\Enums\MetodoVitoria;
use App\Enums\NivelAcesso;
use App\Enums\StatusLuta;
use App\Models\Atleta;
use App\Models\CategoriaPeso;
use App\Models\Luta;
use App\Models\Palpite;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // A pontuação em si é coberta em PontuacaoDePalpitesTest.
    Queue::fake();
    $this->actingAs(administrador(NivelAcesso::Cadastrador));
});

/**
 * Encerra pelo painel uma luta em andamento, com o participante A como vencedor
 * quando o método tem vencedor.
 */
function encerrarLuta(Luta $luta, MetodoVitoria $metodo): void
{
    test()->post(route('admin.lutas.encerrar', $luta), [
        'metodo_vitoria' => $metodo->value,
        'vencedor_id' => $metodo->temVencedor() ? $luta->participante_a_id : null,
    ])->assertSessionHasNoErrors();
}

function lutaEmAndamento(?int $numeroRounds = 3): Luta
{
    return Luta::factory()->create([
        'status' => StatusLuta::EmAndamento,
        'numero_rounds' => $numeroRounds,
        'round_atual' => $numeroRounds === null ? null : 1,
    ]);
}

test('finishing a fight adds the result to the record of both athletes', function (MetodoVitoria $metodo, string $tipo) {
    $luta = lutaEmAndamento();

    encerrarLuta($luta, $metodo);

    $vencedor = $luta->participanteA->refresh();
    $perdedor = $luta->participanteB->refresh();

    expect($vencedor->getAttribute("vitorias_{$tipo}"))->toBe(1)
        ->and($vencedor->vitorias)->toBe(1)
        ->and($vencedor->invicto)->toBeTrue()
        ->and($perdedor->getAttribute("derrotas_{$tipo}"))->toBe(1)
        ->and($perdedor->derrotas)->toBe(1)
        ->and($perdedor->invicto)->toBeFalse();
})->with([
    'KO/TKO' => [MetodoVitoria::KoTko, 'ko'],
    'submissão' => [MetodoVitoria::Submissao, 'submissao'],
    'decisão dividida' => [MetodoVitoria::DecisaoDividida, 'decisao'],
    'desqualificação' => [MetodoVitoria::Desqualificacao, 'decisao'],
]);

test('a result of a fight without rounds counts as a decision in the record', function () {
    $luta = lutaEmAndamento(null);

    encerrarLuta($luta, MetodoVitoria::Ippon);

    expect($luta->participanteA->refresh()->vitorias_decisao)->toBe(1)
        ->and($luta->participanteB->refresh()->derrotas_decisao)->toBe(1);
});

test('a draw adds a draw to both athletes and keeps them undefeated', function () {
    $luta = lutaEmAndamento();

    encerrarLuta($luta, MetodoVitoria::Empate);

    foreach ([$luta->participanteA->refresh(), $luta->participanteB->refresh()] as $atleta) {
        expect($atleta->empates)->toBe(1)
            ->and($atleta->vitorias)->toBe(0)
            ->and($atleta->derrotas)->toBe(0)
            ->and($atleta->invicto)->toBeTrue();
    }
});

test('a no contest leaves the records untouched', function () {
    $luta = lutaEmAndamento();

    encerrarLuta($luta, MetodoVitoria::SemResultado);

    foreach ([$luta->participanteA->refresh(), $luta->participanteB->refresh()] as $atleta) {
        expect([$atleta->vitorias, $atleta->derrotas, $atleta->empates])->toBe([0, 0, 0]);
    }
});

test('a result adds to the record the athlete already had', function () {
    $luta = lutaEmAndamento();
    $luta->participanteA->update(['vitorias_ko' => 9, 'derrotas_decisao' => 2]);

    encerrarLuta($luta, MetodoVitoria::KoTko);

    expect($luta->participanteA->refresh())
        ->vitorias_ko->toBe(10)
        ->vitorias->toBe(10)
        ->derrotas->toBe(2);
});

test('cancelling a fight discards its picks but keeps them for the record', function () {
    $luta = lutaEmAndamento();
    Palpite::factory()->for($luta)->count(2)->create();
    $deOutraLuta = Palpite::factory()->create();

    $this->post(route('admin.lutas.cancelar', $luta));

    expect($luta->palpites()->count())->toBe(0)
        ->and(Palpite::withTrashed()->whereBelongsTo($luta)->count())->toBe(2)
        ->and($deOutraLuta->refresh()->trashed())->toBeFalse();
});

/**
 * Formulário de edição que mantém a luta como está, salvo o que for sobrescrito.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function edicaoDaLuta(Luta $luta, array $overrides = []): array
{
    return [
        ...$luta->only([
            'categoria_id', 'categoria_peso_id', 'participante_a_id', 'participante_b_id', 'ordem_na_card', 'numero_rounds',
        ]),
        ...$overrides,
    ];
}

test('replacing an athlete of a scheduled fight discards its picks', function () {
    $luta = Luta::factory()->create();
    Palpite::factory()->for($luta)->create();

    $this->put(route('admin.lutas.update', $luta), edicaoDaLuta($luta, [
        'participante_b_id' => Atleta::factory()->create()->id,
    ]))->assertSessionHasNoErrors();

    expect($luta->palpites()->count())->toBe(0)
        ->and(Palpite::withTrashed()->whereBelongsTo($luta)->count())->toBe(1);
});

test('editing a fight without replacing an athlete keeps its picks', function (Closure $overrides) {
    $luta = Luta::factory()->create();
    Palpite::factory()->for($luta)->create();

    $this->put(route('admin.lutas.update', $luta), edicaoDaLuta($luta, $overrides($luta)))
        ->assertSessionHasNoErrors();

    expect($luta->palpites()->count())->toBe(1);
})->with([
    'mudar a duração' => [fn (Luta $luta) => ['numero_rounds' => 5]],
    'mudar a categoria de peso' => [fn (Luta $luta) => [
        'categoria_peso_id' => CategoriaPeso::factory()->for($luta->categoria)->create()->id,
    ]],
    'inverter os cantos' => [fn (Luta $luta) => [
        'participante_a_id' => $luta->participante_b_id,
        'participante_b_id' => $luta->participante_a_id,
    ]],
]);
