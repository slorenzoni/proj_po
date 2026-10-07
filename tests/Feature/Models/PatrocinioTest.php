<?php

use App\Enums\EscopoRanking;
use App\Enums\PosicaoBanner;
use App\Models\Banner;
use App\Models\Ranking;

test('a banner starts without metrics and belongs to its sponsor', function () {
    $banner = Banner::factory()->create()->refresh();

    expect($banner->impressoes)->toBe(0)
        ->and($banner->cliques)->toBe(0)
        ->and($banner->posicao)->toBe(PosicaoBanner::Home)
        ->and($banner->patrocinador->banners->sole()->is($banner))->toBeTrue();
});

test('a general ranking row has no reference and starts without points', function () {
    $ranking = Ranking::factory()->create()->refresh();

    expect($ranking->escopo)->toBe(EscopoRanking::Geral)
        ->and($ranking->referencia_id)->toBeNull()
        ->and($ranking->pontos)->toBe('0.00')
        ->and($ranking->palpites_perfeitos)->toBe(0);
});
