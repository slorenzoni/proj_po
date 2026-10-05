<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\OrganizacaoRequest;
use App\Models\Organizacao;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizacaoController extends AdminController
{
    private const PASTA_LOGOS = 'organizacoes';

    public function __construct(private readonly MediaStorage $media) {}

    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/organizacoes/Index', [
            'filtros' => ['busca' => $busca],
            'organizacoes' => Organizacao::query()
                ->when($busca !== '', fn ($query) => $query->whereLike('nome', "%{$busca}%"))
                ->orderBy('nome')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Organizacao $organizacao): array => $this->dados($organizacao)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/organizacoes/Form', ['organizacao' => null]);
    }

    public function store(OrganizacaoRequest $request): RedirectResponse
    {
        $organizacao = new Organizacao($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            $organizacao->logo_url = $this->media->store($request->file('logo'), self::PASTA_LOGOS);
        }

        $organizacao->save();

        $this->sucesso('Organização criada.');

        return to_route('admin.organizacoes.index');
    }

    public function edit(Organizacao $organizacao): Response
    {
        return Inertia::render('admin/organizacoes/Form', ['organizacao' => $this->dados($organizacao)]);
    }

    public function update(OrganizacaoRequest $request, Organizacao $organizacao): RedirectResponse
    {
        $organizacao->fill($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            $organizacao->logo_url = $this->media->replace($organizacao->logo_url, $request->file('logo'), self::PASTA_LOGOS);
        }

        $organizacao->save();

        $this->sucesso('Organização atualizada.');

        return to_route('admin.organizacoes.index');
    }

    public function destroy(Organizacao $organizacao): RedirectResponse
    {
        if ($organizacao->eventos()->exists()) {
            $this->erro('Esta organização tem eventos cadastrados e não pode ser excluída.');

            return back();
        }

        $organizacao->delete();

        $this->sucesso('Organização excluída.');

        return to_route('admin.organizacoes.index');
    }

    /**
     * @return array{uuid: string, nome: string, pais_origem: string|null, logo: string|null}
     */
    private function dados(Organizacao $organizacao): array
    {
        return [
            'uuid' => $organizacao->uuid,
            'nome' => $organizacao->nome,
            'pais_origem' => $organizacao->pais_origem,
            'logo' => $this->media->url($organizacao->logo_url),
        ];
    }
}
