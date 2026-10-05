<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\Categoria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * A configuração de pontuação vale para o padrão geral (campo "categoria" vazio)
 * ou para uma categoria, identificada pelo UUID.
 */
trait ResolveEscopoDaConfiguracao
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function categoriaRules(): array
    {
        return ['nullable', 'uuid', Rule::exists(Categoria::class, 'uuid')->withoutTrashed()];
    }

    /**
     * Id da categoria informada, ou nulo para o padrão geral.
     */
    public function categoriaId(): ?int
    {
        $uuid = $this->input('categoria');

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        $id = Categoria::query()->where('uuid', $uuid)->value('id');

        return is_int($id) ? $id : null;
    }
}
