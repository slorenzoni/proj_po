<?php

use App\Http\Middleware\CabecalhosDeSeguranca;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Na Locaweb o HTTPS termina num proxy antes do PHP: confiar nos cabeçalhos X-Forwarded-*
        // para o Laravel saber o protocolo e o IP real do visitante (usado na auditoria).
        //
        // Corrigido em 10/10/2026 (SEGURANCA.md, PG1): confia SÓ no protocolo, nunca no IP.
        // Diagnóstico feito no T.E.D. (mesmo servidor e mesmo proxy): a Locaweb já entrega o IP
        // real no REMOTE_ADDR, pelo Cloudflare e direto; o X-Forwarded-For, no acesso direto,
        // chega como o visitante mandou. Com '*' em todos os cabeçalhos, um IP falso ali virava o
        // request()->ip() e furava os limites por IP e a auditoria.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_PROTO);

        // Cabeçalhos de segurança em toda resposta (SEGURANCA.md, PM2; depois o PM1).
        $middleware->append(CabecalhosDeSeguranca::class);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
