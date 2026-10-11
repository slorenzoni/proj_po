<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Recebe os relatos de violação de CSP que o navegador manda (report-uri, ver
 * CabecalhosDeSeguranca) e grava no log próprio (storage/logs/csp-AAAA-MM-DD.log). Serve para
 * ajustar a política no modo 'relatorio' antes de passar para 'bloquear' (SEGURANCA.md, PM1).
 *
 * Rota da API (sem sessão e sem CSRF — o navegador manda sozinho) e com o limite da API.
 * Qualquer um pode postar aqui, então só campos conhecidos, cortados, vão para o log.
 */
class CspRelatorioController extends Controller
{
    private const CAMPOS = [
        'document-uri', 'violated-directive', 'effective-directive',
        'blocked-uri', 'source-file', 'line-number', 'disposition',
    ];

    public function __invoke(Request $request): Response
    {
        $relato = json_decode($request->getContent(), true);
        $relato = is_array($relato) ? ($relato['csp-report'] ?? $relato) : [];

        $dados = collect(self::CAMPOS)
            ->mapWithKeys(fn (string $campo): array => [$campo => Str::limit(is_scalar($relato[$campo] ?? null) ? (string) $relato[$campo] : '', 300)])
            ->filter()
            ->all();

        if ($dados !== []) {
            Log::channel('csp')->warning('Violação de CSP', $dados);
        }

        return response()->noContent();
    }
}
