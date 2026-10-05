<?php

namespace App\Services;

use App\Exceptions\PalpiteNaoPermitidoException;
use App\Models\ConfiguracaoPontuacao;
use App\Models\Luta;
use App\Models\PlacarFan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Placar dos fãs (regras aprovadas em 30/09/2026): o usuário pontua cada round uma única
 * vez, pelo sistema 10-point must. A pontuação de um round abre quando ele termina e fecha
 * após o prazo configurado. Não se aplica a modalidades sem rounds.
 */
final class PlacarDosFans
{
    /**
     * Round que pode ser pontuado agora, ou nulo se nenhum estiver aberto.
     */
    public function roundAberto(Luta $luta): ?int
    {
        $round = $luta->ultimoRoundEncerrado();

        if ($round === null || $luta->round_encerrado_em === null) {
            return null;
        }

        $luta->loadMissing('categoria');

        $prazo = ConfiguracaoPontuacao::query()
            ->where(fn ($query) => $query->where('categoria_id', $luta->categoria_id)->orWhereNull('categoria_id'))
            ->orderByRaw('categoria_id is null')
            ->value('prazo_placar_fans_minutos');

        if (! is_int($prazo)) {
            return null;
        }

        return now()->lessThanOrEqualTo($luta->round_encerrado_em->addMinutes($prazo)) ? $round : null;
    }

    /**
     * @throws PalpiteNaoPermitidoException se o round não estiver aberto ou já tiver sido pontuado pelo usuário.
     */
    public function pontuar(User $user, Luta $luta, int $round, int $pontosAtletaA, int $pontosAtletaB): PlacarFan
    {
        if ($this->roundAberto($luta) !== $round) {
            throw new PalpiteNaoPermitidoException('Este round não está aberto para pontuação.');
        }

        $jaPontuou = PlacarFan::query()
            ->whereBelongsTo($luta)
            ->whereBelongsTo($user)
            ->where('round', $round)
            ->exists();

        if ($jaPontuou) {
            throw new PalpiteNaoPermitidoException('Você já pontuou este round.');
        }

        return PlacarFan::query()->create([
            'luta_id' => $luta->id,
            'user_id' => $user->id,
            'round' => $round,
            'pontos_atleta_a' => $pontosAtletaA,
            'pontos_atleta_b' => $pontosAtletaB,
        ]);
    }

    /**
     * Média da comunidade por round.
     *
     * @return Collection<int, array{round: int, media_a: float, media_b: float, votos: int}>
     */
    public function medias(Luta $luta): Collection
    {
        return PlacarFan::query()
            ->whereBelongsTo($luta)
            ->selectRaw('round, avg(pontos_atleta_a) as media_a, avg(pontos_atleta_b) as media_b, count(*) as votos')
            ->groupBy('round')
            ->orderBy('round')
            ->toBase()
            ->get()
            ->map(fn (object $linha): array => [
                'round' => (int) $linha->round,
                'media_a' => round((float) $linha->media_a, 1),
                'media_b' => round((float) $linha->media_b, 1),
                'votos' => (int) $linha->votos,
            ]);
    }
}
