<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AtletaRequest;
use App\Models\Atleta;
use App\Models\AtletaEstilo;
use App\Models\AtletaFoto;
use App\Models\EstiloDeLuta;
use App\Models\Luta;
use App\Models\Treinador;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AtletaController extends AdminController
{
    public function __construct(private readonly MediaStorage $media) {}

    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/atletas/Index', [
            'filtros' => ['busca' => $busca],
            'atletas' => Atleta::query()
                ->when($busca !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->whereLike('nome', "%{$busca}%")
                    ->orWhereLike('apelido', "%{$busca}%")))
                ->orderBy('nome')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Atleta $atleta): array => [
                    'uuid' => $atleta->uuid,
                    'nome' => $atleta->nome,
                    'apelido' => $atleta->apelido,
                    'pais' => $atleta->pais,
                    'cartel' => "{$atleta->vitorias}-{$atleta->derrotas}-{$atleta->empates}",
                    'invicto' => $atleta->invicto,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/atletas/Form', ['atleta' => null]);
    }

    public function store(AtletaRequest $request): RedirectResponse
    {
        $atleta = Atleta::query()->create($request->atributos());

        $this->sucesso('Atleta criado. Adicione agora as fotos e os estilos de luta.');

        return to_route('admin.atletas.edit', $atleta);
    }

    public function edit(Atleta $atleta): Response
    {
        $atleta->load('user:id,email');

        return Inertia::render('admin/atletas/Form', [
            'atleta' => [
                ...$atleta->only([
                    'uuid', 'nome', 'apelido', 'tipo', 'equipe', 'pais', 'cidade_natal', 'altura_cm', 'peso_kg',
                    'alcance_cm', 'stance', 'biografia', 'vitorias', 'vitorias_ko', 'vitorias_submissao',
                    'vitorias_decisao', 'empates', 'derrotas', 'derrotas_ko', 'derrotas_submissao',
                    'derrotas_decisao', 'invicto', 'ranking',
                ]),
                'data_nascimento' => $atleta->data_nascimento?->toDateString(),
                'email_usuario' => $atleta->user?->email,
                'fotos' => $atleta->fotos->map(fn (AtletaFoto $foto): array => [
                    'uuid' => $foto->uuid,
                    'url' => $this->media->url($foto->foto_url),
                    'ordem' => $foto->ordem,
                    'principal' => $foto->principal,
                ]),
                'estilos' => AtletaEstilo::query()
                    ->where('atleta_id', $atleta->id)
                    ->with(['estilo:id,nome', 'treinador:id,nome'])
                    ->get()
                    ->map(fn (AtletaEstilo $vinculo): array => [
                        'uuid' => $vinculo->uuid,
                        'estilo' => $vinculo->estilo?->nome,
                        'treinador' => $vinculo->treinador?->nome,
                    ]),
            ],
            'limiteFotos' => AtletaFoto::LIMITE_POR_ATLETA,
            'estilos' => EstiloDeLuta::query()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (EstiloDeLuta $estilo): array => ['value' => $estilo->id, 'label' => $estilo->nome]),
            'treinadores' => Treinador::query()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (Treinador $treinador): array => ['value' => $treinador->id, 'label' => $treinador->nome]),
        ]);
    }

    public function update(AtletaRequest $request, Atleta $atleta): RedirectResponse
    {
        $atleta->update($request->atributos());

        $this->sucesso('Atleta atualizado.');

        return to_route('admin.atletas.edit', $atleta);
    }

    public function destroy(Atleta $atleta): RedirectResponse
    {
        $emLutas = Luta::query()
            ->where('participante_a_id', $atleta->id)
            ->orWhere('participante_b_id', $atleta->id)
            ->exists();

        if ($emLutas) {
            $this->erro('Este atleta participa de lutas cadastradas e não pode ser excluído.');

            return back();
        }

        $atleta->delete();

        $this->sucesso('Atleta excluído.');

        return to_route('admin.atletas.index');
    }
}
