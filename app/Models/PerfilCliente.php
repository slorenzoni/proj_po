<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\PerfilClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Dados exclusivos do papel Cliente (relação 1:1 com users).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string|null $foto_perfil_url
 * @property string|null $email_secundario
 * @property string|null $telefone
 * @property string|null $endereco
 * @property bool $maior_de_18
 * @property string|null $tipo
 * @property Carbon $data_cadastro
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('perfis_cliente')]
#[Fillable([
    'user_id',
    'foto_perfil_url',
    'email_secundario',
    'telefone',
    'endereco',
    'maior_de_18',
    'tipo',
    'data_cadastro',
])]
class PerfilCliente extends Model
{
    /** @use HasFactory<PerfilClienteFactory> */
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
            'maior_de_18' => 'boolean',
            'data_cadastro' => 'date',
        ];
    }
}
