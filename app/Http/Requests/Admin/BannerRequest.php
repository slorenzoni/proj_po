<?php

namespace App\Http\Requests\Admin;

use App\Enums\ModeloCobranca;
use App\Enums\PosicaoBanner;
use App\Enums\StatusBanner;
use App\Models\Patrocinador;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patrocinador_id' => ['required', 'integer', Rule::exists(Patrocinador::class, 'id')->withoutTrashed()],
            // A imagem é obrigatória só na criação; na edição, enviar outra substitui a atual.
            'imagem' => [Rule::requiredIf($this->route('banner') === null), 'nullable', 'image', 'max:4096'],
            'link_destino' => ['nullable', 'url', 'max:255'],
            'posicao' => ['required', Rule::enum(PosicaoBanner::class)],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'status' => ['required', Rule::enum(StatusBanner::class)],
            'modelo_cobranca' => ['required', Rule::enum(ModeloCobranca::class)],
            'valor_contrato' => ['nullable', 'numeric', 'between:0,99999999.99'],
            'ordem_exibicao' => ['required', 'integer', 'min:0'],
        ];
    }
}
