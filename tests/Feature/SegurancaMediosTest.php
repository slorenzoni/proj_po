<?php

/**
 * Itens médios do SEGURANCA.md (10/10/2026): PM2 (versão do PHP exposta), PM3 (descobrir quem
 * tem conta pelo "esqueci a senha") e PM4 (limites gerais).
 */

use App\Models\Luta;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Inertia\Support\SessionKey;

function pedirRedefinicao(string $email): TestResponse
{
    return test()->from(route('password.request'))->post(route('password.email'), ['email' => $email]);
}

// ---------------------------------------------------------------- PM2

test('resposta não tem X-Powered-By', function () {
    $this->get(route('home'))->assertOk()->assertHeaderMissing('X-Powered-By');
    $this->get(route('login'))->assertHeaderMissing('X-Powered-By');
});

// ---------------------------------------------------------------- PM3

test('"esqueci a senha" responde igual para e-mail com e sem conta', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'tem-conta@teste.com']);

    $comConta = pedirRedefinicao('tem-conta@teste.com');
    $statusComConta = session('status');

    $semConta = pedirRedefinicao('nao-tem-conta@teste.com');
    $statusSemConta = session('status');

    $comConta->assertSessionHasNoErrors()->assertRedirect(route('password.request'));
    $semConta->assertSessionHasNoErrors()->assertRedirect(route('password.request'));
    expect($statusSemConta)->toBe($statusComConta)
        ->and($statusSemConta)->toStartWith('Se este e-mail estiver cadastrado');

    // E-mail só sai para quem tem conta.
    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

test('pedido repetido em menos de um minuto também responde igual', function () {
    Notification::fake();
    User::factory()->create(['email' => 'tem-conta@teste.com']);

    pedirRedefinicao('tem-conta@teste.com');
    pedirRedefinicao('tem-conta@teste.com')->assertSessionHasNoErrors();

    expect(session('status'))->toStartWith('Se este e-mail estiver cadastrado');
    Notification::assertCount(1);
});

test('e-mail inválido continua sendo recusado', function () {
    pedirRedefinicao('nao-e-email')->assertSessionHasErrors('email');
});

// ---------------------------------------------------------------- PM4

test('telas logadas limitadas a 120 por minuto por usuário', function () {
    $user = cliente();
    $outro = cliente();

    foreach (range(1, 120) as $i) {
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    $this->actingAs($user)->get(route('dashboard'))->assertStatus(429);

    // O limite é por usuário: outra pessoa não é afetada.
    $this->actingAs($outro)->get(route('dashboard'))->assertOk();
});

test('palpite limitado a 20 por minuto, com aviso na tela', function () {
    $user = cliente();
    $luta = Luta::factory()->create();

    // O limite conta a tentativa mesmo quando a validação recusa — aqui sem dados.
    foreach (range(1, 20) as $i) {
        $this->actingAs($user)->from(route('lutas.show', $luta))->post(route('palpites.store', $luta), []);
    }

    $this->actingAs($user)->from(route('lutas.show', $luta))->post(route('palpites.store', $luta), [])
        ->assertRedirect(route('lutas.show', $luta));

    expect(session(SessionKey::FLASH_DATA)['toast'] ?? null)->toMatchArray(['type' => 'error'])
        ->and(session(SessionKey::FLASH_DATA)['toast']['message'])->toContain('Muitos envios');
});

test('placar dos fãs limitado a 20 por minuto', function () {
    $user = cliente();
    $luta = Luta::factory()->create();

    foreach (range(1, 20) as $i) {
        $this->actingAs($user)->from(route('lutas.show', $luta))->post(route('placar-dos-fans.store', $luta), []);
    }

    $resposta = $this->actingAs($user)->from(route('lutas.show', $luta))->post(route('placar-dos-fans.store', $luta), []);

    $resposta->assertRedirect(route('lutas.show', $luta))->assertSessionHasNoErrors();
    expect(session(SessionKey::FLASH_DATA)['toast']['message'] ?? '')->toContain('Muitos envios');
});
