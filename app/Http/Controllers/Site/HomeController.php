<?php

namespace App\Http\Controllers\Site;

use App\Enums\EscopoRanking;
use App\Enums\PosicaoBanner;
use App\Enums\StatusEvento;
use App\Enums\StatusPostagem;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ApresentaLutas;
use App\Models\Evento;
use App\Models\Postagem;
use App\Models\Ranking;
use App\Services\ExibicaoDeBanners;
use App\Services\MediaStorage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    use ApresentaLutas;

    public function __invoke(ExibicaoDeBanners $banners, MediaStorage $media): Response
    {
        $proximosEventos = Evento::query()
            ->with([
                'organizacao:id,nome',
                // Só a luta principal de cada evento entra na vitrine.
                'lutas' => fn ($query) => $query->where('ordem_na_card', 1)->with(self::RELACOES_DA_LUTA),
            ])
            ->whereIn('status', [StatusEvento::Agendado, StatusEvento::AoVivo])
            ->orderBy('data')
            ->limit(4)
            ->get();

        return Inertia::render('site/Home', [
            'eventos' => $proximosEventos->map(fn (Evento $evento): array => [
                'uuid' => $evento->uuid,
                'nome' => $evento->nome,
                'organizacao' => $evento->organizacao?->nome,
                'data' => $evento->data->toIso8601String(),
                'local' => $evento->local,
                'ao_vivo' => $evento->status === StatusEvento::AoVivo,
                'luta_principal' => $evento->lutas->first() === null ? null : $this->resumoDaLuta($evento->lutas->first()),
            ]),
            'ranking' => Ranking::query()
                ->with('user:id,name,verificado')
                ->where('escopo', EscopoRanking::Geral)
                ->orderBy('posicao')
                ->limit(5)
                ->get()
                ->map(fn (Ranking $linha): array => [
                    'posicao' => $linha->posicao,
                    'nome' => $linha->user?->name,
                    'verificado' => (bool) $linha->user?->verificado,
                    'pontos' => $linha->pontos,
                ]),
            'postagens' => Postagem::query()
                ->where('status', StatusPostagem::Publicado)
                ->whereDate('data_publicacao', '<=', today())
                ->latest('data_publicacao')
                ->limit(3)
                ->get()
                ->map(fn (Postagem $postagem): array => [
                    'slug' => $postagem->slug,
                    'titulo' => $postagem->titulo,
                    'resumo' => $postagem->meta_description,
                    'capa' => $media->url($postagem->imagem_capa),
                    'patrocinado' => $postagem->patrocinado,
                ]),
            'banners' => $banners->para(PosicaoBanner::Home),
        ]);
    }
}
