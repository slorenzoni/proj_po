<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\AtletaFotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Foto do atleta (máximo de 3 por atleta, uma por posição em "ordem").
 *
 * @property int $id
 * @property string $uuid
 * @property int $atleta_id
 * @property string $foto_url Caminho do arquivo no disco de mídia, não a URL completa.
 * @property int $ordem
 * @property bool $principal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('atleta_fotos')]
#[Fillable(['atleta_id', 'foto_url', 'ordem', 'principal'])]
class AtletaFoto extends Model
{
    /** @use HasFactory<AtletaFotoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Atleta, $this>
     */
    public function atleta(): BelongsTo
    {
        return $this->belongsTo(Atleta::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
        ];
    }
}
