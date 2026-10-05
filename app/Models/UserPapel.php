<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Pivot users × papeis com id próprio, UUID, soft delete e auditoria.
 *
 * Atenção: o detach() do Laravel apaga o pivot fisicamente. Para remover um papel,
 * use User::removerPapel(), que faz soft delete.
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $papel_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('papel_user')]
class UserPapel extends Pivot
{
    use Auditable, HasPublicUuid, SoftDeletes;

    /**
     * O pivot tem PK auto-incremento própria (padrão do projeto).
     *
     * @var bool
     */
    public $incrementing = true;
}
