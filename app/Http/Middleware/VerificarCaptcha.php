<?php

namespace App\Http\Middleware;

use App\Support\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige o captcha do Cloudflare (Turnstile) no login (PG1), no cadastro e no "esqueci a senha"
 * (PG3) — SEGURANCA.md. Entra no grupo de rotas do Fortify (config/fortify.php, 'middleware')
 * e só age nas rotas listadas — o Fortify não tem gancho próprio para isso. Mesmo papel do
 * middleware de mesmo nome no T.E.D.
 *
 * Com o Cloudflare fora do ar (decisão do Sandro, a mesma do T.E.D.): o LOGIN é aceito — ninguém
 * fica trancado, e o limite por e-mail continua valendo; cadastro e "esqueci a senha" são
 * recusados. "Fora do ar" é decidido pela chamada que o SERVIDOR faz ao Cloudflare, não por
 * token vazio.
 */
class VerificarCaptcha
{
    /** Nome da rota => aceita se o Cloudflare estiver fora do ar? */
    private const ROTAS = [
        'login.store' => true,
        'register.store' => false,
        'password.email' => false,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $nomeDaRota = (string) $request->route()?->getName();

        if (! array_key_exists($nomeDaRota, self::ROTAS) || ! Turnstile::ativo()) {
            return $next($request);
        }

        $resultado = Turnstile::verificar($request->input(Turnstile::CAMPO), $request->ip());

        if ($resultado === Turnstile::VALIDO) {
            return $next($request);
        }

        if ($resultado === Turnstile::INDISPONIVEL && self::ROTAS[$nomeDaRota]) {
            return $next($request);
        }

        $mensagem = $resultado === Turnstile::INDISPONIVEL
            ? 'A verificação de segurança está fora do ar no momento. Tente novamente em alguns minutos.'
            : 'Confirme a verificação de segurança antes de continuar.';

        return back()
            ->withInput($request->except(['password', 'password_confirmation', Turnstile::CAMPO]))
            ->withErrors([Turnstile::CAMPO => $mensagem]);
    }
}
