<?php

use App\Models\Categoria;
use App\Models\ConfiguracaoPontuacao;
use App\Models\PesoTrocaPalpite;

beforeEach(function () {
    $this->actingAs(administrador());
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function dadosDePontuacao(array $overrides = []): array
{
    return [
        'categoria' => '',
        'pontos_vencedor' => 10,
        'pontos_vencedor_metodo' => 15,
        'pontos_vencedor_round' => 15,
        'pontos_perfeito' => 22,
        'prazo_placar_fans_minutos' => 5,
        ...$overrides,
    ];
}

test('saving the general scoring keeps a single general row', function () {
    $this->put(route('admin.configuracoes.pontuacao.update'), dadosDePontuacao())->assertSessionHasNoErrors();
    $this->put(route('admin.configuracoes.pontuacao.update'), dadosDePontuacao(['pontos_perfeito' => 30]))
        ->assertSessionHasNoErrors();

    expect(ConfiguracaoPontuacao::query()->sole())
        ->categoria_id->toBeNull()
        ->pontos_perfeito->toBe(30);
});

test('saving for a category creates its own scoring without touching the general one', function () {
    $geral = ConfiguracaoPontuacao::factory()->create();
    $categoria = Categoria::factory()->create();

    $this->put(route('admin.configuracoes.pontuacao.update'), dadosDePontuacao([
        'categoria' => $categoria->uuid,
        'pontos_vencedor' => 12,
    ]))->assertSessionHasNoErrors();

    expect(ConfiguracaoPontuacao::paraCategoria($categoria)->pontos_vencedor)->toBe(12)
        ->and($geral->refresh()->pontos_vencedor)->toBe(10);
});

test('scoring values must be whole non negative numbers and the deadline at least one minute', function (array $overrides, string $campo) {
    $this->put(route('admin.configuracoes.pontuacao.update'), dadosDePontuacao($overrides))
        ->assertSessionHasErrors($campo);

    expect(ConfiguracaoPontuacao::query()->count())->toBe(0);
})->with([
    'pontos negativos' => [['pontos_vencedor' => -1], 'pontos_vencedor'],
    'pontos fracionados' => [['pontos_perfeito' => 22.5], 'pontos_perfeito'],
    'prazo zero' => [['prazo_placar_fans_minutos' => 0], 'prazo_placar_fans_minutos'],
    'categoria inexistente' => [['categoria' => '00000000-0000-0000-0000-000000000000'], 'categoria'],
]);

test('the weight grid of a fight length is saved for its trade moments only', function () {
    $this->put(route('admin.configuracoes.pesos.update'), [
        'categoria' => '',
        'numero_rounds' => 3,
        'pesos' => [0 => 100, 1 => 70, 2 => 40, 3 => 10],
    ])->assertSessionHasNoErrors();

    expect(PesoTrocaPalpite::query()->orderBy('round_da_troca')->pluck('peso', 'round_da_troca')->all())
        ->toBe([0 => '100.00', 1 => '70.00', 2 => '40.00']);
});

test('saving a grid again updates it instead of duplicating', function () {
    $dados = ['categoria' => '', 'numero_rounds' => 3, 'pesos' => [0 => 100, 1 => 70, 2 => 40]];

    $this->put(route('admin.configuracoes.pesos.update'), $dados);
    $this->put(route('admin.configuracoes.pesos.update'), [...$dados, 'pesos' => [0 => 100, 1 => 60, 2 => 30]]);

    expect(PesoTrocaPalpite::query()->count())->toBe(3)
        ->and(PesoTrocaPalpite::gradePara(Categoria::factory()->create(), 3)->all())
        ->toBe([0 => '100.00', 1 => '60.00', 2 => '30.00']);
});

test('a weight grid must be complete, within zero and one hundred, for a supported fight length', function (array $dados, string $campo) {
    $this->put(route('admin.configuracoes.pesos.update'), ['categoria' => '', ...$dados])
        ->assertSessionHasErrors($campo);

    expect(PesoTrocaPalpite::query()->count())->toBe(0);
})->with([
    'grade incompleta' => [['numero_rounds' => 3, 'pesos' => [0 => 100, 1 => 70]], 'pesos.2'],
    'peso acima de cem' => [['numero_rounds' => 3, 'pesos' => [0 => 120, 1 => 70, 2 => 40]], 'pesos.0'],
    'duração sem grade' => [['numero_rounds' => 4, 'pesos' => [0 => 100, 1 => 70, 2 => 40, 3 => 20]], 'numero_rounds'],
]);

test('going back to the default removes only the settings of that category', function () {
    $categoria = Categoria::factory()->create();
    $outra = Categoria::factory()->create();
    ConfiguracaoPontuacao::factory()->create();
    ConfiguracaoPontuacao::factory()->paraCategoria($categoria)->create();
    ConfiguracaoPontuacao::factory()->paraCategoria($outra)->create();
    PesoTrocaPalpite::factory()->paraCategoria($categoria)->create();
    PesoTrocaPalpite::factory()->create();

    $this->delete(route('admin.configuracoes.categorias.destroy', $categoria));

    expect(ConfiguracaoPontuacao::paraCategoria($categoria)->categoria_id)->toBeNull()
        ->and(ConfiguracaoPontuacao::paraCategoria($outra)->categoria_id)->toBe($outra->id)
        ->and(PesoTrocaPalpite::query()->count())->toBe(1);
});

test('the page shows the general values for a category without its own settings', function () {
    ConfiguracaoPontuacao::factory()->create(['pontos_vencedor' => 10]);
    PesoTrocaPalpite::factory()->create(['numero_rounds' => 3, 'round_da_troca' => 1, 'peso' => 70]);
    $categoria = Categoria::factory()->create(['nome' => 'Boxe']);

    $this->get(route('admin.configuracoes.pontuacao.edit', ['categoria' => $categoria->uuid]))
        ->assertInertia(fn ($page) => $page
            ->component('admin/configuracoes/Pontuacao')
            ->where('escopo.nome', 'Boxe')
            ->where('pontuacaoPropria', false)
            ->where('pontuacao.pontos_vencedor', 10)
            ->where('grades.0.propria', false)
            ->where('grades.0.pesos.1.peso', '70.00'));
});

test('the page marks a category that has its own settings', function () {
    $categoria = Categoria::factory()->create();
    ConfiguracaoPontuacao::factory()->create();
    ConfiguracaoPontuacao::factory()->paraCategoria($categoria)->create(['pontos_vencedor' => 12]);

    $this->get(route('admin.configuracoes.pontuacao.edit', ['categoria' => $categoria->uuid]))
        ->assertInertia(fn ($page) => $page
            ->where('pontuacaoPropria', true)
            ->where('pontuacao.pontos_vencedor', 12));
});
