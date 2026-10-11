<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança de toda resposta (SEGURANCA.md, itens PM1 e PM2). Mesmo desenho do
 * middleware de mesmo nome no T.E.D.
 *
 * - X-Powered-By: tirado (PM2) — entregava a versão exata do PHP.
 * - X-Frame-Options / frame-ancestors: o site não pode ser embutido em página de outro domínio
 *   (clickjacking).
 * - X-Content-Type-Options: o navegador não "adivinha" o tipo de arquivo.
 * - Referrer-Policy: outros sites não recebem o caminho completo de onde a pessoa veio.
 * - Permissions-Policy: câmera, microfone, localização, pagamento e USB desligados.
 * - Strict-Transport-Security: só https neste domínio (config/seguranca.php).
 * - Content-Security-Policy: de onde a página pode carregar cada coisa; modo relatório/bloquear
 *   em config/seguranca.php.
 */
class CabecalhosDeSeguranca
{
    /**
     * Origens externas que as páginas usam de verdade (levantadas em 10/10/2026: fontes e
     * imagens são do próprio site; o YouTube aparece só como link, sem vídeo embutido). Origem
     * nova = acrescentar aqui, senão o navegador bloqueia quando a CSP estiver em 'bloquear'.
     */
    private const FONTES_EXTERNAS = [
        // Captcha do Cloudflare (components/CaptchaCloudflare.vue)
        'script' => ['https://challenges.cloudflare.com'],
        'frame' => ['https://challenges.cloudflare.com'],
        'connect' => ['https://challenges.cloudflare.com'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Gera o nonce ANTES da página ser montada: as tags do Vite e o script do modo escuro
        // (resources/views/app.blade.php) saem com ele.
        $nonce = Vite::useCspNonce();

        $resposta = $next($request);

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        $cabecalhos = $resposta->headers;
        $cabecalhos->remove('X-Powered-By');
        $cabecalhos->set('X-Frame-Options', 'SAMEORIGIN');
        $cabecalhos->set('X-Content-Type-Options', 'nosniff');
        $cabecalhos->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $cabecalhos->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        $hsts = (int) config('seguranca.hsts_segundos');

        if ($hsts > 0 && $request->isSecure()) {
            $cabecalhos->set('Strict-Transport-Security', "max-age={$hsts}");
        }

        $modo = config('seguranca.csp.modo');

        if ($modo === 'bloquear' || $modo === 'relatorio') {
            $nome = $modo === 'bloquear' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';
            $cabecalhos->set($nome, $this->politica($nonce, $modo === 'bloquear'));
        }

        return $resposta;
    }

    private function politica(string $nonce, bool $bloquear): string
    {
        $externas = self::FONTES_EXTERNAS;
        $dev = $this->servidorDeDesenvolvimento();

        $diretivas = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$externas['script'], ...$dev['http']],
            // 'unsafe-inline' em estilo: o Vue, os componentes de interface e o captcha aplicam
            // estilo direto nos elementos. Risco baixo (estilo não executa código).
            'style-src' => ["'self'", "'unsafe-inline'", ...$dev['http']],
            'font-src' => ["'self'", 'data:', ...$dev['http']],
            'img-src' => ["'self'", 'data:', 'blob:', ...$dev['http']],
            'connect-src' => ["'self'", ...$externas['connect'], ...$dev['http'], ...$dev['ws']],
            'frame-src' => [...$externas['frame']],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        $texto = collect($diretivas)
            ->map(fn (array $fontes, string $diretiva): string => $diretiva.' '.implode(' ', array_unique($fontes)))
            ->implode('; ');

        if ($bloquear) {
            $texto .= '; upgrade-insecure-requests';
        }

        return $texto.'; report-uri '.route('csp.relatorio');
    }

    /**
     * Com o servidor do Vite rodando (desenvolvimento), os arquivos vêm de outra porta, inclusive
     * o recarregamento por websocket — sem isso a CSP quebraria o desenvolvimento local. Em
     * produção não existe o arquivo public/hot.
     *
     * @return array{http: list<string>, ws: list<string>}
     */
    private function servidorDeDesenvolvimento(): array
    {
        if (! Vite::isRunningHot()) {
            return ['http' => [], 'ws' => []];
        }

        $url = rtrim(trim((string) file_get_contents(public_path('hot'))), '/');

        return ['http' => [$url], 'ws' => [(string) preg_replace('#^http#', 'ws', $url)]];
    }
}
