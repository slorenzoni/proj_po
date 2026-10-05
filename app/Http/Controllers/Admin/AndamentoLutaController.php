<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FuncaoJuiz;
use App\Enums\MetodoVitoria;
use App\Exceptions\AndamentoInvalidoException;
use App\Http\Requests\Admin\EncerrarLutaRequest;
use App\Models\Atleta;
use App\Models\Luta;
use App\Models\LutaJuiz;
use App\Models\Placar;
use App\Services\AndamentoLuta;
use Closure;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tela "ao vivo" da luta: o administrador informa o andamento e lança o placar oficial.
 */
class AndamentoLutaController extends AdminController
{
    public function __construct(private readonly AndamentoLuta $andamento) {}

    public function show(Luta $luta): Response
    {
        $luta->load(['evento:id,uuid,nome', 'participanteA:id,nome', 'participanteB:id,nome', 'vencedor:id,nome']);

        $usaRounds = $luta->numero_rounds !== null;

        return Inertia::render('admin/lutas/Andamento', [
            'luta' => [
                'uuid' => $luta->uuid,
                'evento' => ['uuid' => $luta->evento->uuid, 'nome' => $luta->evento->nome],
                'participante_a' => ['id' => $luta->participante_a_id, 'nome' => $luta->participanteA?->nome],
                'participante_b' => ['id' => $luta->participante_b_id, 'nome' => $luta->participanteB?->nome],
                'status' => $luta->status->label(),
                'usa_rounds' => $usaRounds,
                'numero_rounds' => $luta->numero_rounds,
                'round_atual' => $luta->round_atual,
                'em_intervalo' => $luta->em_intervalo,
                'vencedor' => $luta->vencedor?->nome,
                'metodo_vitoria' => $luta->metodo_vitoria?->label(),
                'round_fim' => $luta->round_fim,
                'tempo_fim' => $luta->tempo_fim,
            ],
            'acoes' => $this->andamento->acoesDisponiveis($luta),
            'metodos' => array_map(
                fn (MetodoVitoria $metodo): array => [
                    'value' => $metodo->value,
                    'label' => $metodo->label(),
                    'tem_vencedor' => $metodo->temVencedor(),
                ],
                MetodoVitoria::paraModalidade($usaRounds),
            ),
            'juizesLaterais' => LutaJuiz::query()
                ->where('luta_id', $luta->id)
                ->where('funcao', FuncaoJuiz::JuizLateral)
                ->with('juiz:id,nome')
                ->get()
                ->map(fn (LutaJuiz $vinculo): array => ['value' => $vinculo->juiz_id, 'label' => (string) $vinculo->juiz?->nome]),
            'placares' => $luta->placares()
                ->with('juiz:id,nome')
                ->orderBy('round')
                ->get()
                ->map(fn (Placar $placar): array => [
                    'uuid' => $placar->uuid,
                    'juiz' => $placar->juiz?->nome,
                    'round' => $placar->round,
                    'pontos_atleta_a' => $placar->pontos_atleta_a,
                    'pontos_atleta_b' => $placar->pontos_atleta_b,
                ]),
        ]);
    }

    public function iniciar(Luta $luta): RedirectResponse
    {
        return $this->executar(fn () => $this->andamento->iniciar($luta), 'Luta iniciada.');
    }

    public function encerrarRound(Luta $luta): RedirectResponse
    {
        return $this->executar(fn () => $this->andamento->encerrarRound($luta), 'Round encerrado. Intervalo aberto.');
    }

    public function iniciarProximoRound(Luta $luta): RedirectResponse
    {
        return $this->executar(fn () => $this->andamento->iniciarProximoRound($luta), 'Round iniciado.');
    }

    public function encerrar(EncerrarLutaRequest $request, Luta $luta): RedirectResponse
    {
        $vencedor = $request->filled('vencedor_id')
            ? Atleta::query()->find($request->integer('vencedor_id'))
            : null;

        return $this->executar(
            fn () => $this->andamento->encerrar(
                $luta,
                $request->enum('metodo_vitoria', MetodoVitoria::class),
                $vencedor,
                $request->filled('round_fim') ? $request->integer('round_fim') : null,
                $request->filled('tempo_fim') ? $request->string('tempo_fim')->toString() : null,
            ),
            'Luta encerrada.',
        );
    }

    public function cancelar(Luta $luta): RedirectResponse
    {
        return $this->executar(fn () => $this->andamento->cancelar($luta), 'Luta cancelada.');
    }

    /**
     * Executa a ação e converte uma transição inválida em mensagem para o administrador.
     *
     * @param  Closure(): void  $acao
     */
    private function executar(Closure $acao, string $mensagemDeSucesso): RedirectResponse
    {
        try {
            $acao();

            $this->sucesso($mensagemDeSucesso);
        } catch (AndamentoInvalidoException $exception) {
            $this->erro($exception->getMessage());
        }

        return back();
    }
}
