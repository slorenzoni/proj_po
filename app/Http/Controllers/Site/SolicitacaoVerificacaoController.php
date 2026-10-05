<?php

namespace App\Http\Controllers\Site;

use App\Enums\StatusSolicitacaoVerificacao;
use App\Http\Controllers\Controller;
use App\Models\SolicitacaoVerificacao;
use App\Models\User;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pedido de selo de verificado, feito pelo cliente e analisado no painel administrativo.
 */
class SolicitacaoVerificacaoController extends Controller
{
    private const PASTA = 'verificacoes';

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Verificacao', [
            'verificado' => (bool) $user->verificado,
            'podeSolicitar' => $this->podeSolicitar($user),
            'solicitacoes' => SolicitacaoVerificacao::query()
                ->whereBelongsTo($user)
                ->latest()
                ->get()
                ->map(fn (SolicitacaoVerificacao $solicitacao): array => [
                    'uuid' => $solicitacao->uuid,
                    'status' => ['value' => $solicitacao->status->value, 'label' => $solicitacao->status->label()],
                    'descricao' => $solicitacao->descricao,
                    'motivo_rejeicao' => $solicitacao->motivo_rejeicao,
                    'criado_em' => $solicitacao->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function store(Request $request, MediaStorage $media): RedirectResponse
    {
        $user = $request->user();

        $dados = $request->validate([
            'documento' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'descricao' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $this->podeSolicitar($user)) {
            throw ValidationException::withMessages([
                'documento' => 'Você já tem uma solicitação em análise ou já é verificado.',
            ]);
        }

        SolicitacaoVerificacao::query()->create([
            'user_id' => $user->id,
            'documento_url' => $media->store($request->file('documento'), self::PASTA),
            'descricao' => $dados['descricao'] ?? null,
            'status' => StatusSolicitacaoVerificacao::Pendente,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Solicitação enviada. Avisaremos quando for analisada.']);

        return back();
    }

    /**
     * Só clientes ainda não verificados, e uma solicitação em análise por vez.
     */
    private function podeSolicitar(User $user): bool
    {
        return $user->isCliente()
            && ! $user->verificado
            && ! SolicitacaoVerificacao::query()
                ->whereBelongsTo($user)
                ->where('status', StatusSolicitacaoVerificacao::Pendente)
                ->exists();
    }
}
