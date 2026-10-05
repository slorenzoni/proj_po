<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\MensagemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Comentário de um usuário numa luta. Não há tempo real: aparece ao recarregar a página.
 *
 * @property int $id
 * @property string $uuid
 * @property int $luta_id
 * @property int $user_id
 * @property string $mensagem
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('mensagens')]
#[Fillable(['luta_id', 'user_id', 'mensagem'])]
class Mensagem extends Model
{
    /** @use HasFactory<MensagemFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Luta, $this>
     */
    public function luta(): BelongsTo
    {
        return $this->belongsTo(Luta::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
