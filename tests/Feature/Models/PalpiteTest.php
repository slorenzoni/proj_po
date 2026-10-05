<?php

use App\Enums\MetodoPalpite;
use App\Models\Luta;
use App\Models\Mensagem;
use App\Models\Palpite;
use App\Models\PalpiteHistorico;
use App\Models\Placar;
use App\Models\PlacarFan;
use App\Models\User;

test('a new pick starts as a pre-fight pick at full weight and without points', function () {
    $luta = Luta::factory()->create();

    $palpite = Palpite::factory()->for($luta)->create()->refresh();

    expect($palpite->vencedorEscolhido->is($luta->participanteA))->toBeTrue()
        ->and($palpite->round_da_troca)->toBe(0)
        ->and($palpite->peso_aplicado)->toBe('100.00')
        ->and($palpite->pontos_obtidos)->toBeNull()
        ->and($palpite->metodo_escolhido)->toBeNull();
});

test('a pick keeps its method and lists its changes from oldest to newest', function () {
    $palpite = Palpite::factory()->create([
        'metodo_escolhido' => MetodoPalpite::Finalizacao,
        'round_escolhido' => 2,
    ]);
    $segunda = PalpiteHistorico::factory()->for($palpite)->create(['round_da_troca' => 1, 'created_at' => now()]);
    $primeira = PalpiteHistorico::factory()->for($palpite)->create(['round_da_troca' => 0, 'created_at' => now()->subHour()]);

    expect($palpite->refresh()->metodo_escolhido)->toBe(MetodoPalpite::Finalizacao)
        ->and($palpite->historicos->modelKeys())->toBe([$primeira->id, $segunda->id])
        ->and($palpite->user->palpites->sole()->is($palpite))->toBeTrue();
});

test('a fight gathers its picks, official scores, fan scores and comments', function () {
    $luta = Luta::factory()->create();
    $user = User::factory()->create();

    Palpite::factory()->for($luta)->for($user)->create();
    Placar::factory()->for($luta)->create();
    PlacarFan::factory()->for($luta)->for($user)->create();
    Mensagem::factory()->for($luta)->for($user)->create();
    Palpite::factory()->create();

    expect($luta->palpites)->toHaveCount(1)
        ->and($luta->placares)->toHaveCount(1)
        ->and($luta->placaresFans->sole()->user->is($user))->toBeTrue()
        ->and($luta->mensagens->sole()->user->is($user))->toBeTrue();
});
