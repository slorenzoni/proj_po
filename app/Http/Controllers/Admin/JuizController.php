<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\JuizRequest;
use App\Models\Juiz;
use App\Models\Placar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JuizController extends AdminController
{
    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/juizes/Index', [
            'filtros' => ['busca' => $busca],
            'juizes' => Juiz::query()
                ->when($busca !== '', fn ($query) => $query->whereLike('nome', "%{$busca}%"))
                ->orderBy('nome')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Juiz $juiz): array => $this->dados($juiz)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/juizes/Form', ['juiz' => null]);
    }

    public function store(JuizRequest $request): RedirectResponse
    {
        Juiz::query()->create($request->validated());

        $this->sucesso('Juiz criado.');

        return to_route('admin.juizes.index');
    }

    public function edit(Juiz $juiz): Response
    {
        return Inertia::render('admin/juizes/Form', ['juiz' => $this->dados($juiz)]);
    }

    public function update(JuizRequest $request, Juiz $juiz): RedirectResponse
    {
        $juiz->update($request->validated());

        $this->sucesso('Juiz atualizado.');

        return to_route('admin.juizes.index');
    }

    public function destroy(Juiz $juiz): RedirectResponse
    {
        if ($juiz->lutas()->exists() || Placar::query()->whereBelongsTo($juiz)->exists()) {
            $this->erro('Este juiz está escalado em lutas ou tem placares lançados e não pode ser excluído.');

            return back();
        }

        $juiz->delete();

        $this->sucesso('Juiz excluído.');

        return to_route('admin.juizes.index');
    }

    /**
     * @return array{uuid: string, nome: string, pais: string|null, certificado_por: string|null}
     */
    private function dados(Juiz $juiz): array
    {
        return [
            'uuid' => $juiz->uuid,
            'nome' => $juiz->nome,
            'pais' => $juiz->pais,
            'certificado_por' => $juiz->certificado_por,
        ];
    }
}
