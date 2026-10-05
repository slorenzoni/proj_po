<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\EscopoRanking;
use Database\Factories\RankingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Linha materializada do ranking de palpites de um usuário num escopo.
 *
 * "referencia_id" é o id do evento ou da organização, conforme o escopo, e fica nulo
 * no ranking geral. A fonte de verdade é "palpites.pontos_obtidos".
 *
 * @property int $id
 * @property string $uuid
 * @property EscopoRanking $escopo
 * @property int|null $referencia_id
 * @property int $user_id
 * @property string $pontos
 * @property int $palpites_perfeitos
 * @property int $vencedores_corretos
 * @property int|null $posicao
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('rankings')]
#[Fillable([
    'escopo',
    'referencia_id',
    'user_id',
    'pontos',
    'palpites_perfeitos',
    'vencedores_corretos',
    'posicao',
])]
class Ranking extends Model
{
    /** @use HasFactory<RankingFactory> */
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
            'escopo' => EscopoRanking::class,
            'pontos' => 'decimal:2',
        ];
    }
}
