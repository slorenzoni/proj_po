<?php

/**
 * SEGURANCA.md, PG3 (10/10/2026): cadastro, "esqueci a senha" e reenvio da verificação mandam
 * e-mail para um endereço digitado — limites de tentativas e captcha do Cloudflare impedem que o
 * SMTP (o mesmo do T.E.D.) vire relé de spam. As respostas do Cloudflare são simuladas.
 */

use App\Models\User;
use App\Support\RespostaDeLimite;
use App\Support\Turnstile;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as RequisicaoHttp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

function cadastrar(string $email, string $ip = '10.0.0.1', array $extra = []): TestResponse
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])->from(route('register'))->post(route('register.store'), [
        'name' => 'Pessoa de Teste',
        'email' => $email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'maior_de_18' => 'on',
        ...$extra,
    ]);
}

function captchaLigado(): void
{
    config([
        'services.turnstile.site_key' => 'chave-publica-de-teste',
        'services.turnstile.secret_key' => 'chave-secreta-de-teste',
    ]);
}

function cloudflareResponde(bool $valido): void
{
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => $valido, 'error-codes' => $valido ? [] : ['invalid-input-response']])]);
}

function cloudflareForaDoAr(): void
{
    Http::fake(['challenges.cloudflare.com/*' => fn () => throw new ConnectionException('timeout')]);
}

// ---------------------------------------------------------------- limites

test('cadastro é limitado a 5 por hora do mesmo IP', function () {
    Notification::fake();

    foreach (range(1, 5) as $i) {
        cadastrar("pessoa{$i}@teste.com")->assertSessionHasNoErrors();
        auth()->logout();
    }

    cadastrar('pessoa6@teste.com')->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Muitos cadastros');
    $this->assertDatabaseMissing('users', ['email' => 'pessoa6@teste.com']);
    Notification::assertSentTimes(VerifyEmail::class, 5);
});

test('cadastro tem teto geral por hora que não depende do IP', function () {
    Notification::fake();

    foreach (range(1, 30) as $i) {
        cadastrar("pessoa{$i}@teste.com", "10.1.0.{$i}")->assertSessionHasNoErrors();
        auth()->logout();
    }

    cadastrar('pessoa31@teste.com', '10.9.9.9')->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('users', ['email' => 'pessoa31@teste.com']);
});

test('"esqueci a senha" é limitado a 3 por hora para o mesmo e-mail', function () {
    Notification::fake();
    User::factory()->create(['email' => 'alvo@teste.com']);

    foreach (range(1, 3) as $i) {
        $this->from(route('password.request'))->post(route('password.email'), ['email' => 'alvo@teste.com'])->assertSessionHasNoErrors();
        $this->travel(2)->minutes(); // passa o limite de 1 por minuto do próprio Laravel
    }

    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'alvo@teste.com'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Muitos pedidos de redefinição de senha');
    Notification::assertSentTimes(ResetPassword::class, 3);
});

test('reenvio da verificação é limitado a 2 por minuto', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'));
    $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'));
    $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'));

    Notification::assertSentToTimes($user, VerifyEmail::class, 2);
});

test('texto do tempo de espera', function () {
    expect(RespostaDeLimite::tempo(45))->toBe('45 segundos')
        ->and(RespostaDeLimite::tempo(60))->toBe('1 minuto')
        ->and(RespostaDeLimite::tempo(61))->toBe('2 minutos')
        ->and(RespostaDeLimite::tempo(3600))->toBe('1 hora')
        ->and(RespostaDeLimite::tempo(86400))->toBe('24 horas');
});

// ---------------------------------------------------------------- captcha

test('telas de cadastro e "esqueci a senha" recebem a chave pública do captcha', function () {
    captchaLigado();

    $this->get(route('register'))->assertInertia(fn ($page) => $page->where('captchaSiteKey', 'chave-publica-de-teste'));
    $this->get(route('password.request'))->assertInertia(fn ($page) => $page->where('captchaSiteKey', 'chave-publica-de-teste'));
});

test('sem as chaves o captcha fica desligado', function () {
    Notification::fake();
    Http::fake();

    $this->get(route('register'))->assertInertia(fn ($page) => $page->where('captchaSiteKey', null));
    cadastrar('nova@teste.com')->assertSessionHasNoErrors();

    Http::assertNothingSent();
});

test('cadastro com captcha válido cria a conta e o servidor confere o token', function () {
    Notification::fake();
    captchaLigado();
    cloudflareResponde(true);

    cadastrar('nova@teste.com', extra: [Turnstile::CAMPO => 'token-do-navegador'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['email' => 'nova@teste.com']);
    Http::assertSent(fn (RequisicaoHttp $req) => str_contains($req->url(), 'siteverify')
        && $req['secret'] === 'chave-secreta-de-teste'
        && $req['response'] === 'token-do-navegador');
});

test('cadastro sem captcha ou com captcha inválido não cria a conta nem manda e-mail', function () {
    Notification::fake();
    captchaLigado();
    cloudflareResponde(false);

    cadastrar('nova@teste.com')->assertSessionHasErrors(Turnstile::CAMPO);
    cadastrar('nova@teste.com', extra: [Turnstile::CAMPO => 'token-forjado'])->assertSessionHasErrors(Turnstile::CAMPO);

    expect(session('errors')->first(Turnstile::CAMPO))->toBe('Confirme a verificação de segurança antes de continuar.');
    $this->assertDatabaseMissing('users', ['email' => 'nova@teste.com']);
    Notification::assertNothingSent();
});

test('cadastro é recusado com o Cloudflare fora do ar', function () {
    Notification::fake();
    captchaLigado();
    cloudflareForaDoAr();

    cadastrar('nova@teste.com', extra: [Turnstile::CAMPO => 'x'])->assertSessionHasErrors(Turnstile::CAMPO);

    expect(session('errors')->first(Turnstile::CAMPO))->toContain('fora do ar');
    $this->assertDatabaseMissing('users', ['email' => 'nova@teste.com']);
});

test('"esqueci a senha" exige captcha válido e recusa com o Cloudflare fora do ar', function () {
    Notification::fake();
    captchaLigado();
    User::factory()->create(['email' => 'alvo@teste.com']);

    cloudflareResponde(false);
    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'alvo@teste.com'])
        ->assertSessionHasErrors(Turnstile::CAMPO);

    cloudflareForaDoAr();
    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'alvo@teste.com', Turnstile::CAMPO => 'x'])
        ->assertSessionHasErrors(Turnstile::CAMPO);

    Notification::assertNothingSent();
});

test('"esqueci a senha" com captcha válido envia o link', function () {
    Notification::fake();
    captchaLigado();
    cloudflareResponde(true);
    $user = User::factory()->create(['email' => 'alvo@teste.com']);

    $this->from(route('password.request'))->post(route('password.email'), ['email' => 'alvo@teste.com', Turnstile::CAMPO => 'x'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('login ainda não passa pelo captcha (entra com o PG1)', function () {
    captchaLigado();
    cloudflareResponde(false);
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
});
