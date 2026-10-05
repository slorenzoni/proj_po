<?php

namespace App\Http\Controllers\Site;

use App\Enums\EscopoRanking;
use App\Http\Controllers\Controller;
use App\Models\Palpite;
use App\Models\Ranking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Painel do cliente: resumo da pontuação e os palpites feitos.
 */
class PainelController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $ranking = Ranking::query()
            ->where('escopo', EscopoRanking::Geral)
            ->where('user_id', $user->id)
            ->first();

        return Inertia::render('Dashboard', [
            'resumo' => [
                'plano' => $user->plano()->label(),
                'cliente' => $user->isCliente(),
                'pontos' => $ranking->pontos ?? '0.00',
                'posicao' => $ranking?->posicao,
                'palpites' => $user->palpites()->count(),
                'palpites_perfeitos' => $ranking->palpites_perfeitos ?? 0,
            ],
            'palpites' => $user->palpites()
                ->with([
                    'luta.evento:id,nome,data',
                    'luta.participanteA:id,nome',
                    'luta.participanteB:id,nome',
                    'vencedorEscolhido:id,nome',
                ])
                ->latest()
                ->paginate(15)
                ->through(fn (Palpite $palpite): array => [
                    'uuid' => $palpite->uuid,
                    'luta_uuid' => $palpite->luta->uuid,
                    'luta' => "{$palpite->luta->participanteA?->nome} × {$palpite->luta->participanteB?->nome}",
                    'evento' => $palpite->luta->evento->nome,
                    'data' => $palpite->luta->evento->data->toIso8601String(),
                    'situacao' => $palpite->luta->status->label(),
                    'vencedor' => $palpite->vencedorEscolhido?->nome,
                    'metodo' => $palpite->metodo_escolhido?->label(),
                    'round' => $palpite->round_escolhido,
                    'peso_aplicado' => $palpite->peso_aplicado,
                    'pontos_obtidos' => $palpite->pontos_obtidos,
                ]),
        ]);
    }
}
