<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\EstiloDeLutaRequest;
use App\Models\EstiloDeLuta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstiloDeLutaController extends AdminController
{
    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/estilos/Index', [
            'filtros' => ['busca' => $busca],
            'estilos' => EstiloDeLuta::query()
                ->when($busca !== '', fn ($query) => $query->whereLike('nome', "%{$busca}%"))
                ->orderBy('nome')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (EstiloDeLuta $estilo): array => $this->dados($estilo)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/estilos/Form', ['estilo' => null]);
    }

    public function store(EstiloDeLutaRequest $request): RedirectResponse
    {
        EstiloDeLuta::query()->create($request->validated());

        $this->sucesso('Estilo de luta criado.');

        return to_route('admin.estilos.index');
    }

    public function edit(EstiloDeLuta $estilo): Response
    {
        return Inertia::render('admin/estilos/Form', ['estilo' => $this->dados($estilo)]);
    }

    public function update(EstiloDeLutaRequest $request, EstiloDeLuta $estilo): RedirectResponse
    {
        $estilo->update($request->validated());

        $this->sucesso('Estilo de luta atualizado.');

        return to_route('admin.estilos.index');
    }

    public function destroy(EstiloDeLuta $estilo): RedirectResponse
    {
        if ($estilo->atletas()->exists()) {
            $this->erro('Este estilo está vinculado a atletas e não pode ser excluído.');

            return back();
        }

        $estilo->delete();

        $this->sucesso('Estilo de luta excluído.');

        return to_route('admin.estilos.index');
    }

    /**
     * @return array{uuid: string, nome: string}
     */
    private function dados(EstiloDeLuta $estilo): array
    {
        return ['uuid' => $estilo->uuid, 'nome' => $estilo->nome];
    }
}
