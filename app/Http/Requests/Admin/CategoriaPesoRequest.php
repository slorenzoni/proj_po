<?php

namespace App\Http\Requests\Admin;

use App\Models\Categoria;
use App\Models\CategoriaPeso;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaPesoRequest extends FormRequest
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
                // O nome só precisa ser único dentro da própria modalidade.
                Rule::unique(CategoriaPeso::class, 'nome')
                    ->where('categoria_id', $this->categoriaId())
                    ->withoutTrashed()
                    ->ignore($this->route('peso')),
            ],
            'peso_minimo_kg' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'peso_maximo_kg' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.99',
                // Só compara quando há mínimo: uma categoria pode ter apenas o limite superior.
                Rule::when($this->filled('peso_minimo_kg'), 'gte:peso_minimo_kg'),
            ],
        ];
    }

    /**
     * Na criação a modalidade vem da rota; na edição, do próprio registro.
     */
    private function categoriaId(): int
    {
        $categoria = $this->route('categoria');
        $peso = $this->route('peso');

        return $categoria instanceof Categoria
            ? $categoria->id
            : ($peso instanceof CategoriaPeso ? $peso->categoria_id : 0);
    }
}
