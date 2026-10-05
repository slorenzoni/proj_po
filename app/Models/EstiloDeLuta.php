<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\EstiloDeLutaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Estilo de luta praticado pelos atletas (ex.: Jiu-Jitsu, Muay Thai).
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('estilos_luta')]
#[Fillable(['nome'])]
class EstiloDeLuta extends Model
{
    /** @use HasFactory<EstiloDeLutaFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * Atletas com vínculo ativo neste estilo (pivots com soft delete são ignorados).
     *
     * @return BelongsToMany<Atleta, $this, AtletaEstilo>
     */
    public function atletas(): BelongsToMany
    {
        return $this->belongsToMany(Atleta::class, 'atleta_estilos', 'estilo_id', 'atleta_id')
            ->using(AtletaEstilo::class)
            ->withPivot('treinador_id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
