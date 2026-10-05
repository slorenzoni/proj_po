<?php

use App\Models\Categoria;
use App\Models\ConfiguracaoPontuacao;
use App\Models\PesoTrocaPalpite;
use Database\Seeders\ConfiguracaoPontuacaoSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('a category without its own scoring uses the general default', function () {
    $geral = ConfiguracaoPontuacao::factory()->create();
    ConfiguracaoPontuacao::factory()->paraCategoria(Categoria::factory()->create())->create();

    $categoria = Categoria::factory()->create();

    expect(ConfiguracaoPontuacao::paraCategoria($categoria)->is($geral))->toBeTrue();
});

test('a category with its own scoring overrides the general default', function () {
    $categoria = Categoria::factory()->create();
    ConfiguracaoPontuacao::factory()->create();
    $propria = ConfiguracaoPontuacao::factory()->paraCategoria($categoria)->create(['pontos_vencedor' => 12]);

    $vigente = ConfiguracaoPontuacao::paraCategoria($categoria);

    expect($vigente->is($propria))->toBeTrue()
        ->and($vigente->pontos_vencedor)->toBe(12);
});

test('a removed category scoring falls back to the general default', function () {
    $categoria = Categoria::factory()->create();
    $geral = ConfiguracaoPontuacao::factory()->create();
    ConfiguracaoPontuacao::factory()->paraCategoria($categoria)->create()->delete();

    expect(ConfiguracaoPontuacao::paraCategoria($categoria)->is($geral))->toBeTrue();
});

test('looking up scoring fails when not even the general default exists', function () {
    ConfiguracaoPontuacao::paraCategoria(Categoria::factory()->create());
})->throws(ModelNotFoundException::class);

test('a category without its own weights uses the general grid for that fight length', function () {
    PesoTrocaPalpite::factory()->create(['numero_rounds' => 3, 'round_da_troca' => 0, 'peso' => 100]);
    PesoTrocaPalpite::factory()->create(['numero_rounds' => 3, 'round_da_troca' => 1, 'peso' => 70]);
    PesoTrocaPalpite::factory()->create(['numero_rounds' => 5, 'round_da_troca' => 1, 'peso' => 80]);

    $grade = PesoTrocaPalpite::gradePara(Categoria::factory()->create(), 3);

    expect($grade->all())->toBe([0 => '100.00', 1 => '70.00']);
});

test('a category with its own weights uses only its own grid', function () {
    $categoria = Categoria::factory()->create();
    PesoTrocaPalpite::factory()->create(['numero_rounds' => 3, 'round_da_troca' => 0, 'peso' => 100]);
    PesoTrocaPalpite::factory()->create(['numero_rounds' => 3, 'round_da_troca' => 2, 'peso' => 40]);
    PesoTrocaPalpite::factory()->paraCategoria($categoria)->create(['numero_rounds' => 3, 'round_da_troca' => 1, 'peso' => 50]);

    expect(PesoTrocaPalpite::gradePara($categoria, 3)->all())->toBe([1 => '50.00'])
        ->and(PesoTrocaPalpite::gradePara($categoria, 5)->all())->toBe([]);
});

test('seeding creates the approved defaults once and keeps values changed by an admin', function () {
    $this->seed(ConfiguracaoPontuacaoSeeder::class);

    ConfiguracaoPontuacao::query()->sole()->update(['pontos_perfeito' => 30]);

    $this->seed(ConfiguracaoPontuacaoSeeder::class);

    $categoria = Categoria::factory()->create();

    expect(ConfiguracaoPontuacao::query()->sole()->pontos_perfeito)->toBe(30)
        ->and(PesoTrocaPalpite::query()->count())->toBe(8)
        ->and(PesoTrocaPalpite::gradePara($categoria, 3)->all())->toBe([0 => '100.00', 1 => '70.00', 2 => '40.00'])
        ->and(PesoTrocaPalpite::gradePara($categoria, 5)->all())
        ->toBe([0 => '100.00', 1 => '80.00', 2 => '60.00', 3 => '40.00', 4 => '20.00']);
});
