<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\PapelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Catálogo de papéis "só-permissão" (ex.: Comentarista). Novo papel = nova linha, sem migration.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property string|null $descricao
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('papeis')]
#[Fillable(['nome', 'descricao'])]
class Papel extends Model
{
    /** @use HasFactory<PapelFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    public const COMENTARISTA = 'Comentarista';

    /**
     * @return BelongsToMany<User, $this, UserPapel>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'papel_user')
            ->using(UserPapel::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}
