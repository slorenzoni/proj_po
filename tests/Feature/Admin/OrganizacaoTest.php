<?php

use App\Enums\NivelAcesso;
use App\Models\Evento;
use App\Models\Organizacao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(administrador(NivelAcesso::Cadastrador));
});

test('the list can be searched by name', function () {
    Organizacao::factory()->create(['nome' => 'Ultimate Fighting']);
    Organizacao::factory()->create(['nome' => 'Bellator']);

    $this->get(route('admin.organizacoes.index', ['busca' => 'ultimate']))
        ->assertInertia(fn ($page) => $page
            ->component('admin/organizacoes/Index')
            ->has('organizacoes.data', 1)
            ->where('organizacoes.data.0.nome', 'Ultimate Fighting'));
});

test('an organization can be created with a logo', function () {
    $this->post(route('admin.organizacoes.store'), [
        'nome' => 'UFC',
        'pais_origem' => 'Estados Unidos',
        'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.organizacoes.index'));

    $organizacao = Organizacao::query()->sole();

    expect($organizacao->nome)->toBe('UFC')
        ->and($organizacao->pais_origem)->toBe('Estados Unidos');

    Storage::disk('public')->assertExists($organizacao->logo_url);
});

test('a name already in use is rejected, but is free again after the other record is deleted', function () {
    $existente = Organizacao::factory()->create(['nome' => 'UFC']);

    $this->post(route('admin.organizacoes.store'), ['nome' => 'UFC'])->assertSessionHasErrors('nome');

    $existente->delete();

    $this->post(route('admin.organizacoes.store'), ['nome' => 'UFC'])->assertSessionHasNoErrors();

    expect(Organizacao::query()->count())->toBe(1);
});

test('an organization keeps its own name on update', function () {
    $organizacao = Organizacao::factory()->create(['nome' => 'UFC']);

    $this->put(route('admin.organizacoes.update', $organizacao), ['nome' => 'UFC', 'pais_origem' => 'EUA'])
        ->assertSessionHasNoErrors();

    expect($organizacao->refresh()->pais_origem)->toBe('EUA');
});

test('sending a new logo replaces the previous file', function () {
    $organizacao = Organizacao::factory()->create([
        'logo_url' => UploadedFile::fake()->image('antiga.png')->store('organizacoes', 'public'),
    ]);
    $logoAntiga = $organizacao->logo_url;

    $this->put(route('admin.organizacoes.update', $organizacao), [
        'nome' => $organizacao->nome,
        'logo' => UploadedFile::fake()->image('nova.png'),
    ])->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing($logoAntiga);
    Storage::disk('public')->assertExists($organizacao->refresh()->logo_url);
});

test('an organization with events cannot be deleted', function () {
    $organizacao = Evento::factory()->create()->organizacao;

    $this->delete(route('admin.organizacoes.destroy', $organizacao));

    expect($organizacao->refresh()->trashed())->toBeFalse();
});

test('an organization without events is soft deleted', function () {
    $organizacao = Organizacao::factory()->create();

    $this->delete(route('admin.organizacoes.destroy', $organizacao))
        ->assertRedirect(route('admin.organizacoes.index'));

    expect($organizacao->refresh()->trashed())->toBeTrue();
});
