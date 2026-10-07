<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusSolicitacaoVerificacao;
use App\Models\SolicitacaoVerificacao;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fila de pedidos de selo de verificado. Enquanto não há gateway, aprovar já concede o
 * selo; a cobrança recorrente (assinaturas_verificacao) virá com o pagamento online.
 */
class VerificacaoController extends AdminController
{
    public function index(Request $request): Response
    {
        $status = $request->enum('status', StatusSolicitacaoVerificacao::class) ?? StatusSolicitacaoVerificacao::Pendente;

        return Inertia::render('admin/verificacoes/Index', [
            'filtros' => ['status' => $status->value],
            'statusDisponiveis' => StatusSolicitacaoVerificacao::opcoes(),
            'solicitacoes' => SolicitacaoVerificacao::query()
                ->with(['user:id,name,email', 'analisadoPor:id,name'])
                ->where('status', $status)
                ->oldest()
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (SolicitacaoVerificacao $solicitacao): array => [
                    'uuid' => $solicitacao->uuid,
                    'usuario' => $solicitacao->user?->name,
                    'email' => $solicitacao->user?->email,
                    'descricao' => $solicitacao->descricao,
                    'status' => $solicitacao->status->label(),
                    'pendente' => $solicitacao->status === StatusSolicitacaoVerificacao::Pendente,
                    'motivo_rejeicao' => $solicitacao->motivo_rejeicao,
                    'analisado_por' => $solicitacao->analisadoPor?->name,
                    'analisado_em' => $solicitacao->analisado_em?->toIso8601String(),
                    'criado_em' => $solicitacao->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * O comprovante pode conter documento pessoal: é entregue só por esta rota
     * protegida, nunca por URL pública do disco.
     */
    public function documento(SolicitacaoVerificacao $solicitacao, MediaStorage $media): StreamedResponse
    {
        abort_unless($media->disk()->exists($solicitacao->documento_url), 404);

        return $media->disk()->download($solicitacao->documento_url);
    }

    public function aprovar(Request $request, SolicitacaoVerificacao $solicitacao): RedirectResponse
    {
        return $this->analisar($request, $solicitacao, StatusSolicitacaoVerificacao::Aprovada, null, 'Solicitação aprovada.');
    }

    public function rejeitar(Request $request, SolicitacaoVerificacao $solicitacao): RedirectResponse
    {
        $dados = $request->validate(['motivo_rejeicao' => ['required', 'string', 'max:1000']]);

        return $this->analisar(
            $request,
            $solicitacao,
            StatusSolicitacaoVerificacao::Rejeitada,
            $dados['motivo_rejeicao'],
            'Solicitação rejeitada.',
        );
    }

    private function analisar(
        Request $request,
        SolicitacaoVerificacao $solicitacao,
        StatusSolicitacaoVerificacao $resultado,
        ?string $motivoRejeicao,
        string $mensagem,
    ): RedirectResponse {
        if ($solicitacao->status !== StatusSolicitacaoVerificacao::Pendente) {
            $this->erro('Esta solicitação já foi analisada.');

            return back();
        }

        DB::transaction(function () use ($request, $solicitacao, $resultado, $motivoRejeicao): void {
            $solicitacao->update([
                'status' => $resultado,
                'motivo_rejeicao' => $motivoRejeicao,
                'analisado_por_user_id' => $request->user()?->id,
                'analisado_em' => now(),
            ]);

            // Enquanto não há gateway, a aprovação já concede o selo (sem gerar a cobrança).
            if ($resultado === StatusSolicitacaoVerificacao::Aprovada) {
                // forceFill: "verificado" não é preenchível por formulário, só por esta análise.
                $solicitacao->user?->forceFill(['verificado' => true, 'verificado_em' => now()])->save();
            }
        });

        $this->sucesso($mensagem);

        return back();
    }
}
