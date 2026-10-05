<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\OrganizacaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Organização promotora de eventos (ex.: UFC, Bellator).
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property string|null $logo_url Caminho do arquivo no disco de mídia, não a URL completa.
 * @property string|null $pais_origem
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('organizacoes')]
#[Fillable(['nome', 'logo_url', 'pais_origem'])]
class Organizacao extends Model
{
    /** @use HasFactory<OrganizacaoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return HasMany<Evento, $this>
     */
    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }
}
