<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\Periodicidade;
use App\Enums\PlanoAssinatura;
use App\Enums\StatusAssinatura;
use Database\Factories\AssinaturaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Assinatura do plano do cliente. O plano Free também gera registro
 * (valor 0, sem periodicidade, gateway nem próxima cobrança).
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property PlanoAssinatura $plano
 * @property Periodicidade|null $periodicidade
 * @property string|null $gateway
 * @property StatusAssinatura $status
 * @property string $valor
 * @property Carbon $data_inicio
 * @property Carbon|null $proxima_cobranca
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('assinaturas')]
#[Fillable([
    'user_id',
    'plano',
    'periodicidade',
    'gateway',
    'status',
    'valor',
    'data_inicio',
    'proxima_cobranca',
])]
class Assinatura extends Model
{
    /** @use HasFactory<AssinaturaFactory> */
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
            'plano' => PlanoAssinatura::class,
            'periodicidade' => Periodicidade::class,
            'status' => StatusAssinatura::class,
            'valor' => 'decimal:2',
            'data_inicio' => 'date',
            'proxima_cobranca' => 'date',
        ];
    }
}
