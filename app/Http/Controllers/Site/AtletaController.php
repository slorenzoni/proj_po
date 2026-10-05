<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Atleta;
use App\Models\AtletaEstilo;
use App\Models\AtletaFoto;
use App\Models\Luta;
use App\Services\MediaStorage;
use Inertia\Inertia;
use Inertia\Response;

class AtletaController extends Controller
{
    /**
     * Lutas exibidas no perfil.
     */
    private const LIMITE_DE_LUTAS = 20;

    public function show(Atleta $atleta, MediaStorage $media): Response
    {
        $lutas = Luta::query()
            ->with(['evento:id,uuid,nome,data', 'participanteA:id,nome', 'participanteB:id,nome'])
            ->where(fn ($query) => $query
                ->where('participante_a_id', $atleta->id)
                ->orWhere('participante_b_id', $atleta->id))
            ->latest('id')
            ->limit(self::LIMITE_DE_LUTAS)
            ->get();

        return Inertia::render('site/atletas/Show', [
            'atleta' => [
                ...$atleta->only([
                    'uuid', 'nome', 'apelido', 'equipe', 'pais', 'cidade_natal', 'altura_cm', 'peso_kg', 'alcance_cm',
                    'stance', 'biografia', 'vitorias', 'vitorias_ko', 'vitorias_submissao', 'vitorias_decisao',
                    'empates', 'derrotas', 'derrotas_ko', 'derrotas_submissao', 'derrotas_decisao', 'invicto', 'ranking',
                ]),
                'idade' => $atleta->data_nascimento?->age,
                'fotos' => $atleta->fotos->map(fn (AtletaFoto $foto): array => [
                    'url' => $media->url($foto->foto_url),
                    'principal' => $foto->principal,
                ]),
                'estilos' => AtletaEstilo::query()
                    ->where('atleta_id', $atleta->id)
                    ->with(['estilo:id,nome', 'treinador:id,nome'])
                    ->get()
                    ->map(fn (AtletaEstilo $vinculo): array => [
                        'estilo' => $vinculo->estilo?->nome,
                        'treinador' => $vinculo->treinador?->nome,
                    ]),
            ],
            'lutas' => $lutas->map(function (Luta $luta) use ($atleta): array {
                $adversario = $luta->participante_a_id === $atleta->id ? $luta->participanteB : $luta->participanteA;

                return [
                    'uuid' => $luta->uuid,
                    'evento' => $luta->evento->nome,
                    'data' => $luta->evento->data->toIso8601String(),
                    'adversario' => $adversario?->nome,
                    'status' => $luta->status->label(),
                    // Nulo enquanto a luta não tem resultado ou quando terminou sem vencedor.
                    'venceu' => $luta->vencedor_id === null ? null : $luta->vencedor_id === $atleta->id,
                    'metodo_vitoria' => $luta->metodo_vitoria?->label(),
                ];
            }),
        ]);
    }
}
