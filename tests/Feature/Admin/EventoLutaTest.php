<?php

use App\Enums\FuncaoJuiz;
use App\Enums\NivelAcesso;
use App\Enums\StatusEvento;
use App\Enums\StatusLuta;
use App\Models\Atleta;
use App\Models\Categoria;
use App\Models\CategoriaPeso;
use App\Models\Evento;
use App\Models\Juiz;
use App\Models\Luta;
use App\Models\LutaJuiz;
use App\Models\Organizacao;
use App\Models\Palpite;
use App\Models\Placar;

beforeEach(function () {
    $this->actingAs(administrador(NivelAcesso::Cadastrador));
});

/**
 * Formulário válido de luta para uma modalidade com rounds.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function dadosDeLuta(array $overrides = []): array
{
    $peso = CategoriaPeso::factory()->create();

    return [
        'categoria_id' => $peso->categoria_id,
        'categoria_peso_id' => $peso->id,
        'participante_a_id' => Atleta::factory()->create()->id,
        'participante_b_id' => Atleta::factory()->create()->id,
        'ordem_na_card' => 1,
        'numero_rounds' => 3,
        ...$overrides,
    ];
}

test('creating an event opens its fight card', function () {
    $organizacao = Organizacao::factory()->create();

    $this->post(route('admin.eventos.store'), [
        'organizacao_id' => $organizacao->id,
        'nome' => 'UFC 300',
        'data' => '2026-12-12T22:00',
        'status' => StatusEvento::Agendado->value,
    ])->assertSessionHasNoErrors();

    $evento = Evento::query()->sole();

    expect($evento->data->format('Y-m-d H:i'))->toBe('2026-12-12 22:00');

    $this->get(route('admin.eventos.show', $evento))
        ->assertInertia(fn ($page) => $page->component('admin/eventos/Show')->where('evento.nome', 'UFC 300'));
});

test('an event needs an existing organization and a valid status', function () {
    $this->post(route('admin.eventos.store'), [
        'organizacao_id' => 999,
        'nome' => 'UFC 300',
        'data' => '2026-12-12T22:00',
        'status' => 'inexistente',
    ])->assertSessionHasErrors(['organizacao_id', 'status']);
});

test('an event with fights cannot be deleted', function () {
    $evento = Luta::factory()->create()->evento;

    $this->delete(route('admin.eventos.destroy', $evento));

    expect($evento->refresh()->trashed())->toBeFalse();
});

test('a fight is added to the card of its event', function () {
    $evento = Evento::factory()->create();

    $this->post(route('admin.eventos.lutas.store', $evento), dadosDeLuta(['numero_rounds' => 5]))
        ->assertSessionHasNoErrors();

    $luta = $evento->lutas()->sole();

    expect($luta->numero_rounds)->toBe(5)
        ->and($luta->status)->toBe(StatusLuta::Agendada);

    $this->get(route('admin.lutas.edit', $luta))
        ->assertInertia(fn ($page) => $page->component('admin/lutas/Form')->where('luta.editavel', true));
});

test('the weight class must belong to the chosen category', function () {
    $outraCategoria = CategoriaPeso::factory()->create();

    $this->post(route('admin.eventos.lutas.store', Evento::factory()->create()), dadosDeLuta([
        'categoria_peso_id' => $outraCategoria->id,
    ]))->assertSessionHasErrors('categoria_peso_id');
});

test('an athlete cannot fight against themselves', function () {
    $atleta = Atleta::factory()->create();

    $this->post(route('admin.eventos.lutas.store', Evento::factory()->create()), dadosDeLuta([
        'participante_a_id' => $atleta->id,
        'participante_b_id' => $atleta->id,
    ]))->assertSessionHasErrors('participante_b_id');
});

test('a category with rounds requires a fight length of three or five rounds', function (mixed $numeroRounds) {
    $this->post(route('admin.eventos.lutas.store', Evento::factory()->create()), dadosDeLuta([
        'numero_rounds' => $numeroRounds,
    ]))->assertSessionHasErrors('numero_rounds');
})->with([
    'ausente' => null,
    'quatro rounds' => 4,
]);

test('a category without rounds stores no fight length', function () {
    $peso = CategoriaPeso::factory()->for(Categoria::factory()->state(['usa_rounds' => false]))->create();
    $evento = Evento::factory()->create();

    $this->post(route('admin.eventos.lutas.store', $evento), dadosDeLuta([
        'categoria_id' => $peso->categoria_id,
        'categoria_peso_id' => $peso->id,
        'numero_rounds' => 3,
    ]))->assertSessionHasNoErrors();

    expect($evento->lutas()->sole()->numero_rounds)->toBeNull();
});

test('a card position is unique within an event but can repeat in another', function () {
    $luta = Luta::factory()->create(['ordem_na_card' => 1]);

    $this->post(route('admin.eventos.lutas.store', $luta->evento), dadosDeLuta())
        ->assertSessionHasErrors('ordem_na_card');

    $this->post(route('admin.eventos.lutas.store', Evento::factory()->create()), dadosDeLuta())
        ->assertSessionHasNoErrors();
});

test('a fight keeps its own card position on update', function () {
    $luta = Luta::factory()->create(['ordem_na_card' => 1]);

    $this->put(route('admin.lutas.update', $luta), dadosDeLuta(['numero_rounds' => 5]))
        ->assertSessionHasNoErrors();

    expect($luta->refresh()->numero_rounds)->toBe(5);
});

test('a fight that has started can no longer be edited or deleted here', function () {
    $luta = Luta::factory()->create(['status' => StatusLuta::EmAndamento, 'numero_rounds' => 3]);

    $this->put(route('admin.lutas.update', $luta), dadosDeLuta(['numero_rounds' => 5]));
    $this->delete(route('admin.lutas.destroy', $luta));

    expect($luta->refresh()->numero_rounds)->toBe(3)
        ->and($luta->trashed())->toBeFalse();
});

test('a scheduled fight with picks cannot be deleted', function () {
    $luta = Palpite::factory()->create()->luta;

    $this->delete(route('admin.lutas.destroy', $luta));

    expect($luta->refresh()->trashed())->toBeFalse();
});

test('a scheduled fight without picks is removed from the card', function () {
    $luta = Luta::factory()->create();

    $this->delete(route('admin.lutas.destroy', $luta))
        ->assertRedirect(route('admin.eventos.show', $luta->evento));

    expect($luta->refresh()->trashed())->toBeTrue();
});

test('a judge is assigned to a fight once, with a role', function () {
    $luta = Luta::factory()->create();
    $juiz = Juiz::factory()->create();

    $this->post(route('admin.lutas.juizes.store', $luta), ['juiz_id' => $juiz->id, 'funcao' => FuncaoJuiz::JuizLateral->value])
        ->assertSessionHasNoErrors();
    $this->post(route('admin.lutas.juizes.store', $luta), ['juiz_id' => $juiz->id, 'funcao' => FuncaoJuiz::Arbitro->value])
        ->assertSessionHasErrors('juiz_id');

    expect(LutaJuiz::query()->sole()->funcao)->toBe(FuncaoJuiz::JuizLateral);
});

test('a judge who already scored the fight cannot be removed from it', function () {
    $luta = Luta::factory()->create();
    $juiz = Juiz::factory()->create();
    $luta->juizes()->attach($juiz, ['funcao' => FuncaoJuiz::JuizLateral]);
    Placar::factory()->for($luta)->for($juiz)->create();

    $this->delete(route('admin.lutas.juizes.destroy', LutaJuiz::query()->sole()));

    expect($luta->juizes()->count())->toBe(1);
});

test('a judge without scores can be removed from the fight', function () {
    $luta = Luta::factory()->create();
    $luta->juizes()->attach(Juiz::factory()->create(), ['funcao' => FuncaoJuiz::Arbitro]);

    $this->delete(route('admin.lutas.juizes.destroy', LutaJuiz::query()->sole()));

    expect($luta->juizes()->count())->toBe(0);
});
