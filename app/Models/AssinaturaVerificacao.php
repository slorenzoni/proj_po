<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\Periodicidade;
use App\Enums\StatusAssinatura;
use Database\Factories\AssinaturaVerificacaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Cobrança recorrente do selo de verificado, originada de uma solicitação aprovada.
 * É cobrada à parte da assinatura do plano.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $solicitacao_verificacao_id
 * @property Periodicidade $periodicidade
 * @property string $gateway
 * @property string $valor
 * @property StatusAssinatura $status
 * @property Carbon $data_inicio
 * @property Carbon|null $proxima_cobranca
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('assinaturas_verificacao')]
#[Fillable([
    'user_id',
    'solicitacao_verificacao_id',
    'periodicidade',
    'gateway',
    'valor',
    'status',
    'data_inicio',
    'proxima_cobranca',
])]
class AssinaturaVerificacao extends Model
{
    /** @use HasFactory<AssinaturaVerificacaoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SolicitacaoVerificacao, $this>
     */
    public function solicitacaoVerificacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoVerificacao::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periodicidade' => Periodicidade::class,
            'valor' => 'decimal:2',
            'status' => StatusAssinatura::class,
            'data_inicio' => 'date',
            'proxima_cobranca' => 'date',
        ];
    }
}
