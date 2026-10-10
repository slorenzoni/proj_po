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
 * @property string $documento_url Caminho do arquivo no disco PRIVADO (DISCO_DOCUMENTO), não uma URL.
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
     * Disco do comprovante enviado na verificação (pode ser documento de identidade).
     * PRIVADO de propósito (storage/app/private, fora da pasta pública): antes ia
     * para o disco de mídia, publicado em /storage — quem tivesse o nome do arquivo
     * abria o documento sem login (SEGURANCA.md, PG4, 10/10/2026). Só sai pela rota
     * protegida do painel (Admin\VerificacaoController::documento).
     */
    public const DISCO_DOCUMENTO = 'local';

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
