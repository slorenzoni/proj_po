<?php

namespace App\Http\Controllers\Site\Concerns;

use App\Models\Atleta;
use App\Models\Luta;
use App\Services\MediaStorage;

/**
 * Formato com que atletas e lutas chegam às páginas do site. As relações usadas
 * (participantes com foto principal, categoria de peso, vencedor) devem vir carregadas.
 */
trait ApresentaLutas
{
    /**
     * Relações que resumoDaLuta() espera carregadas.
     *
     * @var list<string>
     */
    protected const RELACOES_DA_LUTA = [
        'participanteA.fotoPrincipal',
        'participanteB.fotoPrincipal',
        'categoriaPeso:id,nome',
    ];

    /**
     * @return array{id: int, uuid: string, nome: string, apelido: string|null, pais: string|null, foto: string|null, cartel: string}
     */
    protected function resumoDoAtleta(Atleta $atleta): array
    {
        return [
            'id' => $atleta->id,
            'uuid' => $atleta->uuid,
            'nome' => $atleta->nome,
            'apelido' => $atleta->apelido,
            'pais' => $atleta->pais,
            'foto' => app(MediaStorage::class)->url($atleta->fotoPrincipal?->foto_url),
            'cartel' => "{$atleta->vitorias}-{$atleta->derrotas}-{$atleta->empates}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resumoDaLuta(Luta $luta): array
    {
        return [
            'uuid' => $luta->uuid,
            'ordem_na_card' => $luta->ordem_na_card,
            'tipo_card' => $luta->tipo_card?->label(),
            'categoria_peso' => $luta->categoriaPeso?->nome,
            'numero_rounds' => $luta->numero_rounds,
            'status' => ['value' => $luta->status->value, 'label' => $luta->status->label()],
            'participante_a' => $this->resumoDoAtleta($luta->participanteA),
            'participante_b' => $this->resumoDoAtleta($luta->participanteB),
            'vencedor_id' => $luta->vencedor_id,
            'metodo_vitoria' => $luta->metodo_vitoria?->label(),
            'round_fim' => $luta->round_fim,
        ];
    }
}
