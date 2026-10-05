<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\StatusSolicitacaoVerificacao;
use Database\Factories\SolicitacaoVerificacaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Pedido de selo de verificado, avaliado por um administrador.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property string $documento_url Caminho do arquivo no disco de mídia, não a URL completa.
 * @property string|null $descricao
 * @property StatusSolicitacaoVerificacao $status
 * @property string|null $motivo_rejeicao
 * @property int|null $analisado_por_user_id
 * @property Carbon|null $analisado_em
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('solicitacoes_verificacao')]
#[Fillable([
    'user_id',
    'documento_url',
    'descricao',
    'status',
    'motivo_rejeicao',
    'analisado_por_user_id',
    'analisado_em',
])]
class SolicitacaoVerificacao extends Model
{
    /** @use HasFactory<SolicitacaoVerificacaoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * Quem pediu o selo.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Administrador que avaliou o pedido.
     *
     * @return BelongsTo<User, $this>
     */
    public function analisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por_user_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusSolicitacaoVerificacao::class,
            'analisado_em' => 'datetime',
        ];
    }
}
