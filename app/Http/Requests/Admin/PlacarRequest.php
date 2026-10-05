<?php

namespace App\Http\Requests\Admin;

use App\Enums\FuncaoJuiz;
use App\Enums\StatusLuta;
use App\Models\Luta;
use App\Models\LutaJuiz;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlacarRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $luta = $this->luta();

        return [
            'juiz_id' => [
                'required',
                'integer',
                // Decisão de 01/10/2026: só juízes laterais escalados na luta pontuam.
                Rule::exists(LutaJuiz::class, 'juiz_id')
                    ->where('luta_id', $luta->id)
                    ->where('funcao', FuncaoJuiz::JuizLateral->value)
                    ->withoutTrashed(),
            ],
            'round' => ['required', 'integer', 'between:1,'.max(1, (int) $luta->numero_rounds)],
            'pontos_atleta_a' => ['required', 'integer', 'between:0,10'],
            'pontos_atleta_b' => ['required', 'integer', 'between:0,10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'juiz_id.exists' => 'Só juízes laterais escalados nesta luta podem pontuar.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $luta = $this->luta();

                if ($luta->numero_rounds === null) {
                    $validator->errors()->add('round', 'Esta modalidade não tem placar por round.');
                }

                if (! in_array($luta->status, [StatusLuta::EmAndamento, StatusLuta::Encerrada], true)) {
                    $validator->errors()->add('round', 'O placar só pode ser lançado depois que a luta começa.');
                }
            },
        ];
    }

    private function luta(): Luta
    {
        $luta = $this->route('luta');

        abort_unless($luta instanceof Luta, 404);

        return $luta;
    }
}
