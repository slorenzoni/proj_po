<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusEvento;
use App\Enums\TipoTransmissao;
use App\Http\Requests\Admin\EventoRequest;
use App\Models\Evento;
use App\Models\Luta;
use App\Models\Organizacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class EventoController extends AdminController
{
    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/eventos/Index', [
            'filtros' => ['busca' => $busca],
            'eventos' => Evento::query()
                ->with('organizacao:id,nome')
                ->withCount('lutas')
                ->when($busca !== '', fn ($query) => $query->whereLike('nome', "%{$busca}%"))
                ->orderByDesc('data')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Evento $evento): array => [
                    'uuid' => $evento->uuid,
                    'nome' => $evento->nome,
                    'organizacao' => $evento->organizacao?->nome,
                    'data' => $evento->data->toIso8601String(),
                    'status' => $evento->status->label(),
                    'lutas_count' => $evento->getAttribute('lutas_count'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/eventos/Form', ['evento' => null, ...$this->opcoes()]);
    }

    public function store(EventoRequest $request): RedirectResponse
    {
        $evento = Evento::query()->create($request->validated());

        $this->sucesso('Evento criado. Monte agora o card de lutas.');

        return to_route('admin.eventos.show', $evento);
    }

    /**
     * Card do evento: lista as lutas e dá acesso à edição e ao andamento de cada uma.
     */
    public function show(Evento $evento): Response
    {
        $evento->load([
            'organizacao:id,nome',
            'lutas.participanteA:id,nome',
            'lutas.participanteB:id,nome',
            'lutas.categoriaPeso:id,nome',
        ]);

        return Inertia::render('admin/eventos/Show', [
            'evento' => [
                'uuid' => $evento->uuid,
                'nome' => $evento->nome,
                'organizacao' => $evento->organizacao?->nome,
                'data' => $evento->data->toIso8601String(),
                'local' => $evento->local,
                'status' => $evento->status->label(),
                'lutas' => $evento->lutas->map(fn (Luta $luta): array => [
                    'uuid' => $luta->uuid,
                    'ordem_na_card' => $luta->ordem_na_card,
                    'tipo_card' => $luta->tipo_card?->label(),
                    'participante_a' => $luta->participanteA?->nome,
                    'participante_b' => $luta->participanteB?->nome,
                    'categoria_peso' => $luta->categoriaPeso?->nome,
                    'numero_rounds' => $luta->numero_rounds,
                    'status' => $luta->status->label(),
                ]),
            ],
        ]);
    }

    public function edit(Evento $evento): Response
    {
        return Inertia::render('admin/eventos/Form', [
            'evento' => [
                ...$evento->only(['uuid', 'nome', 'organizacao_id', 'local', 'cidade', 'pais', 'link_canal_youtube']),
                // Formato aceito pelo <input type="datetime-local">.
                'data' => $evento->data->format('Y-m-d\TH:i'),
                'tipo_transmissao' => $evento->tipo_transmissao?->value,
                'status' => $evento->status->value,
            ],
            ...$this->opcoes(),
        ]);
    }

    public function update(EventoRequest $request, Evento $evento): RedirectResponse
    {
        $evento->update($request->validated());

        $this->sucesso('Evento atualizado.');

        return to_route('admin.eventos.show', $evento);
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        if ($evento->lutas()->exists()) {
            $this->erro('Este evento tem lutas cadastradas e não pode ser excluído.');

            return back();
        }

        $evento->delete();

        $this->sucesso('Evento excluído.');

        return to_route('admin.eventos.index');
    }

    /**
     * @return array{organizacoes: Collection<int, array{value: int, label: string}>, tiposTransmissao: list<array{value: string, label: string}>, status: list<array{value: string, label: string}>}
     */
    private function opcoes(): array
    {
        return [
            'organizacoes' => Organizacao::query()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (Organizacao $organizacao): array => ['value' => $organizacao->id, 'label' => $organizacao->nome]),
            'tiposTransmissao' => TipoTransmissao::opcoes(),
            'status' => StatusEvento::opcoes(),
        ];
    }
}
