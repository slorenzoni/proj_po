<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FuncaoJuiz;
use App\Models\Juiz;
use App\Models\Luta;
use App\Models\LutaJuiz;
use App\Models\Placar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Escalação de juízes numa luta, gerenciada dentro da tela da luta.
 */
class LutaJuizController extends AdminController
{
    public function store(Request $request, Luta $luta): RedirectResponse
    {
        $dados = $request->validate([
            'juiz_id' => ['required', 'integer', Rule::exists(Juiz::class, 'id')->withoutTrashed()],
            'funcao' => ['required', Rule::enum(FuncaoJuiz::class)],
        ]);

        if ($luta->juizes()->whereKey($dados['juiz_id'])->exists()) {
            throw ValidationException::withMessages(['juiz_id' => 'Este juiz já está escalado nesta luta.']);
        }

        // attach() com pivot customizado dispara os eventos do LutaJuiz (UUID e auditoria).
        $luta->juizes()->attach($dados['juiz_id'], ['funcao' => $dados['funcao']]);

        $this->sucesso('Juiz escalado.');

        return back();
    }

    /**
     * Soft delete do vínculo (o detach() apagaria fisicamente).
     */
    public function destroy(LutaJuiz $vinculo): RedirectResponse
    {
        $temPlacar = Placar::query()
            ->where('luta_id', $vinculo->luta_id)
            ->where('juiz_id', $vinculo->juiz_id)
            ->exists();

        if ($temPlacar) {
            $this->erro('Este juiz já lançou placar nesta luta e não pode ser removido.');

            return back();
        }

        $vinculo->delete();

        $this->sucesso('Juiz removido da luta.');

        return back();
    }
}
