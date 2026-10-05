<?php

use App\Models\Atleta;
use App\Models\AtletaEstilo;
use App\Models\AtletaFoto;
use App\Models\EstiloDeLuta;
use App\Models\Treinador;

test('record totals are derived from the breakdown by method', function () {
    $atleta = Atleta::factory()->create([
        'vitorias_ko' => 5,
        'vitorias_submissao' => 3,
        'vitorias_decisao' => 2,
        'derrotas_ko' => 1,
        'derrotas_decisao' => 1,
    ]);

    expect($atleta->vitorias)->toBe(10)
        ->and($atleta->derrotas)->toBe(2)
        ->and($atleta->invicto)->toBeFalse();
});

test('an athlete without defeats is undefeated until a defeat is recorded', function () {
    $atleta = Atleta::factory()->create(['vitorias_ko' => 4]);

    expect($atleta->invicto)->toBeTrue();

    $atleta->update(['derrotas_submissao' => 1]);

    expect($atleta->refresh()->invicto)->toBeFalse()
        ->and($atleta->derrotas)->toBe(1);
});

test('photos are returned in display order', function () {
    $atleta = Atleta::factory()->create();
    AtletaFoto::factory()->for($atleta)->create(['ordem' => 2]);
    AtletaFoto::factory()->for($atleta)->create(['ordem' => 1]);

    expect($atleta->fotos->pluck('ordem')->all())->toBe([1, 2]);
});

test('linking a style creates an audited pivot that carries the coach', function () {
    $atleta = Atleta::factory()->create();
    $estilo = EstiloDeLuta::factory()->create();
    $treinador = Treinador::factory()->create();

    $atleta->estilos()->attach($estilo, ['treinador_id' => $treinador->id]);

    $vinculo = AtletaEstilo::query()->sole();

    expect($vinculo->uuid)->toBeString()->not->toBeEmpty()
        ->and($vinculo->treinador->is($treinador))->toBeTrue()
        ->and($estilo->atletas->sole()->is($atleta))->toBeTrue();
});

test('a soft deleted style link no longer appears on the athlete', function () {
    $atleta = Atleta::factory()->create();
    $estilo = EstiloDeLuta::factory()->create();
    $atleta->estilos()->attach($estilo);

    AtletaEstilo::query()->sole()->delete();

    expect($atleta->estilos()->count())->toBe(0)
        ->and(AtletaEstilo::withTrashed()->count())->toBe(1);
});
