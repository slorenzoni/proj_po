<?php

namespace App\Http\Requests\Admin;

use App\Models\Organizacao;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizacaoRequest extends FormRequest
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
                Rule::unique(Organizacao::class, 'nome')->withoutTrashed()->ignore($this->route('organizacao')),
            ],
            'pais_origem' => ['nullable', 'string', 'max:60'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
