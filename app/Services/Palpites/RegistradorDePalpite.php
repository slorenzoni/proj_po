<?php

namespace App\Services\Palpites;

use App\Enums\MetodoPalpite;
use App\Enums\StatusLuta;
use App\Exceptions\PalpiteNaoPermitidoException;
use App\Models\Luta;
use App\Models\Palpite;
use App\Models\PesoTrocaPalpite;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cria e troca palpites respeitando a janela aprovada em 30/09/2026:
 *
 * - pré-luta (Free e Membro): até a luta começar, edição livre e peso cheio;
 * - ao vivo (só Membro): apenas nos intervalos entre rounds, com o peso do momento;
 * - durante o round, e depois que a luta termina ou é cancelada: bloqueado.
 *
 * O peso gravado é sempre o do momento da última troca — voltar ao palpite original
 * num intervalo não recupera o peso cheio. Cada troca fica registrada no histórico.
 */
final class RegistradorDePalpite
{
    /**
     * Peso da pré-luta quando não há grade cadastrada para a duração da luta
     * (inclusive modalidades sem rounds, que só têm pré-luta).
     */
    private const PESO_CHEIO = '100.00';

    public function janela(Luta $luta, User $user): JanelaDePalpite
    {
        if (! $user->isCliente()) {
            return JanelaDePalpite::fechada('Só contas de cliente podem palpitar.');
        }

        return match ($luta->status) {
            StatusLuta::Agendada => JanelaDePalpite::preLuta($this->pesoPara($luta, 0) ?? self::PESO_CHEIO),
            StatusLuta::EmAndamento => $this->janelaAoVivo($luta, $user),
            StatusLuta::Encerrada => JanelaDePalpite::fechada('A luta já terminou.'),
            StatusLuta::Cancelada => JanelaDePalpite::fechada('A luta foi cancelada.'),
        };
    }

    /**
     * @throws PalpiteNaoPermitidoException se a janela estiver fechada para o usuário.
     */
    public function registrar(User $user, Luta $luta, int $vencedorId, ?MetodoPalpite $metodo, ?int $round): Palpite
    {
        $janela = $this->janela($luta, $user);

        if (! $janela->aberta) {
            throw new PalpiteNaoPermitidoException((string) $janela->motivo);
        }

        $escolha = [
            'vencedor_escolhido_id' => $vencedorId,
            'metodo_escolhido' => $metodo,
            'round_escolhido' => $round,
        ];

        return DB::transaction(function () use ($user, $luta, $janela, $escolha): Palpite {
            $palpite = Palpite::query()
                ->whereBelongsTo($luta)
                ->whereBelongsTo($user)
                ->lockForUpdate()
                ->first();

            // Reenviar o mesmo palpite não é uma troca: não mexe no peso nem no histórico.
            if ($palpite !== null && $this->mesmaEscolha($palpite, $escolha)) {
                return $palpite;
            }

            $momento = ['round_da_troca' => $janela->roundDaTroca, 'peso_aplicado' => $janela->peso];

            if ($palpite === null) {
                $palpite = Palpite::query()->create(['luta_id' => $luta->id, 'user_id' => $user->id, ...$escolha, ...$momento]);
            } else {
                $palpite->update([...$escolha, ...$momento]);
            }

            $palpite->historicos()->create([...$escolha, 'round_da_troca' => $janela->roundDaTroca]);

            return $palpite;
        });
    }

    private function janelaAoVivo(Luta $luta, User $user): JanelaDePalpite
    {
        if (! $luta->em_intervalo || $luta->round_atual === null) {
            return JanelaDePalpite::fechada($user->isMembro()
                ? 'Palpites bloqueados durante o round. A troca abre no próximo intervalo.'
                : 'A luta já começou. Os palpites fecham no início da luta.');
        }

        if (! $user->isMembro()) {
            return JanelaDePalpite::fechada('A troca de palpite durante a luta é exclusiva do plano Membro.');
        }

        $peso = $this->pesoPara($luta, $luta->round_atual);

        return $peso === null
            ? JanelaDePalpite::fechada('A troca ao vivo não está disponível para esta luta.')
            : JanelaDePalpite::aoVivo($luta->round_atual, $peso);
    }

    /**
     * Peso configurado para o momento da troca, ou nulo se não houver grade para a luta.
     */
    private function pesoPara(Luta $luta, int $roundDaTroca): ?string
    {
        if ($luta->numero_rounds === null) {
            return null;
        }

        $luta->loadMissing('categoria');

        return PesoTrocaPalpite::gradePara($luta->categoria, $luta->numero_rounds)->get($roundDaTroca);
    }

    /**
     * @param  array{vencedor_escolhido_id: int, metodo_escolhido: MetodoPalpite|null, round_escolhido: int|null}  $escolha
     */
    private function mesmaEscolha(Palpite $palpite, array $escolha): bool
    {
        return $palpite->vencedor_escolhido_id === $escolha['vencedor_escolhido_id']
            && $palpite->metodo_escolhido === $escolha['metodo_escolhido']
            && $palpite->round_escolhido === $escolha['round_escolhido'];
    }
}
