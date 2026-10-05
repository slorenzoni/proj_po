<?php

namespace App\Http\Controllers\Site;

use App\Enums\PosicaoBanner;
use App\Enums\StatusEvento;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\ApresentaLutas;
use App\Models\Evento;
use App\Models\Luta;
use App\Services\ExibicaoDeBanners;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventoController extends Controller
{
    use ApresentaLutas;

    /**
     * Próximos eventos (padrão) ou já encerrados ("?quando=encerrados").
     */
    public function index(Request $request): Response
    {
        $encerrados = $request->query('quando') === 'encerrados';

        return Inertia::render('site/eventos/Index', [
            'quando' => $encerrados ? 'encerrados' : 'proximos',
            'eventos' => Evento::query()
                ->with('organizacao:id,nome')
                ->withCount('lutas')
                ->when(
                    $encerrados,
                    fn ($query) => $query->where('status', StatusEvento::Encerrado)->orderByDesc('data'),
                    fn ($query) => $query->where('status', '!=', StatusEvento::Encerrado)->orderBy('data'),
                )
                ->paginate(12)
                ->withQueryString()
                ->through(fn (Evento $evento): array => [
                    'uuid' => $evento->uuid,
                    'nome' => $evento->nome,
                    'organizacao' => $evento->organizacao?->nome,
                    'data' => $evento->data->toIso8601String(),
                    'local' => implode(' · ', array_filter([$evento->local, $evento->cidade, $evento->pais])),
                    'status' => ['value' => $evento->status->value, 'label' => $evento->status->label()],
                    'lutas_count' => $evento->getAttribute('lutas_count'),
                ]),
        ]);
    }

    public function show(Evento $evento, ExibicaoDeBanners $banners): Response
    {
        $evento->load(['organizacao:id,uuid,nome', 'lutas' => fn ($query) => $query->with(self::RELACOES_DA_LUTA)]);

        return Inertia::render('site/eventos/Show', [
            'evento' => [
                'uuid' => $evento->uuid,
                'nome' => $evento->nome,
                'organizacao' => $evento->organizacao === null
                    ? null
                    : ['uuid' => $evento->organizacao->uuid, 'nome' => $evento->organizacao->nome],
                'data' => $evento->data->toIso8601String(),
                'local' => implode(' · ', array_filter([$evento->local, $evento->cidade, $evento->pais])),
                'status' => ['value' => $evento->status->value, 'label' => $evento->status->label()],
                'tipo_transmissao' => $evento->tipo_transmissao?->label(),
                'link_youtube' => $evento->link_canal_youtube,
                'lutas' => $evento->lutas->map(fn (Luta $luta): array => $this->resumoDaLuta($luta)),
            ],
            'banners' => $banners->para(PosicaoBanner::Evento),
        ]);
    }
}
