<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PlacarRequest;
use App\Models\Luta;
use App\Models\Placar;
use Illuminate\Http\RedirectResponse;

/**
 * Placar oficial, lançado na tela de andamento da luta.
 */
class PlacarController extends AdminController
{
    /**
     * Lança a pontuação do juiz no round. Lançar de novo corrige o valor anterior:
     * cada juiz tem uma única pontuação por round.
     */
    public function store(PlacarRequest $request, Luta $luta): RedirectResponse
    {
        $luta->placares()->updateOrCreate(
            $request->safe()->only(['juiz_id', 'round']),
            $request->safe()->only(['pontos_atleta_a', 'pontos_atleta_b']),
        );

        $this->sucesso('Placar lançado.');

        return back();
    }

    public function destroy(Placar $placar): RedirectResponse
    {
        $placar->delete();

        $this->sucesso('Placar excluído.');

        return back();
    }
}
