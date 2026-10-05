<?php

namespace App\Http\Controllers\Site;

use App\Enums\EscopoRanking;
use App\Enums\StatusEvento;
use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Organizacao;
use App\Models\Ranking;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ranking dos palpites: geral, por evento e por organização. Os dados vêm da tabela
 * materializada, atualizada a cada luta encerrada.
 */
class RankingController extends Controller
{
    public function geral(Request $request): Response
    {
        return $this->pagina($request, EscopoRanking::Geral, null, 'Ranking geral');
    }

    public function evento(Request $request, Evento $evento): Response
    {
        return $this->pagina($request, EscopoRanking::Evento, $evento->id, $evento->nome);
    }

    public function organizacao(Request $request, Organizacao $organizacao): Response
    {
        return $this->pagina($request, EscopoRanking::Organizacao, $organizacao->id, $organizacao->nome);
    }

    private function pagina(Request $request, EscopoRanking $escopo, ?int $referenciaId, string $titulo): Response
    {
        $doEscopo = fn () => Ranking::query()->where('escopo', $escopo)->where('referencia_id', $referenciaId);

        $minha = $request->user() === null
            ? null
            : $doEscopo()->where('user_id', $request->user()->id)->first();

        return Inertia::render('site/ranking/Index', [
            'escopo' => ['tipo' => $escopo->value, 'titulo' => $titulo],
            'linhas' => $doEscopo()
                ->with('user:id,name,verificado')
                ->orderBy('posicao')
                ->paginate(50)
                ->through(fn (Ranking $linha): array => [
                    'uuid' => $linha->uuid,
                    'posicao' => $linha->posicao,
                    'nome' => $linha->user?->name,
                    'verificado' => (bool) $linha->user?->verificado,
                    'pontos' => $linha->pontos,
                    'palpites_perfeitos' => $linha->palpites_perfeitos,
                    'vencedores_corretos' => $linha->vencedores_corretos,
                    'sou_eu' => $linha->user_id === $request->user()?->id,
                ]),
            'minhaPosicao' => $minha === null ? null : ['posicao' => $minha->posicao, 'pontos' => $minha->pontos],
            'organizacoes' => Organizacao::query()
                ->whereHas('eventos')
                ->orderBy('nome')
                ->get(['uuid', 'nome'])
                ->map(fn (Organizacao $organizacao): array => ['uuid' => $organizacao->uuid, 'nome' => $organizacao->nome]),
            'eventos' => Evento::query()
                ->where('status', StatusEvento::Encerrado)
                ->orderByDesc('data')
                ->limit(10)
                ->get(['uuid', 'nome'])
                ->map(fn (Evento $evento): array => ['uuid' => $evento->uuid, 'nome' => $evento->nome]),
        ]);
    }
}
