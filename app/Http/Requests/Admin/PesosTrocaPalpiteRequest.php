<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ResolveEscopoDaConfiguracao;
use App\Models\PesoTrocaPalpite;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PesosTrocaPalpiteRequest extends FormRequest
{
    use ResolveEscopoDaConfiguracao;

    /**
     * A grade precisa vir completa: um peso para a pré-luta (0) e um para cada
     * intervalo (1 até o penúltimo round).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $regras = [
            'categoria' => $this->categoriaRules(),
            'numero_rounds' => ['required', 'integer', Rule::in(PesoTrocaPalpite::NUMEROS_DE_ROUNDS)],
            'pesos' => ['required', 'array'],
        ];

        foreach (PesoTrocaPalpite::momentosDaTroca($this->integer('numero_rounds')) as $roundDaTroca) {
            $regras["pesos.{$roundDaTroca}"] = ['required', 'numeric', 'between:0,100'];
        }

        return $regras;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['pesos.*' => 'peso'];
    }

    /**
     * Peso por momento da troca, só com os momentos válidos para o número de rounds.
     *
     * @return array<int, float>
     */
    public function pesos(): array
    {
        $pesos = [];

        foreach (PesoTrocaPalpite::momentosDaTroca($this->integer('numero_rounds')) as $roundDaTroca) {
            $pesos[$roundDaTroca] = $this->float("pesos.{$roundDaTroca}");
        }

        return $pesos;
    }
}
