<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusEvento;
use App\Enums\TipoTransmissao;
use App\Models\Organizacao;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organizacao_id' => ['required', 'integer', Rule::exists(Organizacao::class, 'id')->withoutTrashed()],
            'nome' => ['required', 'string', 'max:200'],
            'data' => ['required', 'date'],
            'local' => ['nullable', 'string', 'max:200'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'pais' => ['nullable', 'string', 'max:60'],
            'link_canal_youtube' => ['nullable', 'url', 'max:255'],
            'tipo_transmissao' => ['nullable', Rule::enum(TipoTransmissao::class)],
            'status' => ['required', Rule::enum(StatusEvento::class)],
        ];
    }
}
