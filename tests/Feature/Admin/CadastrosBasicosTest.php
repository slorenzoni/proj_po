<?php

use App\Enums\NivelAcesso;
use App\Models\Atleta;
use App\Models\Categoria;
use App\Models\CategoriaPeso;
use App\Models\EstiloDeLuta;
use App\Models\Juiz;
use App\Models\Luta;
use App\Models\Treinador;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(administrador(NivelAcesso::Cadastrador));
});

test('creating a category leads to its page to register weight classes', function () {
    $this->post(route('admin.categorias.store'), ['nome' => 'Judô', 'usa_rounds' => '0'])
        ->assertSessionHasNoErrors();

    $categoria = Categoria::query()->sole();

    expect($categoria->usa_rounds)->toBeFalse();

    $this->get(route('admin.categorias.edit', $categoria))
        ->assertInertia(fn ($page) => $page->component('admin/categorias/Form')->where('categoria.nome', 'Judô'));
});

test('a weight class name is unique only within its own category', function () {
    $mma = Categoria::factory()->create();
    $boxe = Categoria::factory()->create();
    CategoriaPeso::factory()->for($mma)->create(['nome' => 'Peso Leve']);

    $this->post(route('admin.categorias.pesos.store', $mma), ['nome' => 'Peso Leve'])
        ->assertSessionHasErrors('nome');

    $this->post(route('admin.categorias.pesos.store', $boxe), ['nome' => 'Peso Leve'])
        ->assertSessionHasNoErrors();

    expect($boxe->categoriasPeso()->count())->toBe(1);
});

test('a weight class cannot have a maximum below its minimum', function () {
    $categoria = Categoria::factory()->create();

    $this->post(route('admin.categorias.pesos.store', $categoria), [
        'nome' => 'Peso Leve',
        'peso_minimo_kg' => 70,
        'peso_maximo_kg' => 66,
    ])->assertSessionHasErrors('peso_maximo_kg');
});

test('a weight class can be renamed without colliding with itself', function () {
    $peso = CategoriaPeso::factory()->create(['nome' => 'Peso Leve']);

    $this->put(route('admin.pesos.update', $peso), ['nome' => 'Peso Leve', 'peso_maximo_kg' => 70.3])
        ->assertSessionHasNoErrors();

    expect($peso->refresh()->peso_maximo_kg)->toBe('70.30');
});

test('a category in use cannot be deleted', function () {
    $categoria = CategoriaPeso::factory()->create()->categoria;

    $this->delete(route('admin.categorias.destroy', $categoria));

    expect($categoria->refresh()->trashed())->toBeFalse();
});

test('a weight class used by a fight cannot be deleted', function () {
    $peso = Luta::factory()->create()->categoriaPeso;

    $this->delete(route('admin.pesos.destroy', $peso));

    expect($peso->refresh()->trashed())->toBeFalse();
});

test('a fighting style name must be unique', function () {
    EstiloDeLuta::factory()->create(['nome' => 'Jiu-Jitsu']);

    $this->post(route('admin.estilos.store'), ['nome' => 'Jiu-Jitsu'])->assertSessionHasErrors('nome');
    $this->post(route('admin.estilos.store'), ['nome' => 'Muay Thai'])->assertRedirect(route('admin.estilos.index'));

    expect(EstiloDeLuta::query()->count())->toBe(2);
});

test('a fighting style linked to an athlete cannot be deleted', function () {
    $estilo = EstiloDeLuta::factory()->create();
    Atleta::factory()->create()->estilos()->attach($estilo);

    $this->delete(route('admin.estilos.destroy', $estilo));

    expect($estilo->refresh()->trashed())->toBeFalse();
});

test('a judge can be created, updated and deleted', function () {
    $this->post(route('admin.juizes.store'), ['nome' => 'Herb Dean', 'pais' => 'EUA'])
        ->assertRedirect(route('admin.juizes.index'));

    $juiz = Juiz::query()->sole();

    $this->put(route('admin.juizes.update', $juiz), ['nome' => 'Herb Dean', 'pais' => '', 'certificado_por' => 'NSAC'])
        ->assertSessionHasNoErrors();

    expect($juiz->refresh()->certificado_por)->toBe('NSAC')
        ->and($juiz->pais)->toBeNull();

    $this->delete(route('admin.juizes.destroy', $juiz));

    expect($juiz->refresh()->trashed())->toBeTrue();
});

test('a coach can be linked to a user account by email', function () {
    $conta = User::factory()->create();

    $this->post(route('admin.treinadores.store'), ['nome' => 'Dedé Pederneiras', 'email_usuario' => $conta->email])
        ->assertSessionHasNoErrors();

    expect(Treinador::query()->sole()->user->is($conta))->toBeTrue();
});

test('a coach cannot be linked to an unknown account or to one already linked', function () {
    $conta = User::factory()->create();
    Treinador::factory()->create(['user_id' => $conta->id]);

    $this->post(route('admin.treinadores.store'), ['nome' => 'Novo', 'email_usuario' => 'ninguem@example.com'])
        ->assertSessionHasErrors('email_usuario');

    $this->post(route('admin.treinadores.store'), ['nome' => 'Novo', 'email_usuario' => $conta->email])
        ->assertSessionHasErrors('email_usuario');

    expect(Treinador::query()->count())->toBe(1);
});

test('clearing the email unlinks the coach from the account', function () {
    $treinador = Treinador::factory()->create(['user_id' => User::factory()->create()->id]);

    $this->put(route('admin.treinadores.update', $treinador), ['nome' => $treinador->nome, 'email_usuario' => ''])
        ->assertSessionHasNoErrors();

    expect($treinador->refresh()->user_id)->toBeNull();
});
