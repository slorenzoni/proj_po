<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CategoriaPesoRequest;
use App\Models\Categoria;
use App\Models\CategoriaPeso;
use App\Models\Luta;
use Illuminate\Http\RedirectResponse;

/**
 * Categorias de peso, sempre gerenciadas dentro da tela da modalidade.
 */
class CategoriaPesoController extends AdminController
{
    public function store(CategoriaPesoRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->categoriasPeso()->create($request->validated());

        $this->sucesso('Categoria de peso adicionada.');

        return back();
    }

    public function update(CategoriaPesoRequest $request, CategoriaPeso $peso): RedirectResponse
    {
        $peso->update($request->validated());

        $this->sucesso('Categoria de peso atualizada.');

        return back();
    }

    public function destroy(CategoriaPeso $peso): RedirectResponse
    {
        if (Luta::query()->whereBelongsTo($peso, 'categoriaPeso')->exists()) {
            $this->erro('Esta categoria de peso é usada em lutas e não pode ser excluída.');

            return back();
        }

        $peso->delete();

        $this->sucesso('Categoria de peso excluída.');

        return back();
    }
}
