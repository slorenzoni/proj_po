<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModeloCobranca;
use App\Enums\PosicaoBanner;
use App\Enums\StatusBanner;
use App\Http\Requests\Admin\BannerRequest;
use App\Models\Banner;
use App\Models\Patrocinador;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends AdminController
{
    private const PASTA = 'banners';

    public function __construct(private readonly MediaStorage $media) {}

    public function index(): Response
    {
        return Inertia::render('admin/banners/Index', [
            'banners' => Banner::query()
                ->with('patrocinador:id,nome')
                ->orderBy('posicao')
                ->orderBy('ordem_exibicao')
                ->paginate(self::POR_PAGINA)
                ->through(fn (Banner $banner): array => [
                    'uuid' => $banner->uuid,
                    'imagem' => $this->media->url($banner->imagem_url),
                    'patrocinador' => $banner->patrocinador?->nome,
                    'posicao' => $banner->posicao->label(),
                    'status' => $banner->status->label(),
                    'periodo' => $banner->data_inicio->format('d/m/Y').' – '.($banner->data_fim?->format('d/m/Y') ?? 'sem fim'),
                    'impressoes' => $banner->impressoes,
                    'cliques' => $banner->cliques,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/banners/Form', ['banner' => null, ...$this->opcoes()]);
    }

    public function store(BannerRequest $request): RedirectResponse
    {
        $banner = new Banner($request->safe()->except('imagem'));
        $banner->imagem_url = $this->media->store($request->file('imagem'), self::PASTA);
        $banner->save();

        $this->sucesso('Banner criado.');

        return to_route('admin.banners.index');
    }

    public function edit(Banner $banner): Response
    {
        return Inertia::render('admin/banners/Form', [
            'banner' => [
                ...$banner->only(['uuid', 'patrocinador_id', 'link_destino', 'valor_contrato', 'ordem_exibicao', 'impressoes', 'cliques']),
                'posicao' => $banner->posicao->value,
                'status' => $banner->status->value,
                'modelo_cobranca' => $banner->modelo_cobranca->value,
                'data_inicio' => $banner->data_inicio->toDateString(),
                'data_fim' => $banner->data_fim?->toDateString(),
                'imagem' => $this->media->url($banner->imagem_url),
            ],
            ...$this->opcoes(),
        ]);
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner->fill($request->safe()->except('imagem'));

        if ($request->hasFile('imagem')) {
            $banner->imagem_url = $this->media->replace($banner->imagem_url, $request->file('imagem'), self::PASTA);
        }

        $banner->save();

        $this->sucesso('Banner atualizado.');

        return to_route('admin.banners.index');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        $this->sucesso('Banner excluído.');

        return to_route('admin.banners.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function opcoes(): array
    {
        return [
            'patrocinadores' => Patrocinador::query()->orderBy('nome')->get(['id', 'nome'])
                ->map(fn (Patrocinador $patrocinador): array => ['value' => $patrocinador->id, 'label' => $patrocinador->nome]),
            'posicoes' => PosicaoBanner::opcoes(),
            'status' => StatusBanner::opcoes(),
            'modelosCobranca' => ModeloCobranca::opcoes(),
        ];
    }
}
