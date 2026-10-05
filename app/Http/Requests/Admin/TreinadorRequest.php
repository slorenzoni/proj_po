<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\VinculaContaDeUsuario;
use App\Models\Treinador;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TreinadorRequest extends FormRequest
{
    use VinculaContaDeUsuario;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'pais' => ['nullable', 'string', 'max:60'],
            'email_usuario' => $this->emailUsuarioRules(),
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $treinador = $this->route('treinador');

                $this->validarContaLivre($validator, Treinador::class, $treinador instanceof Treinador ? $treinador : null);
            },
        ];
    }
}
