<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CategoriaRequest;
use App\Models\Categoria;
use App\Models\CategoriaPeso;
use App\Models\Luta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends AdminController
{
    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/categorias/Index', [
            'filtros' => ['busca' => $busca],
            'categorias' => Categoria::query()
                ->withCount('categoriasPeso')
                ->when($busca !== '', fn ($query) => $query->whereLike('nome', "%{$busca}%"))
                ->orderBy('nome')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Categoria $categoria): array => [
                    'uuid' => $categoria->uuid,
                    'nome' => $categoria->nome,
                    'usa_rounds' => $categoria->usa_rounds,
                    'categorias_peso_count' => $categoria->getAttribute('categorias_peso_count'),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categorias/Form', ['categoria' => null]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        $categoria = Categoria::query()->create($request->validated());

        $this->sucesso('Categoria criada. Cadastre agora as categorias de peso.');

        return to_route('admin.categorias.edit', $categoria);
    }

    public function edit(Categoria $categoria): Response
    {
        return Inertia::render('admin/categorias/Form', [
            'categoria' => [
                'uuid' => $categoria->uuid,
                'nome' => $categoria->nome,
                'usa_rounds' => $categoria->usa_rounds,
                'pesos' => $categoria->categoriasPeso()
                    ->orderBy('peso_maximo_kg')
                    ->orderBy('nome')
                    ->get()
                    ->map(fn (CategoriaPeso $peso): array => [
                        'uuid' => $peso->uuid,
                        'nome' => $peso->nome,
                        'peso_minimo_kg' => $peso->peso_minimo_kg,
                        'peso_maximo_kg' => $peso->peso_maximo_kg,
                    ]),
            ],
        ]);
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        $this->sucesso('Categoria atualizada.');

        return to_route('admin.categorias.edit', $categoria);
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        $emUso = $categoria->categoriasPeso()->exists()
            || Luta::query()->whereBelongsTo($categoria)->exists();

        if ($emUso) {
            $this->erro('Esta categoria tem categorias de peso ou lutas vinculadas e não pode ser excluída.');

            return back();
        }

        $categoria->delete();

        $this->sucesso('Categoria excluída.');

        return to_route('admin.categorias.index');
    }
}
