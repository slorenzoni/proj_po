<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\PlacarFanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Placar dos fãs: pontuação que um usuário deu a um round (sistema 10-point must).
 * Cada usuário pontua cada round uma única vez. Não se aplica ao Judô.
 *
 * @property int $id
 * @property string $uuid
 * @property int $luta_id
 * @property int $user_id
 * @property int $round
 * @property int $pontos_atleta_a
 * @property int $pontos_atleta_b
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('placar_fans')]
#[Fillable(['luta_id', 'user_id', 'round', 'pontos_atleta_a', 'pontos_atleta_b'])]
class PlacarFan extends Model
{
    /** @use HasFactory<PlacarFanFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Luta, $this>
     */
    public function luta(): BelongsTo
    {
        return $this->belongsTo(Luta::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
