<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Para cadastros (treinador, atleta) que podem ser ligados a uma conta de usuário.
 * O administrador informa o e-mail da conta no campo "email_usuario"; cada conta
 * só pode estar ligada a um registro ativo do mesmo tipo.
 */
trait VinculaContaDeUsuario
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailUsuarioRules(): array
    {
        return ['nullable', 'string', 'email', Rule::exists(User::class, 'email')->withoutTrashed()];
    }

    /**
     * Id da conta informada, ou nulo se o campo veio vazio.
     */
    public function userId(): ?int
    {
        $email = $this->input('email_usuario');

        if (! is_string($email) || $email === '') {
            return null;
        }

        $id = User::query()->where('email', $email)->value('id');

        return is_int($id) ? $id : null;
    }

    /**
     * Acusa erro em "email_usuario" se a conta já estiver ligada a outro registro ativo.
     *
     * @param  class-string<Model>  $model
     */
    protected function validarContaLivre(Validator $validator, string $model, ?Model $registroAtual): void
    {
        $userId = $this->userId();

        if ($userId === null || $validator->errors()->has('email_usuario')) {
            return;
        }

        $jaVinculada = $model::query()
            ->where('user_id', $userId)
            ->when($registroAtual !== null, fn ($query) => $query->whereKeyNot($registroAtual?->getKey()))
            ->exists();

        if ($jaVinculada) {
            $validator->errors()->add('email_usuario', 'Esta conta já está vinculada a outro cadastro.');
        }
    }
}
