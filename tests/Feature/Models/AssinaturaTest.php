<?php

use App\Enums\PlanoAssinatura;
use App\Enums\StatusSolicitacaoVerificacao;
use App\Models\Assinatura;
use App\Models\AssinaturaVerificacao;
use App\Models\SolicitacaoVerificacao;
use App\Models\User;

test('a free subscription has no charge, gateway or next billing date', function () {
    $assinatura = Assinatura::factory()->create()->refresh();

    expect($assinatura->plano)->toBe(PlanoAssinatura::Free)
        ->and($assinatura->valor)->toBe('0.00')
        ->and($assinatura->gateway)->toBeNull()
        ->and($assinatura->periodicidade)->toBeNull()
        ->and($assinatura->proxima_cobranca)->toBeNull()
        ->and($assinatura->user->assinaturas->sole()->is($assinatura))->toBeTrue();
});

test('a member subscription is billed monthly', function () {
    $assinatura = Assinatura::factory()->membro()->create()->refresh();

    expect($assinatura->plano)->toBe(PlanoAssinatura::Membro)
        ->and($assinatura->proxima_cobranca->isSameDay($assinatura->data_inicio->addMonth()))->toBeTrue();
});

test('a verification request distinguishes the requester from the reviewing admin', function () {
    $solicitante = User::factory()->create();
    $admin = User::factory()->create();

    $solicitacao = SolicitacaoVerificacao::factory()->for($solicitante)->create([
        'status' => StatusSolicitacaoVerificacao::Aprovada,
        'analisado_por_user_id' => $admin,
        'analisado_em' => now(),
    ]);

    expect($solicitacao->user->is($solicitante))->toBeTrue()
        ->and($solicitacao->analisadoPor->is($admin))->toBeTrue();
});

test('the verified badge charge belongs to the user who made the request', function () {
    $cobranca = AssinaturaVerificacao::factory()->create();

    expect($cobranca->solicitacaoVerificacao->user_id)->toBe($cobranca->user_id);
});
