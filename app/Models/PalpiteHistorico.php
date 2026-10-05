<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\MetodoPalpite;
use Database\Factories\PalpiteHistoricoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Registro de cada troca de palpite, para auditoria e contestação.
 * A data da troca é o "created_at".
 *
 * @property int $id
 * @property string $uuid
 * @property int $palpite_id
 * @property int $vencedor_escolhido_id
 * @property MetodoPalpite|null $metodo_escolhido
 * @property int|null $round_escolhido
 * @property int $round_da_troca 0 = pré-luta; N = intervalo após o round N.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('palpite_historicos')]
#[Fillable([
    'palpite_id',
    'vencedor_escolhido_id',
    'metodo_escolhido',
    'round_escolhido',
    'round_da_troca',
])]
class PalpiteHistorico extends Model
{
    /** @use HasFactory<PalpiteHistoricoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Palpite, $this>
     */
    public function palpite(): BelongsTo
    {
        return $this->belongsTo(Palpite::class);
    }

    /**
     * @return BelongsTo<Atleta, $this>
     */
    public function vencedorEscolhido(): BelongsTo
    {
        return $this->belongsTo(Atleta::class, 'vencedor_escolhido_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metodo_escolhido' => MetodoPalpite::class,
        ];
    }
}
