<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ConfiguracaoPontuacaoRequest;
use App\Http\Requests\Admin\PesosTrocaPalpiteRequest;
use App\Models\Categoria;
use App\Models\ConfiguracaoPontuacao;
use App\Models\PesoTrocaPalpite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pontuação dos palpites e pesos por momento da troca.
 *
 * A tela trabalha com um escopo por vez: o padrão geral ou uma categoria ("?categoria=uuid").
 * Salvar no escopo de uma categoria cria a configuração própria dela; "voltar ao padrão"
 * remove a configuração própria.
 */
class ConfiguracaoPontuacaoController extends AdminController
{
    public function edit(Request $request): Response
    {
        $categoria = $request->filled('categoria')
            ? Categoria::query()->where('uuid', $request->string('categoria')->toString())->firstOrFail()
            : null;

        $propria = ConfiguracaoPontuacao::query()->where('categoria_id', $categoria?->id)->first();
        $vigente = $propria ?? ConfiguracaoPontuacao::query()->whereNull('categoria_id')->first();

        $grades = [];

        foreach (PesoTrocaPalpite::NUMEROS_DE_ROUNDS as $numeroRounds) {
            $proprios = PesoTrocaPalpite::query()
                ->where('categoria_id', $categoria?->id)
                ->where('numero_rounds', $numeroRounds)
                ->pluck('peso', 'round_da_troca');

            $gerais = $categoria === null
                ? $proprios
                : PesoTrocaPalpite::query()
                    ->whereNull('categoria_id')
                    ->where('numero_rounds', $numeroRounds)
                    ->pluck('peso', 'round_da_troca');

            $vigentes = $proprios->isNotEmpty() ? $proprios : $gerais;

            $grades[] = [
                'numero_rounds' => $numeroRounds,
                'propria' => $proprios->isNotEmpty(),
                'pesos' => array_map(
                    fn (int $roundDaTroca): array => [
                        'round_da_troca' => $roundDaTroca,
                        'peso' => $vigentes->get($roundDaTroca),
                    ],
                    PesoTrocaPalpite::momentosDaTroca($numeroRounds),
                ),
            ];
        }

        return Inertia::render('admin/configuracoes/Pontuacao', [
            'escopo' => $categoria === null ? null : ['uuid' => $categoria->uuid, 'nome' => $categoria->nome],
            'categorias' => Categoria::query()->orderBy('nome')->get(['uuid', 'nome'])
                ->map(fn (Categoria $opcao): array => ['value' => $opcao->uuid, 'label' => $opcao->nome]),
            'pontuacao' => $vigente?->only([
                'pontos_vencedor',
                'pontos_vencedor_metodo',
                'pontos_vencedor_round',
                'pontos_perfeito',
                'prazo_placar_fans_minutos',
            ]),
            'pontuacaoPropria' => $propria !== null,
            'grades' => $grades,
        ]);
    }

    public function updatePontuacao(ConfiguracaoPontuacaoRequest $request): RedirectResponse
    {
        ConfiguracaoPontuacao::query()->updateOrCreate(
            ['categoria_id' => $request->categoriaId()],
            $request->safe()->except('categoria'),
        );

        $this->sucesso('Pontuação salva.');

        return back();
    }

    public function updatePesos(PesosTrocaPalpiteRequest $request): RedirectResponse
    {
        foreach ($request->pesos() as $roundDaTroca => $peso) {
            PesoTrocaPalpite::query()->updateOrCreate(
                [
                    'categoria_id' => $request->categoriaId(),
                    'numero_rounds' => $request->integer('numero_rounds'),
                    'round_da_troca' => $roundDaTroca,
                ],
                ['peso' => $peso],
            );
        }

        $this->sucesso('Pesos salvos.');

        return back();
    }

    /**
     * Remove a configuração própria da categoria: ela volta a usar o padrão geral.
     */
    public function voltarAoPadrao(Categoria $categoria): RedirectResponse
    {
        ConfiguracaoPontuacao::query()->whereBelongsTo($categoria)->get()
            ->each(fn (ConfiguracaoPontuacao $configuracao) => $configuracao->delete());

        PesoTrocaPalpite::query()->whereBelongsTo($categoria)->get()
            ->each(fn (PesoTrocaPalpite $peso) => $peso->delete());

        $this->sucesso('A categoria voltou a usar o padrão geral.');

        return back();
    }
}
