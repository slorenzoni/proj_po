<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * captcha do Cloudflare (Turnstile). O navegador manda
 * um token gerado pelo Cloudflare junto com o formulario; aqui o SERVIDOR
 * pergunta ao Cloudflare se o token vale. Como a checagem e nossa, um robo
 * que acesse a Locaweb direto (sem passar pelo Cloudflare) nao consegue
 * gerar token valido - fecha o atalho dos itens PG1 e PG3 do SEGURANCA.md.
 *
 * Tres resultados possiveis, porque o tratamento muda conforme a tela
 * (decisao do Sandro no T.E.D., mantida no PO): se o Cloudflare estiver fora do ar, o LOGIN e
 * aceito (ninguem fica trancado; os limites por e-mail continuam valendo)
 * e o cadastro / "esqueci a senha" sao recusados.
 *
 * "Fora do ar" e decidido pela chamada que o SERVIDOR faz ao Cloudflare -
 * um robo nao tem como provocar isso mandando token vazio.
 */
class Turnstile
{
    public const VALIDO = 'valido';

    public const INVALIDO = 'invalido';

    public const INDISPONIVEL = 'indisponivel';

    public const CAMPO = 'cf-turnstile-response';

    private const URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public static function ativo(): bool
    {
        return filled(config('services.turnstile.site_key')) && filled(config('services.turnstile.secret_key'));
    }

    public static function verificar(?string $token, ?string $ip = null): string
    {
        try {
            $resposta = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post(self::URL, array_filter([
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => (string) $token,
                    'remoteip' => $ip,
                ]));
        } catch (ConnectionException $erro) {
            Log::warning('Turnstile indisponível (conexão).', ['erro' => $erro->getMessage()]);

            return self::INDISPONIVEL;
        }

        // 5xx do Cloudflare = servico fora; resposta sem o campo "success"
        // tambem nao da pra confiar.
        if ($resposta->serverError() || ! is_bool($resposta->json('success'))) {
            Log::warning('Turnstile indisponível (resposta).', ['status' => $resposta->status()]);

            return self::INDISPONIVEL;
        }

        if ($resposta->json('success') === true) {
            return self::VALIDO;
        }

        // Chave secreta errada/ausente e problema NOSSO, nao do visitante:
        // registra pra quem cuida do sistema ver.
        $codigos = (array) $resposta->json('error-codes', []);

        if (array_intersect($codigos, ['missing-input-secret', 'invalid-input-secret'])) {
            Log::error('Turnstile: chave secreta inválida ou ausente.', ['codigos' => $codigos]);
        }

        return self::INVALIDO;
    }
}
