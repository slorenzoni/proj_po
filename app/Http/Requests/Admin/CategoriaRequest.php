<?php

namespace App\Http\Requests\Admin;

use App\Models\Categoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
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
                'max:50',
                Rule::unique(Categoria::class, 'nome')->withoutTrashed()->ignore($this->route('categoria')),
            ],
            'usa_rounds' => ['required', 'boolean'],
        ];
    }
}
