<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPatrocinador;
use App\Http\Requests\Admin\PatrocinadorRequest;
use App\Models\Patrocinador;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatrocinadorController extends AdminController
{
    private const PASTA_LOGOS = 'patrocinadores';

    public function __construct(private readonly MediaStorage $media) {}

    public function index(Request $request): Response
    {
        $busca = $this->busca($request);

        return Inertia::render('admin/patrocinadores/Index', [
            'filtros' => ['busca' => $busca],
            'patrocinadores' => Patrocinador::query()
                ->when($busca !== '', fn ($query) => $query->whereLike('nome', "%{$busca}%"))
                ->orderBy('nome')
                ->paginate(self::POR_PAGINA)
                ->withQueryString()
                ->through(fn (Patrocinador $patrocinador): array => [
                    'uuid' => $patrocinador->uuid,
                    'nome' => $patrocinador->nome,
                    'status' => $patrocinador->status->label(),
                    'data_fim_contrato' => $patrocinador->data_fim_contrato?->toDateString(),
                    'logo' => $this->media->url($patrocinador->logo_url),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/patrocinadores/Form', [
            'patrocinador' => null,
            'status' => StatusPatrocinador::opcoes(),
        ]);
    }

    public function store(PatrocinadorRequest $request): RedirectResponse
    {
        $patrocinador = new Patrocinador($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            $patrocinador->logo_url = $this->media->store($request->file('logo'), self::PASTA_LOGOS);
        }

        $patrocinador->save();

        $this->sucesso('Patrocinador criado.');

        return to_route('admin.patrocinadores.index');
    }

    public function edit(Patrocinador $patrocinador): Response
    {
        return Inertia::render('admin/patrocinadores/Form', [
            'patrocinador' => [
                ...$patrocinador->only(['uuid', 'nome', 'link_site', 'email_contato']),
                'status' => $patrocinador->status->value,
                'data_inicio_contrato' => $patrocinador->data_inicio_contrato?->toDateString(),
                'data_fim_contrato' => $patrocinador->data_fim_contrato?->toDateString(),
                'logo' => $this->media->url($patrocinador->logo_url),
            ],
            'status' => StatusPatrocinador::opcoes(),
        ]);
    }

    public function update(PatrocinadorRequest $request, Patrocinador $patrocinador): RedirectResponse
    {
        $patrocinador->fill($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            $patrocinador->logo_url = $this->media->replace($patrocinador->logo_url, $request->file('logo'), self::PASTA_LOGOS);
        }

        $patrocinador->save();

        $this->sucesso('Patrocinador atualizado.');

        return to_route('admin.patrocinadores.index');
    }

    public function destroy(Patrocinador $patrocinador): RedirectResponse
    {
        if ($patrocinador->banners()->exists() || $patrocinador->postagens()->exists()) {
            $this->erro('Este patrocinador tem banners ou postagens vinculados e não pode ser excluído.');

            return back();
        }

        $patrocinador->delete();

        $this->sucesso('Patrocinador excluído.');

        return to_route('admin.patrocinadores.index');
    }
}
