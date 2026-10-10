<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança de toda resposta (SEGURANCA.md, PM2; os do PM1 — CSP,
 * X-Frame-Options etc. — entram aqui depois, como no T.E.D.).
 *
 * Por enquanto: tira o "X-Powered-By: PHP/8.5.7" que o PHP da Locaweb põe em toda resposta
 * (entrega a versão exata para quem procura falha conhecida). Não dá para desligar o expose_php
 * na hospedagem compartilhada; header_remove() tira o cabeçalho antes de ele sair.
 */
class CabecalhosDeSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $resposta->headers->remove('X-Powered-By');

        return $resposta;
    }
}
