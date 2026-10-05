<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FuncaoJuiz;
use App\Enums\StatusLuta;
use App\Enums\TipoCard;
use App\Http\Requests\Admin\LutaRequest;
use App\Models\Atleta;
use App\Models\Categoria;
use App\Models\CategoriaPeso;
use App\Models\Evento;
use App\Models\Juiz;
use App\Models\Luta;
use App\Models\LutaJuiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lutas do card. São criadas dentro de um evento; depois de iniciada, a luta só
 * muda pela tela de andamento.
 */
class LutaController extends AdminController
{
    public function create(Evento $evento): Response
    {
        return Inertia::render('admin/lutas/Form', [
            'evento' => $this->dadosEvento($evento),
            'luta' => null,
            'proximaOrdem' => ((int) $evento->lutas()->max('ordem_na_card')) + 1,
            ...$this->opcoes(),
        ]);
    }

    public function store(LutaRequest $request, Evento $evento): RedirectResponse
    {
        $luta = $evento->lutas()->create($request->atributos());

        $this->sucesso('Luta adicionada ao card. Escale agora os juízes.');

        return to_route('admin.lutas.edit', $luta);
    }

    public function edit(Luta $luta): Response
    {
        $luta->load('evento');

        return Inertia::render('admin/lutas/Form', [
            'evento' => $this->dadosEvento($luta->evento),
            'luta' => [
                ...$luta->only([
                    'uuid', 'categoria_id', 'categoria_peso_id', 'participante_a_id', 'participante_b_id',
                    'ordem_na_card', 'numero_rounds', 'chance_do_a', 'chance_do_b',
                ]),
                'tipo_card' => $luta->tipo_card?->value,
                'editavel' => $luta->status === StatusLuta::Agendada,
                'status' => $luta->status->label(),
                'juizes' => LutaJuiz::query()
                    ->where('luta_id', $luta->id)
                    ->with('juiz:id,nome')
                    ->get()
                    ->map(fn (LutaJuiz $vinculo): array => [
                        'uuid' => $vinculo->uuid,
                        'nome' => $vinculo->juiz?->nome,
                        'funcao' => $vinculo->funcao->label(),
                    ]),
            ],
            'proximaOrdem' => null,
            ...$this->opcoes(),
            'juizes' => Juiz::query()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (Juiz $juiz): array => ['value' => $juiz->id, 'label' => $juiz->nome]),
            'funcoesJuiz' => FuncaoJuiz::opcoes(),
        ]);
    }

    public function update(LutaRequest $request, Luta $luta): RedirectResponse
    {
        if ($luta->status !== StatusLuta::Agendada) {
            $this->erro('Só é possível alterar lutas agendadas.');

            return back();
        }

        $atributos = $request->atributos();

        // Trocar um dos atletas invalida os palpites; inverter os cantos (A ↔ B) não.
        $trocouAtleta = array_diff(
            [(int) $atributos['participante_a_id'], (int) $atributos['participante_b_id']],
            [$luta->participante_a_id, $luta->participante_b_id],
        ) !== [];

        $palpitesDescartados = DB::transaction(function () use ($luta, $atributos, $trocouAtleta): int {
            $luta->update($atributos);

            return $trocouAtleta ? $luta->descartarPalpites() : 0;
        });

        $this->sucesso($palpitesDescartados > 0
            ? "Luta atualizada. {$palpitesDescartados} palpite(s) foram zerados porque um atleta foi substituído."
            : 'Luta atualizada.');

        return to_route('admin.lutas.edit', $luta);
    }

    public function destroy(Luta $luta): RedirectResponse
    {
        if ($luta->status !== StatusLuta::Agendada || $luta->palpites()->exists()) {
            $this->erro('Só é possível excluir lutas agendadas e sem palpites. Para as demais, use o cancelamento.');

            return back();
        }

        $evento = $luta->evento;

        $luta->delete();

        $this->sucesso('Luta excluída do card.');

        return to_route('admin.eventos.show', $evento);
    }

    /**
     * @return array{uuid: string, nome: string}
     */
    private function dadosEvento(Evento $evento): array
    {
        return ['uuid' => $evento->uuid, 'nome' => $evento->nome];
    }

    /**
     * Listas dos campos de seleção. As categorias de peso levam a modalidade, para o
     * formulário filtrar conforme a categoria escolhida.
     *
     * @return array<string, mixed>
     */
    private function opcoes(): array
    {
        return [
            'categorias' => Categoria::query()->orderBy('nome')->get(['id', 'nome', 'usa_rounds'])
                ->map(fn (Categoria $categoria): array => [
                    'value' => $categoria->id,
                    'label' => $categoria->nome,
                    'usa_rounds' => $categoria->usa_rounds,
                ]),
            'categoriasPeso' => CategoriaPeso::query()->orderBy('peso_maximo_kg')->orderBy('nome')
                ->get(['id', 'nome', 'categoria_id'])
                ->map(fn (CategoriaPeso $peso): array => [
                    'value' => $peso->id,
                    'label' => $peso->nome,
                    'categoria_id' => $peso->categoria_id,
                ]),
            'atletas' => Atleta::query()->orderBy('nome')->get(['id', 'nome', 'apelido'])
                ->map(fn (Atleta $atleta): array => [
                    'value' => $atleta->id,
                    'label' => $atleta->apelido ? "{$atleta->nome} “{$atleta->apelido}”" : $atleta->nome,
                ]),
            'tiposCard' => TipoCard::opcoes(),
        ];
    }
}
