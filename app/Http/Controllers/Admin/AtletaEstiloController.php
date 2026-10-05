<?php

namespace App\Http\Controllers\Admin;

use App\Models\Atleta;
use App\Models\AtletaEstilo;
use App\Models\EstiloDeLuta;
use App\Models\Treinador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Estilos de luta do atleta (com o treinador de cada estilo), gerenciados dentro da tela do atleta.
 */
class AtletaEstiloController extends AdminController
{
    public function store(Request $request, Atleta $atleta): RedirectResponse
    {
        $dados = $request->validate([
            'estilo_id' => ['required', 'integer', Rule::exists(EstiloDeLuta::class, 'id')->withoutTrashed()],
            'treinador_id' => ['nullable', 'integer', Rule::exists(Treinador::class, 'id')->withoutTrashed()],
        ]);

        if ($atleta->estilos()->whereKey($dados['estilo_id'])->exists()) {
            throw ValidationException::withMessages(['estilo_id' => 'O atleta já tem este estilo.']);
        }

        // attach() com pivot customizado dispara os eventos do AtletaEstilo (UUID e auditoria).
        $atleta->estilos()->attach($dados['estilo_id'], ['treinador_id' => $dados['treinador_id'] ?? null]);

        $this->sucesso('Estilo adicionado.');

        return back();
    }

    /**
     * Soft delete do vínculo (o detach() apagaria fisicamente).
     */
    public function destroy(AtletaEstilo $vinculo): RedirectResponse
    {
        $vinculo->delete();

        $this->sucesso('Estilo removido.');

        return back();
    }
}
