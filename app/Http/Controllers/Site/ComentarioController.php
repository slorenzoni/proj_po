<?php

namespace App\Http\Controllers\Site;

use App\Enums\StatusLuta;
use App\Http\Controllers\Controller;
use App\Models\Luta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ComentarioController extends Controller
{
    /**
     * Comentam: Membro ativo e os papéis especiais (ver User::podeComentar()).
     */
    public function store(Request $request, Luta $luta): RedirectResponse
    {
        abort_unless($request->user()->podeComentar(), 403);

        $dados = $request->validate(['mensagem' => ['required', 'string', 'max:500']]);

        if ($luta->status === StatusLuta::Cancelada) {
            throw ValidationException::withMessages(['mensagem' => 'A luta foi cancelada e não recebe mais comentários.']);
        }

        $luta->mensagens()->create(['user_id' => $request->user()->id, 'mensagem' => $dados['mensagem']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comentário publicado.']);

        return back();
    }
}
