<?php

namespace App\Http\Controllers\Site;

use App\Enums\StatusLuta;
use App\Http\Controllers\Controller;
use App\Models\Luta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DicaController extends Controller
{
    /**
     * Publica uma dica. Só comentaristas e Membros com selo de verificado (User::podeDarDica()).
     */
    public function store(Request $request, Luta $luta): RedirectResponse
    {
        abort_unless($request->user()->podeDarDica(), 403);

        $dados = $request->validate(['texto' => ['required', 'string', 'max:2000']]);

        // Dica é análise antes ou durante a luta: depois do resultado ou do cancelamento não faz sentido.
        if (in_array($luta->status, [StatusLuta::Encerrada, StatusLuta::Cancelada], true)) {
            throw ValidationException::withMessages(['texto' => 'Esta luta já terminou e não recebe mais dicas.']);
        }

        $luta->dicas()->create(['user_id' => $request->user()->id, 'texto' => $dados['texto']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dica publicada.']);

        return back();
    }
}
