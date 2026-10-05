<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPostagem;
use App\Http\Requests\Admin\PostagemRequest;
use App\Models\Categoria;
use App\Models\Patrocinador;
use App\Models\Postagem;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostagemController extends AdminController
{
    private const PASTA_CAPAS = 'postagens';

    public function __construct(private readonly MediaStorage $media) {}

    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/postagens/Index', [
            'filtros' => ['busca' => $busca],
            'postagens' => Postagem::query()
                ->with('user:id,name')
                ->when($busca !== '', fn ($query) => $query->whereLike('titulo', "%{$busca}%"))
                ->latest()
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Postagem $postagem): array => [
                    'uuid' => $postagem->uuid,
                    'titulo' => $postagem->titulo,
                    'autor' => $postagem->user?->name,
                    'status' => $postagem->status->label(),
                    'patrocinado' => $postagem->patrocinado,
                    'data_publicacao' => $postagem->data_publicacao?->toDateString(),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/postagens/Form', ['postagem' => null, ...$this->opcoes()]);
    }

    public function store(PostagemRequest $request): RedirectResponse
    {
        $postagem = new Postagem($request->atributos());
        // O autor é sempre o administrador logado.
        $postagem->user_id = (int) $request->user()?->id;

        if ($request->hasFile('capa')) {
            $postagem->imagem_capa = $this->media->store($request->file('capa'), self::PASTA_CAPAS);
        }

        $postagem->save();

        $this->sucesso('Postagem criada.');

        return to_route('admin.postagens.index');
    }

    public function edit(Postagem $postagem): Response
    {
        return Inertia::render('admin/postagens/Form', [
            'postagem' => [
                ...$postagem->only([
                    'uuid', 'titulo', 'slug', 'conteudo', 'meta_description', 'patrocinador_id',
                    'fonte_original_url', 'categoria_id',
                ]),
                'status' => $postagem->status->value,
                'data_publicacao' => $postagem->data_publicacao?->toDateString(),
                'capa' => $this->media->url($postagem->imagem_capa),
            ],
            ...$this->opcoes(),
        ]);
    }

    public function update(PostagemRequest $request, Postagem $postagem): RedirectResponse
    {
        $postagem->fill($request->atributos());

        if ($request->hasFile('capa')) {
            $postagem->imagem_capa = $this->media->replace($postagem->imagem_capa, $request->file('capa'), self::PASTA_CAPAS);
        }

        $postagem->save();

        $this->sucesso('Postagem atualizada.');

        return to_route('admin.postagens.index');
    }

    public function destroy(Postagem $postagem): RedirectResponse
    {
        $postagem->delete();

        $this->sucesso('Postagem excluída.');

        return to_route('admin.postagens.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoes(): array
    {
        return [
            'patrocinadores' => Patrocinador::query()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (Patrocinador $patrocinador): array => ['value' => $patrocinador->id, 'label' => $patrocinador->nome]),
            'categorias' => Categoria::query()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (Categoria $categoria): array => ['value' => $categoria->id, 'label' => $categoria->nome]),
            'status' => StatusPostagem::opcoes(),
        ];
    }
}
