<?php

use App\Enums\FuncaoJuiz;
use App\Enums\MetodoVitoria;
use App\Enums\StatusLuta;
use App\Models\Atleta;
use App\Models\Evento;
use App\Models\Juiz;
use App\Models\Luta;
use App\Models\LutaJuiz;
use App\Models\Organizacao;

test('a fight resolves each corner and the winner to the right athlete', function () {
    $atletaA = Atleta::factory()->create();
    $atletaB = Atleta::factory()->create();

    $luta = Luta::factory()->create([
        'participante_a_id' => $atletaA,
        'participante_b_id' => $atletaB,
        'vencedor_id' => $atletaB,
        'status' => StatusLuta::Encerrada,
        'metodo_vitoria' => MetodoVitoria::DecisaoUnanime,
    ]);

    $luta->refresh();

    expect($luta->participanteA->is($atletaA))->toBeTrue()
        ->and($luta->participanteB->is($atletaB))->toBeTrue()
        ->and($luta->vencedor->is($atletaB))->toBeTrue()
        ->and($luta->status)->toBe(StatusLuta::Encerrada)
        ->and($luta->metodo_vitoria)->toBe(MetodoVitoria::DecisaoUnanime);
});

test('the weight class created for a fight belongs to the fight category', function () {
    $luta = Luta::factory()->create();

    expect($luta->categoriaPeso->categoria_id)->toBe($luta->categoria_id);
});

test('an event lists its card from the main event down', function () {
    $evento = Evento::factory()->create();
    Luta::factory()->for($evento)->create(['ordem_na_card' => 2]);
    Luta::factory()->for($evento)->create(['ordem_na_card' => 1]);

    expect($evento->lutas->pluck('ordem_na_card')->all())->toBe([1, 2])
        ->and($evento->organizacao)->toBeInstanceOf(Organizacao::class)
        ->and($evento->organizacao->eventos->sole()->is($evento))->toBeTrue();
});

test('assigning a judge creates an audited pivot that carries the role', function () {
    $luta = Luta::factory()->create();
    $juiz = Juiz::factory()->create();

    $luta->juizes()->attach($juiz, ['funcao' => FuncaoJuiz::JuizLateral]);

    $vinculo = LutaJuiz::query()->sole();

    expect($vinculo->uuid)->toBeString()->not->toBeEmpty()
        ->and($vinculo->funcao)->toBe(FuncaoJuiz::JuizLateral)
        ->and($juiz->lutas->sole()->is($luta))->toBeTrue();
});

test('a soft deleted judge assignment no longer appears on the fight', function () {
    $luta = Luta::factory()->create();
    $luta->juizes()->attach(Juiz::factory()->create(), ['funcao' => FuncaoJuiz::Arbitro]);

    LutaJuiz::query()->sole()->delete();

    expect($luta->juizes()->count())->toBe(0)
        ->and(LutaJuiz::withTrashed()->count())->toBe(1);
});
