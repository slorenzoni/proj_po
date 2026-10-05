<?php

use App\Enums\NivelAcesso;
use App\Models\Atleta;
use App\Models\AtletaEstilo;
use App\Models\AtletaFoto;
use App\Models\EstiloDeLuta;
use App\Models\Luta;
use App\Models\Treinador;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(administrador(NivelAcesso::Cadastrador));
});

/**
 * Campos obrigatórios do formulário de atleta, com o cartel zerado.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function dadosDeAtleta(array $overrides = []): array
{
    return [
        'nome' => 'José Aldo',
        'tipo' => 'lutador',
        'vitorias_ko' => 0,
        'vitorias_submissao' => 0,
        'vitorias_decisao' => 0,
        'empates' => 0,
        'derrotas_ko' => 0,
        'derrotas_submissao' => 0,
        'derrotas_decisao' => 0,
        ...$overrides,
    ];
}

test('creating an athlete derives the record totals and ignores totals sent by the form', function () {
    $this->post(route('admin.atletas.store'), dadosDeAtleta([
        'vitorias_ko' => 17,
        'vitorias_decisao' => 14,
        'derrotas_ko' => 4,
        'vitorias' => 99,
        'invicto' => true,
    ]))->assertSessionHasNoErrors();

    $atleta = Atleta::query()->sole();

    expect($atleta->vitorias)->toBe(31)
        ->and($atleta->derrotas)->toBe(4)
        ->and($atleta->invicto)->toBeFalse();

    $this->get(route('admin.atletas.edit', $atleta))
        ->assertInertia(fn ($page) => $page->component('admin/atletas/Form')->where('atleta.vitorias', 31));
});

test('an athlete needs every record breakdown field', function () {
    $this->post(route('admin.atletas.store'), ['nome' => 'José Aldo', 'tipo' => 'lutador'])
        ->assertSessionHasErrors(['vitorias_ko', 'derrotas_decisao', 'empates']);
});

test('the first photo becomes the main one and takes the first position', function () {
    $atleta = Atleta::factory()->create();

    $this->post(route('admin.atletas.fotos.store', $atleta), ['foto' => UploadedFile::fake()->image('a.jpg')])
        ->assertSessionHasNoErrors();
    $this->post(route('admin.atletas.fotos.store', $atleta), ['foto' => UploadedFile::fake()->image('b.jpg')])
        ->assertSessionHasNoErrors();

    [$primeira, $segunda] = $atleta->fotos()->get()->all();

    expect($primeira->ordem)->toBe(1)
        ->and($primeira->principal)->toBeTrue()
        ->and($segunda->ordem)->toBe(2)
        ->and($segunda->principal)->toBeFalse();

    Storage::disk('public')->assertExists($primeira->foto_url);
});

test('an athlete cannot have more photos than the limit', function () {
    $atleta = Atleta::factory()->create();

    foreach (range(1, AtletaFoto::LIMITE_POR_ATLETA) as $ordem) {
        AtletaFoto::factory()->for($atleta)->create(['ordem' => $ordem]);
    }

    $this->post(route('admin.atletas.fotos.store', $atleta), ['foto' => UploadedFile::fake()->image('extra.jpg')])
        ->assertSessionHasErrors('foto');

    expect($atleta->fotos()->count())->toBe(AtletaFoto::LIMITE_POR_ATLETA);
});

test('a new photo fills the position freed by a deleted one', function () {
    $atleta = Atleta::factory()->create();
    AtletaFoto::factory()->for($atleta)->create(['ordem' => 1, 'principal' => true]);
    $segunda = AtletaFoto::factory()->for($atleta)->create(['ordem' => 2]);
    AtletaFoto::factory()->for($atleta)->create(['ordem' => 3]);

    $this->delete(route('admin.fotos.destroy', $segunda));
    $this->post(route('admin.atletas.fotos.store', $atleta), ['foto' => UploadedFile::fake()->image('nova.jpg')])
        ->assertSessionHasNoErrors();

    expect($atleta->fotos()->pluck('ordem')->all())->toBe([1, 2, 3]);
});

test('making a photo the main one demotes the previous main photo', function () {
    $atleta = Atleta::factory()->create();
    $antiga = AtletaFoto::factory()->for($atleta)->create(['ordem' => 1, 'principal' => true]);
    $nova = AtletaFoto::factory()->for($atleta)->create(['ordem' => 2]);

    $this->put(route('admin.fotos.principal', $nova));

    expect($nova->refresh()->principal)->toBeTrue()
        ->and($antiga->refresh()->principal)->toBeFalse();
});

test('deleting the main photo promotes the next one', function () {
    $atleta = Atleta::factory()->create();
    $principal = AtletaFoto::factory()->for($atleta)->create(['ordem' => 1, 'principal' => true]);
    $proxima = AtletaFoto::factory()->for($atleta)->create(['ordem' => 2]);

    $this->delete(route('admin.fotos.destroy', $principal));

    expect($principal->refresh()->trashed())->toBeTrue()
        ->and($proxima->refresh()->principal)->toBeTrue();
});

test('a style is linked with its coach and cannot be linked twice', function () {
    $atleta = Atleta::factory()->create();
    $estilo = EstiloDeLuta::factory()->create();
    $treinador = Treinador::factory()->create();

    $this->post(route('admin.atletas.estilos.store', $atleta), ['estilo_id' => $estilo->id, 'treinador_id' => $treinador->id])
        ->assertSessionHasNoErrors();
    $this->post(route('admin.atletas.estilos.store', $atleta), ['estilo_id' => $estilo->id])
        ->assertSessionHasErrors('estilo_id');

    expect(AtletaEstilo::query()->sole()->treinador->is($treinador))->toBeTrue();
});

test('a removed style keeps its history and can be linked again', function () {
    $atleta = Atleta::factory()->create();
    $estilo = EstiloDeLuta::factory()->create();
    $atleta->estilos()->attach($estilo);

    $this->delete(route('admin.atletas.estilos.destroy', AtletaEstilo::query()->sole()));
    $this->post(route('admin.atletas.estilos.store', $atleta), ['estilo_id' => $estilo->id])
        ->assertSessionHasNoErrors();

    expect($atleta->estilos()->count())->toBe(1)
        ->and(AtletaEstilo::withTrashed()->count())->toBe(2);
});

test('an athlete who takes part in a fight cannot be deleted', function () {
    $atleta = Luta::factory()->create()->participanteB;

    $this->delete(route('admin.atletas.destroy', $atleta));

    expect($atleta->refresh()->trashed())->toBeFalse();
});
