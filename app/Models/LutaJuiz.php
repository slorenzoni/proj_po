<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\FuncaoJuiz;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Pivot lutas × juizes com id próprio, UUID, soft delete, auditoria e a função do juiz na luta.
 *
 * Atenção: o detach() do Laravel apaga o pivot fisicamente. Para desfazer o vínculo
 * preservando o histórico, faça soft delete do pivot.
 *
 * @property int $id
 * @property string $uuid
 * @property int $luta_id
 * @property int $juiz_id
 * @property FuncaoJuiz $funcao
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('luta_juizes')]
class LutaJuiz extends Pivot
{
    use Auditable, HasPublicUuid, SoftDeletes;

    /**
     * O pivot tem PK auto-incremento própria (padrão do projeto).
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'funcao' => FuncaoJuiz::class,
        ];
    }
}
