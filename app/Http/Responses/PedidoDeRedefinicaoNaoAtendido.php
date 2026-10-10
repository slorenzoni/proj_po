<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * "Esqueci a senha" sem revelar quem tem conta (SEGURANCA.md, PM3). O padrão do Fortify
 * respondia "Não encontramos um usuário com esse endereço de e-mail" — dava para testar e-mails
 * e descobrir quem é cliente do sistema.
 *
 * Agora, e-mail sem conta e pedido repetido em menos de 1 minuto (o Laravel só segura isso para
 * e-mail QUE EXISTE — responder diferente também revelaria) recebem a mesma resposta de sucesso:
 * "Se este e-mail estiver cadastrado, enviamos..." (lang/pt_BR/passwords.php, 'sent').
 * Qualquer outro motivo de falha segue o comportamento padrão. Mesma classe do T.E.D.
 */
class PedidoDeRedefinicaoNaoAtendido extends FailedPasswordResetLinkRequestResponse
{
    private const RESPONDER_COMO_SUCESSO = [
        Password::INVALID_USER,
        Password::RESET_THROTTLED,
    ];

    public function toResponse($request)
    {
        if (in_array($this->status, self::RESPONDER_COMO_SUCESSO, true)) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
        }

        return parent::toResponse($request);
    }
}
