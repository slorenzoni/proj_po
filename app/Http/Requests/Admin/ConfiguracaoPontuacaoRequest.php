<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ResolveEscopoDaConfiguracao;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfiguracaoPontuacaoRequest extends FormRequest
{
    use ResolveEscopoDaConfiguracao;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pontos = ['required', 'integer', 'between:0,1000'];

        return [
            'categoria' => $this->categoriaRules(),
            'pontos_vencedor' => $pontos,
            'pontos_vencedor_metodo' => $pontos,
            'pontos_vencedor_round' => $pontos,
            'pontos_perfeito' => $pontos,
            'prazo_placar_fans_minutos' => ['required', 'integer', 'between:1,1440'],
        ];
    }
}
