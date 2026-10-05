<?php

namespace App\Services;

use App\Enums\MetodoVitoria;
use App\Enums\StatusLuta;
use App\Exceptions\AndamentoInvalidoException;
use App\Jobs\ProcessarResultadoDaLuta;
use App\Models\Atleta;
use App\Models\Luta;
use Illuminate\Support\Facades\DB;

/**
 * Andamento da luta, informado manualmente pelo administrador (decisão de 01/10/2026):
 *
 *   agendada → em andamento (round 1) → intervalo → round 2 → ... → encerrada
 *
 * "round_atual" e "em_intervalo" são o que o servidor consulta para liberar a troca do
 * palpite ao vivo, que só acontece nos intervalos. Modalidades sem rounds (ex.: Judô)
 * vão direto de "em andamento" para "encerrada".
 */
final class AndamentoLuta
{
    /**
     * O que o administrador pode fazer agora — usado pela tela para exibir só as ações válidas.
     *
     * @return array{iniciar: bool, encerrarRound: bool, iniciarProximoRound: bool, encerrar: bool, cancelar: bool}
     */
    public function acoesDisponiveis(Luta $luta): array
    {
        $emAndamento = $luta->status === StatusLuta::EmAndamento;

        return [
            'iniciar' => $luta->status === StatusLuta::Agendada,
            'encerrarRound' => $emAndamento && $this->podeEncerrarRound($luta),
            'iniciarProximoRound' => $emAndamento && $luta->em_intervalo,
            'encerrar' => $emAndamento,
            'cancelar' => in_array($luta->status, [StatusLuta::Agendada, StatusLuta::EmAndamento], true),
        ];
    }

    public function iniciar(Luta $luta): void
    {
        $this->exigir($this->acoesDisponiveis($luta)['iniciar'], 'Só é possível iniciar uma luta agendada.');

        $luta->update([
            'status' => StatusLuta::EmAndamento,
            'round_atual' => $luta->numero_rounds === null ? null : 1,
            'em_intervalo' => false,
        ]);
    }

    /**
     * Fim do round atual: abre o intervalo, em que o Membro pode trocar o palpite.
     */
    public function encerrarRound(Luta $luta): void
    {
        $this->exigir(
            $this->acoesDisponiveis($luta)['encerrarRound'],
            'Não há round em andamento para encerrar. Após o último round, encerre a luta.',
        );

        $luta->update(['em_intervalo' => true]);
    }

    public function iniciarProximoRound(Luta $luta): void
    {
        $this->exigir(
            $this->acoesDisponiveis($luta)['iniciarProximoRound'],
            'Só é possível iniciar o próximo round durante um intervalo.',
        );

        $luta->update([
            'round_atual' => (int) $luta->round_atual + 1,
            'em_intervalo' => false,
        ]);
    }

    /**
     * Encerra com o resultado oficial. Empate e "sem resultado" não têm vencedor.
     */
    public function encerrar(
        Luta $luta,
        MetodoVitoria $metodo,
        ?Atleta $vencedor = null,
        ?int $roundFim = null,
        ?string $tempoFim = null,
    ): void {
        $this->exigir($this->acoesDisponiveis($luta)['encerrar'], 'Só é possível encerrar uma luta em andamento.');

        if ($metodo->temVencedor()) {
            $this->exigir(
                $vencedor !== null && in_array($vencedor->id, [$luta->participante_a_id, $luta->participante_b_id], true),
                'O vencedor precisa ser um dos dois participantes da luta.',
            );
        }

        DB::transaction(function () use ($luta, $metodo, $vencedor, $roundFim, $tempoFim): void {
            $luta->update([
                'status' => StatusLuta::Encerrada,
                'em_intervalo' => false,
                'vencedor_id' => $metodo->temVencedor() ? $vencedor?->id : null,
                'metodo_vitoria' => $metodo,
                'round_fim' => $roundFim,
                'tempo_fim' => $tempoFim,
            ]);

            // No mesmo passo do encerramento: como a luta só encerra uma vez, o cartel só soma uma vez.
            $this->atualizarCartel($luta, $metodo);
        });

        // Pontuação e ranking podem ser demorados: ficam para a fila.
        ProcessarResultadoDaLuta::dispatch($luta);
    }

    public function cancelar(Luta $luta): void
    {
        $this->exigir(
            $this->acoesDisponiveis($luta)['cancelar'],
            'Só é possível cancelar lutas agendadas ou em andamento.',
        );

        DB::transaction(function () use ($luta): void {
            $luta->update(['status' => StatusLuta::Cancelada, 'em_intervalo' => false]);

            // Regra aprovada em 30/09/2026: luta cancelada descarta os palpites, sem pontuação.
            $luta->descartarPalpites();
        });
    }

    /**
     * Soma o resultado ao cartel dos dois atletas. "Sem resultado" não altera o cartel.
     */
    private function atualizarCartel(Luta $luta, MetodoVitoria $metodo): void
    {
        $participantes = [$luta->participanteA, $luta->participanteB];

        if ($metodo === MetodoVitoria::Empate) {
            foreach ($participantes as $atleta) {
                $atleta->registrarEmpate();
            }

            return;
        }

        if (! $metodo->temVencedor()) {
            return;
        }

        foreach ($participantes as $atleta) {
            $atleta->is($luta->vencedor)
                ? $atleta->registrarVitoria($metodo)
                : $atleta->registrarDerrota($metodo);
        }
    }

    /**
     * Há intervalo depois de todo round, menos do último.
     */
    private function podeEncerrarRound(Luta $luta): bool
    {
        return $luta->numero_rounds !== null
            && $luta->round_atual !== null
            && ! $luta->em_intervalo
            && $luta->round_atual < $luta->numero_rounds;
    }

    private function exigir(bool $condicao, string $mensagem): void
    {
        if (! $condicao) {
            throw new AndamentoInvalidoException($mensagem);
        }
    }
}
