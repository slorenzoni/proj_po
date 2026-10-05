<?php

namespace App\Services;

use App\Enums\PosicaoBanner;
use App\Enums\StatusBanner;
use App\Models\Banner;

/**
 * Banners a exibir numa posição do site. Entregar o banner conta como impressão.
 */
final class ExibicaoDeBanners
{
    public function __construct(private readonly MediaStorage $media) {}

    /**
     * Banners ativos e dentro do período de exibição, na ordem configurada.
     *
     * @return list<array{uuid: string, imagem: string|null, patrocinador: string|null, tem_link: bool}>
     */
    public function para(PosicaoBanner $posicao, int $limite = 1): array
    {
        $hoje = today()->toDateString();

        $banners = Banner::query()
            ->with('patrocinador:id,nome')
            ->where('posicao', $posicao)
            ->where('status', StatusBanner::Ativo)
            ->whereDate('data_inicio', '<=', $hoje)
            ->where(fn ($query) => $query->whereNull('data_fim')->orWhereDate('data_fim', '>=', $hoje))
            ->orderBy('ordem_exibicao')
            ->limit($limite)
            ->get();

        if ($banners->isNotEmpty()) {
            // Contador direto no banco: é métrica, não uma alteração do cadastro (não passa pela auditoria).
            Banner::query()->whereKey($banners->modelKeys())->toBase()->increment('impressoes');
        }

        return array_values($banners
            ->map(fn (Banner $banner): array => [
                'uuid' => $banner->uuid,
                'imagem' => $this->media->url($banner->imagem_url),
                'patrocinador' => $banner->patrocinador?->nome,
                'tem_link' => $banner->link_destino !== null,
            ])
            ->all());
    }

    /**
     * Registra o clique e devolve o destino, ou nulo se o banner não tiver link.
     */
    public function registrarClique(Banner $banner): ?string
    {
        if ($banner->link_destino === null) {
            return null;
        }

        Banner::query()->whereKey($banner->id)->toBase()->increment('cliques');

        return $banner->link_destino;
    }
}
