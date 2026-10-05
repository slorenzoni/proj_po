<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Pivot atletas × estilos_luta com id próprio, UUID, soft delete, auditoria e o
 * treinador do atleta naquele estilo.
 *
 * Atenção: o detach() do Laravel apaga o pivot fisicamente. Para desfazer o vínculo
 * preservando o histórico, faça soft delete do pivot.
 *
 * @property int $id
 * @property string $uuid
 * @property int $atleta_id
 * @property int $estilo_id
 * @property int|null $treinador_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('atleta_estilos')]
class AtletaEstilo extends Pivot
{
    use Auditable, HasPublicUuid, SoftDeletes;

    /**
     * O pivot tem PK auto-incremento própria (padrão do projeto).
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * @return BelongsTo<EstiloDeLuta, $this>
     */
    public function estilo(): BelongsTo
    {
        return $this->belongsTo(EstiloDeLuta::class, 'estilo_id');
    }

    /**
     * @return BelongsTo<Treinador, $this>
     */
    public function treinador(): BelongsTo
    {
        return $this->belongsTo(Treinador::class);
    }
}
