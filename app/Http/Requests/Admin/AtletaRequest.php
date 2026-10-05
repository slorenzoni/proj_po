<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\VinculaContaDeUsuario;
use App\Models\Atleta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AtletaRequest extends FormRequest
{
    use VinculaContaDeUsuario;

    /**
     * Totais de vitórias/derrotas e "invicto" não entram: são calculados pelo model.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contagem = ['required', 'integer', 'min:0', 'max:9999'];

        return [
            'nome' => ['required', 'string', 'max:150'],
            'apelido' => ['nullable', 'string', 'max:100'],
            'tipo' => ['required', 'string', 'max:30'],
            'equipe' => ['nullable', 'string', 'max:150'],
            'pais' => ['nullable', 'string', 'max:60'],
            'cidade_natal' => ['nullable', 'string', 'max:100'],
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'altura_cm' => ['nullable', 'integer', 'between:50,300'],
            'peso_kg' => ['nullable', 'numeric', 'between:0,999.99'],
            'alcance_cm' => ['nullable', 'integer', 'between:50,300'],
            'stance' => ['nullable', 'string', 'max:20'],
            'biografia' => ['nullable', 'string', 'max:10000'],
            'vitorias_ko' => $contagem,
            'vitorias_submissao' => $contagem,
            'vitorias_decisao' => $contagem,
            'empates' => $contagem,
            'derrotas_ko' => $contagem,
            'derrotas_submissao' => $contagem,
            'derrotas_decisao' => $contagem,
            'ranking' => ['nullable', 'integer', 'min:0', 'max:9999'],
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
                $atleta = $this->route('atleta');

                $this->validarContaLivre($validator, Atleta::class, $atleta instanceof Atleta ? $atleta : null);
            },
        ];
    }

    /**
     * Atributos do model: os campos validados, trocando o e-mail pela conta correspondente.
     *
     * @return array<string, mixed>
     */
    public function atributos(): array
    {
        return [
            ...$this->safe()->except('email_usuario'),
            'user_id' => $this->userId(),
        ];
    }
}
