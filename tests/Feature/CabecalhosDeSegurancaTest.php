<?php

/**
 * Cabeçalhos de segurança e CSP (SEGURANCA.md, PM1). Mesmos casos do T.E.D.
 */

use Illuminate\Support\Facades\Log;

function politicaEmRelatorio(): string
{
    return (string) test()->get(route('login'))->headers->get('Content-Security-Policy-Report-Only');
}

test('cabeçalhos básicos em toda resposta', function () {
    $this->get(route('home'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()')
        ->assertHeaderMissing('X-Powered-By');
});

test('HSTS só em https e pode ser desligado', function () {
    $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');

    config(['seguranca.hsts_segundos' => 0]);
    $this->get('https://localhost/login')->assertHeaderMissing('Strict-Transport-Security');
});

test('CSP começa em modo relatório', function () {
    $resposta = $this->get(route('login'));

    $resposta->assertHeaderMissing('Content-Security-Policy');
    $politica = (string) $resposta->headers->get('Content-Security-Policy-Report-Only');

    expect($politica)
        ->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'self'")
        ->toContain("object-src 'none'")
        ->toContain('report-uri '.route('csp.relatorio'));
});

test('CSP libera o captcha do Cloudflare e nada mais de fora', function () {
    $politica = politicaEmRelatorio();

    expect($politica)->toContain('https://challenges.cloudflare.com');

    preg_match_all('#https?://[a-z0-9.-]+#i', str_replace(route('csp.relatorio'), '', $politica), $origens);
    expect(array_unique($origens[0]))->toBe(['https://challenges.cloudflare.com']);
});

test('o único script embutido sai com o mesmo nonce da política', function () {
    $resposta = $this->get(route('login'));

    preg_match("/'nonce-([^']+)'/", (string) $resposta->headers->get('Content-Security-Policy-Report-Only'), $politica);
    expect($politica[1] ?? null)->not->toBeEmpty();

    // Scripts executáveis embutidos (sem src e que não sejam blocos de dados JSON do Inertia).
    preg_match_all('/<script(?![^>]*\bsrc=)(?![^>]*type="application\/json")([^>]*)>/', $resposta->getContent(), $embutidos);
    expect($embutidos[1])->not->toBeEmpty();

    foreach ($embutidos[1] as $atributos) {
        expect($atributos)->toContain('nonce="'.$politica[1].'"');
    }
});

test('o nonce muda a cada requisição', function () {
    preg_match("/'nonce-([^']+)'/", politicaEmRelatorio(), $a);
    preg_match("/'nonce-([^']+)'/", politicaEmRelatorio(), $b);

    expect($a[1])->not->toBe($b[1]);
});

test('modo bloquear envia a política de verdade', function () {
    config(['seguranca.csp.modo' => 'bloquear']);

    $resposta = $this->get(route('login'));

    $resposta->assertHeaderMissing('Content-Security-Policy-Report-Only');
    expect((string) $resposta->headers->get('Content-Security-Policy'))->toContain('upgrade-insecure-requests');
});

test('modo desligado não envia CSP, mas mantém os outros cabeçalhos', function () {
    config(['seguranca.csp.modo' => 'desligado']);

    $this->get(route('login'))
        ->assertHeaderMissing('Content-Security-Policy')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

test('relato de violação vai para o log da CSP só com os campos conhecidos', function () {
    Log::shouldReceive('channel')->once()->with('csp')->andReturnSelf();
    Log::shouldReceive('warning')->once()->withArgs(fn (string $mensagem, array $dados): bool => $mensagem === 'Violação de CSP'
        && $dados['blocked-uri'] === 'https://malicioso.example/x.js'
        && $dados['violated-directive'] === 'script-src'
        && ! array_key_exists('campo-estranho', $dados));

    $corpo = (string) json_encode(['csp-report' => [
        'document-uri' => 'https://po.vipti.com.br/login',
        'violated-directive' => 'script-src',
        'blocked-uri' => 'https://malicioso.example/x.js',
        'campo-estranho' => str_repeat('x', 5000),
    ]]);

    $this->call('POST', '/api/csp-relatorio', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], $corpo)
        ->assertNoContent();
});

test('relato vazio ou inválido é ignorado', function () {
    Log::shouldReceive('channel')->never();

    $this->call('POST', '/api/csp-relatorio', [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], 'isto não é json')
        ->assertNoContent();
});
