<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPatrocinador;
use App\Models\Patrocinador;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatrocinadorRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:150',
                Rule::unique(Patrocinador::class, 'nome')->withoutTrashed()->ignore($this->route('patrocinador')),
            ],
            'logo' => ['nullable', 'image', 'max:2048'],
            'link_site' => ['nullable', 'url', 'max:255'],
            'email_contato' => ['nullable', 'email', 'max:150'],
            'status' => ['required', Rule::enum(StatusPatrocinador::class)],
            'data_inicio_contrato' => ['nullable', 'date'],
            'data_fim_contrato' => ['nullable', 'date', 'after_or_equal:data_inicio_contrato'],
        ];
    }
}
