<?php

use App\Enums\NivelAcesso;
use App\Enums\StatusSolicitacaoVerificacao;
use App\Models\SolicitacaoVerificacao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->moderador = administrador(NivelAcesso::Moderador);
    $this->actingAs($this->moderador);
});

test('the queue shows pending requests by default and can be filtered by status', function () {
    $pendente = SolicitacaoVerificacao::factory()->create();
    SolicitacaoVerificacao::factory()->create(['status' => StatusSolicitacaoVerificacao::Rejeitada]);

    $this->get(route('admin.verificacoes.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/verificacoes/Index')
            ->has('solicitacoes.data', 1)
            ->where('solicitacoes.data.0.uuid', $pendente->uuid));

    $this->get(route('admin.verificacoes.index', ['status' => StatusSolicitacaoVerificacao::Rejeitada->value]))
        ->assertInertia(fn ($page) => $page->has('solicitacoes.data', 1)->where('solicitacoes.data.0.pendente', false));
});

test('approving records who analysed the request and when', function () {
    $solicitacao = SolicitacaoVerificacao::factory()->create();

    $this->post(route('admin.verificacoes.aprovar', $solicitacao));

    expect($solicitacao->refresh())
        ->status->toBe(StatusSolicitacaoVerificacao::Aprovada)
        ->analisado_por_user_id->toBe($this->moderador->id)
        ->analisado_em->not->toBeNull()
        ->motivo_rejeicao->toBeNull();
});

test('rejecting requires a reason and stores it', function () {
    $solicitacao = SolicitacaoVerificacao::factory()->create();

    $this->post(route('admin.verificacoes.rejeitar', $solicitacao))->assertSessionHasErrors('motivo_rejeicao');

    expect($solicitacao->refresh()->status)->toBe(StatusSolicitacaoVerificacao::Pendente);

    $this->post(route('admin.verificacoes.rejeitar', $solicitacao), ['motivo_rejeicao' => 'Documento ilegível.'])
        ->assertSessionHasNoErrors();

    expect($solicitacao->refresh())
        ->status->toBe(StatusSolicitacaoVerificacao::Rejeitada)
        ->motivo_rejeicao->toBe('Documento ilegível.');
});

test('a request that was already analysed cannot be analysed again', function () {
    $solicitacao = SolicitacaoVerificacao::factory()->create(['status' => StatusSolicitacaoVerificacao::Rejeitada]);

    $this->post(route('admin.verificacoes.aprovar', $solicitacao));

    expect($solicitacao->refresh())
        ->status->toBe(StatusSolicitacaoVerificacao::Rejeitada)
        ->analisado_por_user_id->toBeNull();
});

test('the proof document is downloaded through the protected route', function () {
    $caminho = UploadedFile::fake()->create('rg.pdf', 20)->store('verificacoes', 'public');
    $solicitacao = SolicitacaoVerificacao::factory()->create(['documento_url' => $caminho]);

    $this->get(route('admin.verificacoes.documento', $solicitacao))->assertOk()->assertDownload();
});

test('a missing proof document returns not found', function () {
    $solicitacao = SolicitacaoVerificacao::factory()->create(['documento_url' => 'verificacoes/sumiu.pdf']);

    $this->get(route('admin.verificacoes.documento', $solicitacao))->assertNotFound();
});

test('a registrar cannot download proof documents', function () {
    $solicitacao = SolicitacaoVerificacao::factory()->create();

    $this->actingAs(administrador(NivelAcesso::Cadastrador))
        ->get(route('admin.verificacoes.documento', $solicitacao))
        ->assertForbidden();
});
