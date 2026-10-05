<?php

namespace App\Services\Pontuacao;

use App\Enums\EscopoRanking;
use App\Enums\StatusLuta;
use App\Models\Luta;
use App\Models\Palpite;
use App\Models\Ranking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula o ranking materializado de um escopo a partir dos palpites já pontuados.
 *
 * Ordenação: mais pontos; em caso de empate, mais palpites perfeitos, depois mais
 * vencedores corretos e, por fim, quem palpitou primeiro. O ranking geral considera
 * todo o histórico.
 *
 * Pode ser executado mais de uma vez: reconstrói o escopo inteiro.
 */
final class AtualizadorDeRanking
{
    public function __construct(private readonly PontuadorDeLuta $pontuador) {}

    /**
     * Atualiza os três rankings afetados pelo resultado de uma luta.
     */
    public function recalcularParaLuta(Luta $luta): void
    {
        $luta->loadMissing('evento');

        $this->recalcular(EscopoRanking::Geral);
        $this->recalcular(EscopoRanking::Evento, $luta->evento_id);
        $this->recalcular(EscopoRanking::Organizacao, $luta->evento->organizacao_id);
    }

    public function recalcular(EscopoRanking $escopo, ?int $referenciaId = null): void
    {
        $linhas = $this->classificacao($escopo, $referenciaId);

        DB::transaction(function () use ($escopo, $referenciaId, $linhas): void {
            $existentes = Ranking::query()
                ->where('escopo', $escopo)
                ->where('referencia_id', $referenciaId)
                ->get()
                ->keyBy('user_id');

            foreach ($linhas as $posicao => $linha) {
                $dados = [
                    'pontos' => $linha['pontos'],
                    'palpites_perfeitos' => $linha['palpites_perfeitos'],
                    'vencedores_corretos' => $linha['vencedores_corretos'],
                    'posicao' => $posicao + 1,
                ];

                $ranking = $existentes->pull($linha['user_id']);

                $ranking === null
                    ? Ranking::query()->create([
                        'escopo' => $escopo,
                        'referencia_id' => $referenciaId,
                        'user_id' => $linha['user_id'],
                        ...$dados,
                    ])
                    : $ranking->update($dados);
            }

            // Quem não tem mais palpite pontuado no escopo sai do ranking.
            $existentes->each(fn (Ranking $ranking) => $ranking->delete());
        });
    }

    /**
     * Totais por usuário no escopo, já na ordem do ranking.
     *
     * @return list<array{user_id: int, pontos: float, palpites_perfeitos: int, vencedores_corretos: int, primeiro_palpite: string}>
     */
    private function classificacao(EscopoRanking $escopo, ?int $referenciaId): array
    {
        /** @var array<int, array{user_id: int, pontos: float, palpites_perfeitos: int, vencedores_corretos: int, primeiro_palpite: string}> $totais */
        $totais = [];

        Palpite::query()
            ->whereNotNull('pontos_obtidos')
            ->whereHas('luta', fn (Builder $query) => $this->filtrarLutas($query, $escopo, $referenciaId))
            ->with('luta')
            ->lazyById()
            ->each(function (Palpite $palpite) use (&$totais): void {
                $acertos = $this->pontuador->acertos($palpite, $palpite->luta);
                $criadoEm = (string) $palpite->created_at?->toDateTimeString();

                $total = $totais[$palpite->user_id] ?? [
                    'user_id' => $palpite->user_id,
                    'pontos' => 0.0,
                    'palpites_perfeitos' => 0,
                    'vencedores_corretos' => 0,
                    'primeiro_palpite' => $criadoEm,
                ];

                $total['pontos'] += (float) $palpite->pontos_obtidos;
                $total['palpites_perfeitos'] += (int) $acertos->perfeito();
                $total['vencedores_corretos'] += (int) $acertos->vencedor;
                $total['primeiro_palpite'] = min($total['primeiro_palpite'], $criadoEm);

                $totais[$palpite->user_id] = $total;
            });

        $linhas = array_values($totais);

        usort($linhas, fn (array $a, array $b): int => [$b['pontos'], $b['palpites_perfeitos'], $b['vencedores_corretos'], $a['primeiro_palpite']]
            <=> [$a['pontos'], $a['palpites_perfeitos'], $a['vencedores_corretos'], $b['primeiro_palpite']]);

        return $linhas;
    }

    /**
     * @param  Builder<Luta>  $query
     */
    private function filtrarLutas(Builder $query, EscopoRanking $escopo, ?int $referenciaId): void
    {
        $query->where('status', StatusLuta::Encerrada);

        match ($escopo) {
            EscopoRanking::Geral => null,
            EscopoRanking::Evento => $query->where('evento_id', $referenciaId),
            EscopoRanking::Organizacao => $query->whereHas(
                'evento',
                fn (Builder $evento) => $evento->where('organizacao_id', $referenciaId),
            ),
        };
    }
}
