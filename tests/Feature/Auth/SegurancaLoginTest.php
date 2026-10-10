<?php

/**
 * SEGURANCA.md, PG1 (10/10/2026): o limite de login não pode ser contornado trocando o IP —
 * nem de fato (limite só por e-mail), nem falsificando o X-Forwarded-For (o sistema confia no
 * proxy só para o protocolo) — e o login exige o captcha do Cloudflare, aceito quando o
 * Cloudflare está fora do ar. As respostas do Cloudflare são simuladas.
 */

use App\Models\User;
use App\Support\Turnstile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

function tentarLogin(string $email, string $ip, string $senha = 'senha-errada', array $cabecalhos = [], array $extra = []): TestResponse
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeaders($cabecalhos)
        ->from(route('login'))
        ->post(route('login.store'), ['email' => $email, 'password' => $senha, ...$extra]);
}

function ligarCaptchaDoLogin(): void
{
    config([
        'services.turnstile.site_key' => 'chave-publica-de-teste',
        'services.turnstile.secret_key' => 'chave-secreta-de-teste',
    ]);
}

// ---------------------------------------------------------------- limite por e-mail

test('login é bloqueado por e-mail mesmo trocando de IP a cada tentativa', function () {
    $user = User::factory()->create(['email' => 'alvo@teste.com']);

    foreach (range(1, 20) as $i) {
        tentarLogin('alvo@teste.com', "10.0.0.{$i}")->assertSessionHasErrors('email');
        expect(session('errors')->first('email'))->not->toContain('Muitas tentativas');
    }

    tentarLogin('alvo@teste.com', '10.0.1.99')->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Muitas tentativas de entrar com este e-mail');

    // Nem com a senha certa entra enquanto o bloqueio durar.
    tentarLogin('alvo@teste.com', '10.0.2.1', 'password');
    $this->assertGuest();

    $this->travel(61)->minutes();
    tentarLogin('alvo@teste.com', '10.0.2.1', 'password');
    $this->assertAuthenticatedAs($user);
});

test('login do mesmo IP continua limitado a 5 por minuto', function () {
    User::factory()->create(['email' => 'alvo@teste.com']);

    foreach (range(1, 5) as $i) {
        tentarLogin('alvo@teste.com', '10.0.0.1');
    }

    tentarLogin('alvo@teste.com', '10.0.0.1')->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Tente novamente em');
});

// ---------------------------------------------------------------- IP real

test('X-Forwarded-For falso não muda o IP da requisição', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '189.6.240.136'])
        ->withHeaders(['X-Forwarded-For' => '6.6.6.6', 'X-Forwarded-Proto' => 'https'])
        ->get('/up')->assertOk();

    expect(request()->ip())->toBe('189.6.240.136')
        ->and(request()->isSecure())->toBeTrue();
});

test('IPv6 do visitante vindo pelo Cloudflare é mantido', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '2804:14d:4c76:8046:dcb4:8a72:ab41:cf1d'])
        ->get('/up')->assertOk();

    expect(request()->ip())->toBe('2804:14d:4c76:8046:dcb4:8a72:ab41:cf1d');
});

test('limite de login por IP não é furado trocando o X-Forwarded-For', function () {
    User::factory()->create(['email' => 'alvo@teste.com']);

    foreach (range(1, 6) as $i) {
        $resposta = tentarLogin('alvo@teste.com', '189.6.240.136', cabecalhos: ['X-Forwarded-For' => "6.6.6.{$i}"]);
    }

    $resposta->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Muitas tentativas');
});

// ---------------------------------------------------------------- captcha no login

test('login com captcha válido entra', function () {
    ligarCaptchaDoLogin();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
    $user = User::factory()->create();

    tentarLogin($user->email, '10.0.0.1', 'password', extra: [Turnstile::CAMPO => 'token-do-navegador']);

    $this->assertAuthenticatedAs($user);
});

/** Robô que acessa a Locaweb direto (sem o Cloudflare) não tem token válido — com o Cloudflare no ar, é recusado. */
test('login sem captcha ou com captcha inválido é recusado', function () {
    ligarCaptchaDoLogin();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);
    $user = User::factory()->create();

    tentarLogin($user->email, '10.0.0.1', 'password')->assertSessionHasErrors(Turnstile::CAMPO);
    $this->assertGuest();
    expect(session('errors')->first(Turnstile::CAMPO))->toBe('Confirme a verificação de segurança antes de continuar.');

    tentarLogin($user->email, '10.0.0.1', 'password', extra: [Turnstile::CAMPO => 'token-forjado']);
    $this->assertGuest();
});

test('login é aceito com o Cloudflare fora do ar', function () {
    ligarCaptchaDoLogin();
    Http::fake(['challenges.cloudflare.com/*' => fn () => throw new ConnectionException('timeout')]);
    $user = User::factory()->create();

    tentarLogin($user->email, '10.0.0.1', 'password');

    $this->assertAuthenticatedAs($user);
});

test('login também é aceito quando o Cloudflare responde com erro 5xx', function () {
    ligarCaptchaDoLogin();
    Http::fake(['challenges.cloudflare.com/*' => Http::response('erro', 503)]);
    $user = User::factory()->create();

    tentarLogin($user->email, '10.0.0.1', 'password');

    $this->assertAuthenticatedAs($user);
});

test('tela de login recebe a chave pública do captcha', function () {
    ligarCaptchaDoLogin();

    $this->get(route('login'))->assertInertia(fn ($page) => $page->where('captchaSiteKey', 'chave-publica-de-teste'));
});
