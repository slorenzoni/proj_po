<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\NivelAcesso;
use Database\Factories\PerfilAdministradorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Dados exclusivos do papel Administrador (relação 1:1 com users).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property NivelAcesso $nivel_acesso
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('perfis_administrador')]
#[Fillable(['user_id', 'nivel_acesso'])]
class PerfilAdministrador extends Model
{
    /** @use HasFactory<PerfilAdministradorFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nivel_acesso' => NivelAcesso::class,
        ];
    }
}
