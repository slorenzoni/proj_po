<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\TreinadorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Treinador de atletas. O vínculo com users só existe se o treinador tiver conta no sistema.
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property string $nome
 * @property string|null $pais
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('treinadores')]
#[Fillable(['user_id', 'nome', 'pais'])]
class Treinador extends Model
{
    /** @use HasFactory<TreinadorFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
