<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * limite de tentativas no cadastro e no "esqueci a
 * senha", as duas rotas publicas do Fortify que mandam e-mail pra um
 * endereco digitado por qualquer um. Sem limite, um robo usa o SMTP do
 * sistema pra disparar e-mails a terceiros (SEGURANCA.md, item PG3).
 *
 * O Fortify nao tem opcao de limite pra essas duas rotas (so pra login,
 * duas etapas, passkeys e verificacao). Este middleware entra no grupo de
 * todas as rotas do Fortify (config/fortify.php, 'middleware') e so age
 * nessas duas, delegando pro throttle padrao do Laravel com os limitadores
 * nomeados de FortifyServiceProvider.
 */
class LimitarEnviosDaAutenticacao
{
    /** Nome da rota => limitador (RateLimiter::for). */
    private const LIMITADORES = [
        'register.store' => 'cadastro',
        'password.email' => 'recuperacao-senha',
    ];

    public function __construct(private ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next): Response
    {
        $limitador = self::LIMITADORES[$request->route()?->getName()] ?? null;

        if ($limitador === null) {
            return $next($request);
        }

        return $this->throttle->handle($request, $next, $limitador);
    }
}
