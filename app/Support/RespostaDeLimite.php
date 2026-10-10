<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Resposta amigável quando um limite de tentativas é atingido (Limit::...->response(...)).
 * O padrão do Laravel devolve um 429 cru, que o Inertia mostra como uma janela de erro; aqui a
 * pessoa volta para a mesma tela com a mensagem no campo ou num aviso (toast).
 * Ver SEGURANCA.md, itens PG1 e PG3. Mesmo papel da classe de mesmo nome no T.E.D.
 */
class RespostaDeLimite
{
    /**
     * Volta com a mensagem de erro num campo do formulário.
     * ":tempo" na mensagem vira "5 minutos", "1 hora" etc.
     */
    public static function noCampo(string $campo, string $mensagem): Closure
    {
        return fn (Request $request, array $headers) => back()
            ->withInput($request->except(['password', 'password_confirmation']))
            ->withErrors([$campo => self::preencher($mensagem, $headers)]);
    }

    /** Volta com a mensagem num aviso de erro (toast, ver resources/js/lib/flashToast.ts). */
    public static function noAviso(string $mensagem): Closure
    {
        return function (Request $request, array $headers) use ($mensagem) {
            Inertia::flash('toast', ['type' => 'error', 'message' => self::preencher($mensagem, $headers)]);

            return back(303);
        };
    }

    /**
     * @param  array<string, mixed>  $headers  Cabeçalhos da resposta de limite (traz o Retry-After).
     */
    private static function preencher(string $mensagem, array $headers): string
    {
        return str_replace(':tempo', self::tempo((int) ($headers['Retry-After'] ?? 60)), $mensagem);
    }

    /** 45 -> "45 segundos"; 90 -> "2 minutos"; 3600 -> "1 hora". */
    public static function tempo(int $segundos): string
    {
        if ($segundos < 60) {
            return $segundos === 1 ? '1 segundo' : "{$segundos} segundos";
        }

        $minutos = (int) ceil($segundos / 60);

        if ($minutos < 60) {
            return $minutos === 1 ? '1 minuto' : "{$minutos} minutos";
        }

        $horas = (int) ceil($minutos / 60);

        return $horas === 1 ? '1 hora' : "{$horas} horas";
    }
}
