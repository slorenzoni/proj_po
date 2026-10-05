<?php

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlacarFanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'round' => ['required', 'integer', 'min:1'],
            'pontos_atleta_a' => ['required', 'integer', 'between:7,10'],
            'pontos_atleta_b' => ['required', 'integer', 'between:7,10'],
        ];
    }

    /**
     * Sistema 10-point must: o vencedor do round leva 10 (10-10 em caso de empate).
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (max($this->integer('pontos_atleta_a'), $this->integer('pontos_atleta_b')) !== 10) {
                    $validator->errors()->add('pontos_atleta_a', 'Um dos atletas precisa receber 10 pontos.');
                }
            },
        ];
    }
}
