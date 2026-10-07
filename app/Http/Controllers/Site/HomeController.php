<?php

namespace App\Http\Controllers\Site;

use App\Enums\EscopoRanking;
use App\Enums\PosicaoBanner;
use App\Enums\StatusEvento;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ApresentaLutas;
use App\Models\Evento;
use App\Models\Ranking;
use App\Services\ExibicaoDeBanners;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    use ApresentaLutas;

    public function __invoke(ExibicaoDeBanners $banners): Response
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
            'banners' => $banners->para(PosicaoBanner::Home),
        ]);
    }
}
