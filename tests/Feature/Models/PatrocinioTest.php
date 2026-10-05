<?php

use App\Enums\EscopoRanking;
use App\Enums\PosicaoBanner;
use App\Models\Banner;
use App\Models\Patrocinador;
use App\Models\Postagem;
use App\Models\Ranking;

test('a post is flagged as sponsored only while it has a sponsor', function () {
    $patrocinador = Patrocinador::factory()->create();
    $postagem = Postagem::factory()->create();

    expect($postagem->patrocinado)->toBeFalse();

    $postagem->update(['patrocinador_id' => $patrocinador->id]);

    expect($postagem->refresh()->patrocinado)->toBeTrue()
        ->and($patrocinador->postagens->sole()->is($postagem))->toBeTrue();

    $postagem->update(['patrocinador_id' => null]);

    expect($postagem->refresh()->patrocinado)->toBeFalse();
});

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
