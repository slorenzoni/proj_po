<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Base dos controllers do painel: mensagens de retorno e leitura dos filtros de listagem.
 */
abstract class AdminController extends Controller
{
    /**
     * Registros por página nas listagens do painel.
     */
    protected const POR_PAGINA = 15;

    protected function sucesso(string $mensagem): void
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $mensagem]);
    }

    protected function erro(string $mensagem): void
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $mensagem]);
    }

    /**
     * Termo digitado no campo de busca da listagem ("?busca=").
     */
    protected function busca(Request $request): string
    {
        return $request->string('busca')->trim()->toString();
    }
}
