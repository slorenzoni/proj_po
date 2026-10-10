<?php

namespace App\Http\Middleware;

use App\Support\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige o captcha do Cloudflare (Turnstile) nas rotas do Fortify que mandam e-mail para um
 * endereço digitado: cadastro e "esqueci a senha" (SEGURANCA.md, PG3). Entra no grupo de rotas
 * do Fortify (config/fortify.php, 'middleware') e só age nas rotas listadas — o Fortify não tem
 * gancho próprio para isso. Mesmo papel do middleware de mesmo nome no T.E.D.
 *
 * Com o Cloudflare fora do ar, cadastro e "esqueci a senha" são RECUSADOS (decisão do Sandro no
 * T.E.D.). O login, que no T.E.D. é aceito nesse caso, entra com o PG1.
 */
class VerificarCaptcha
{
    /** Rotas que exigem o captcha. */
    private const ROTAS = [
        'register.store',
        'password.email',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->route()?->getName(), self::ROTAS, true) || ! Turnstile::ativo()) {
            return $next($request);
        }

        $resultado = Turnstile::verificar($request->input(Turnstile::CAMPO), $request->ip());

        if ($resultado === Turnstile::VALIDO) {
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
