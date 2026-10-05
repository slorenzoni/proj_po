<?php

namespace App\Http\Requests\Admin;

use App\Models\EstiloDeLuta;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstiloDeLutaRequest extends FormRequest
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
                'max:60',
                Rule::unique(EstiloDeLuta::class, 'nome')->withoutTrashed()->ignore($this->route('estilo')),
            ],
        ];
    }
}
