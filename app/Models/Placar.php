<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\PlacarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Placar oficial: pontuação de um juiz lateral num round (sistema 10-point must).
 * Não se aplica ao Judô.
 *
 * @property int $id
 * @property string $uuid
 * @property int $luta_id
 * @property int $juiz_id
 * @property int $round
 * @property int $pontos_atleta_a
 * @property int $pontos_atleta_b
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('placares')]
#[Fillable(['luta_id', 'juiz_id', 'round', 'pontos_atleta_a', 'pontos_atleta_b'])]
class Placar extends Model
{
    /** @use HasFactory<PlacarFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Luta, $this>
     */
    public function luta(): BelongsTo
    {
        return $this->belongsTo(Luta::class);
    }

    /**
     * @return BelongsTo<Juiz, $this>
     */
    public function juiz(): BelongsTo
    {
        return $this->belongsTo(Juiz::class);
    }
}
