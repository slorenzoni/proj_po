<?php

use App\Enums\ModeloCobranca;
use App\Enums\NivelAcesso;
use App\Enums\PosicaoBanner;
use App\Enums\StatusBanner;
use App\Enums\StatusPatrocinador;
use App\Enums\StatusPostagem;
use App\Models\Banner;
use App\Models\Patrocinador;
use App\Models\Postagem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = administrador(NivelAcesso::Cadastrador);
    $this->actingAs($this->admin);
});

/**
 * Formulário válido de banner, sem a imagem.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function dadosDeBanner(array $overrides = []): array
{
    return [
        'patrocinador_id' => Patrocinador::factory()->create()->id,
        'posicao' => PosicaoBanner::Home->value,
        'data_inicio' => '2026-11-01',
        'status' => StatusBanner::Ativo->value,
        'modelo_cobranca' => ModeloCobranca::Fixo->value,
        'ordem_exibicao' => 0,
        ...$overrides,
    ];
}

test('a sponsor is created with its contract dates', function () {
    $this->post(route('admin.patrocinadores.store'), [
        'nome' => 'Monster',
        'status' => StatusPatrocinador::Ativo->value,
        'data_inicio_contrato' => '2026-01-01',
        'data_fim_contrato' => '2026-12-31',
    ])->assertSessionHasNoErrors();

    expect(Patrocinador::query()->sole()->data_fim_contrato->toDateString())->toBe('2026-12-31');
});

test('a sponsor contract cannot end before it starts', function () {
    $this->post(route('admin.patrocinadores.store'), [
        'nome' => 'Monster',
        'status' => StatusPatrocinador::Ativo->value,
        'data_inicio_contrato' => '2026-12-31',
        'data_fim_contrato' => '2026-01-01',
    ])->assertSessionHasErrors('data_fim_contrato');
});

test('a sponsor with banners cannot be deleted', function () {
    $patrocinador = Banner::factory()->create()->patrocinador;

    $this->delete(route('admin.patrocinadores.destroy', $patrocinador));

    expect($patrocinador->refresh()->trashed())->toBeFalse();
});

test('a banner needs an image when it is created', function () {
    $this->post(route('admin.banners.store'), dadosDeBanner())->assertSessionHasErrors('imagem');

    $this->post(route('admin.banners.store'), dadosDeBanner(['imagem' => UploadedFile::fake()->image('peca.jpg')]))
        ->assertSessionHasNoErrors();

    Storage::disk('public')->assertExists(Banner::query()->sole()->imagem_url);
});

test('a banner keeps its image when updated without a new one', function () {
    $banner = Banner::factory()->create();

    $this->put(route('admin.banners.update', $banner), dadosDeBanner(['status' => StatusBanner::Pausado->value]))
        ->assertSessionHasNoErrors();

    expect($banner->refresh())
        ->status->toBe(StatusBanner::Pausado)
        ->imagem_url->toBe($banner->imagem_url);
});

test('a banner display period cannot end before it starts', function () {
    $this->post(route('admin.banners.store'), dadosDeBanner([
        'imagem' => UploadedFile::fake()->image('peca.jpg'),
        'data_fim' => '2026-10-01',
    ]))->assertSessionHasErrors('data_fim');
});

test('a post gets its address from the title and the logged administrator as author', function () {
    $this->post(route('admin.postagens.store'), [
        'titulo' => 'Aldo anuncia aposentadoria',
        'conteudo' => 'Texto da matéria.',
        'status' => StatusPostagem::Rascunho->value,
    ])->assertSessionHasNoErrors();

    expect(Postagem::query()->sole())
        ->slug->toBe('aldo-anuncia-aposentadoria')
        ->user_id->toBe($this->admin->id)
        ->data_publicacao->toBeNull()
        ->patrocinado->toBeFalse();
});

test('publishing a post without a date uses today', function () {
    $this->post(route('admin.postagens.store'), [
        'titulo' => 'Card completo do UFC 300',
        'conteudo' => 'Texto da matéria.',
        'status' => StatusPostagem::Publicado->value,
    ])->assertSessionHasNoErrors();

    expect(Postagem::query()->sole()->data_publicacao->isToday())->toBeTrue();
});

test('a post address must be unique, even when typed differently', function () {
    Postagem::factory()->create(['slug' => 'card-completo']);

    $this->post(route('admin.postagens.store'), [
        'titulo' => 'Outro título',
        'slug' => 'Card Completo',
        'conteudo' => 'Texto.',
        'status' => StatusPostagem::Rascunho->value,
    ])->assertSessionHasErrors('slug');
});

test('linking a sponsor on update flags the post as sponsored and keeps its author', function () {
    $postagem = Postagem::factory()->create();
    $patrocinador = Patrocinador::factory()->create();

    $this->put(route('admin.postagens.update', $postagem), [
        'titulo' => $postagem->titulo,
        'slug' => $postagem->slug,
        'conteudo' => $postagem->conteudo,
        'status' => StatusPostagem::Rascunho->value,
        'patrocinador_id' => $patrocinador->id,
    ])->assertSessionHasNoErrors();

    expect($postagem->refresh())
        ->patrocinado->toBeTrue()
        ->user_id->toBe($postagem->user_id);
});
