<?php

namespace App\Http\Controllers\Site;

use App\Exceptions\PalpiteNaoPermitidoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\PalpiteRequest;
use App\Models\Luta;
use App\Services\Palpites\RegistradorDePalpite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PalpiteController extends Controller
{
    /**
     * Cria o palpite do usuário na luta ou troca o que já existe (um por usuário por luta).
     */
    public function store(PalpiteRequest $request, Luta $luta, RegistradorDePalpite $registrador): RedirectResponse
    {
        try {
            $registrador->registrar(
                $request->user(),
                $luta,
                $request->integer('vencedor_id'),
                $request->metodo(),
                $request->filled('round') ? $request->integer('round') : null,
            );
        } catch (PalpiteNaoPermitidoException $exception) {
            throw ValidationException::withMessages(['vencedor_id' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Palpite registrado.']);

        return back();
    }
}
