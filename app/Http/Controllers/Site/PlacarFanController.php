<?php

namespace App\Http\Controllers\Site;

use App\Exceptions\PalpiteNaoPermitidoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\PlacarFanRequest;
use App\Models\Luta;
use App\Services\PlacarDosFans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PlacarFanController extends Controller
{
    /**
     * Pontua um round no placar dos fãs. Liberado para qualquer cliente (Free ou Membro).
     */
    public function store(PlacarFanRequest $request, Luta $luta, PlacarDosFans $placar): RedirectResponse
    {
        abort_unless($request->user()->isCliente(), 403);

        try {
            $placar->pontuar(
                $request->user(),
                $luta,
                $request->integer('round'),
                $request->integer('pontos_atleta_a'),
                $request->integer('pontos_atleta_b'),
            );
        } catch (PalpiteNaoPermitidoException $exception) {
            throw ValidationException::withMessages(['round' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Round pontuado.']);

        return back();
    }
}
