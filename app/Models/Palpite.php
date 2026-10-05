<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\MetodoPalpite;
use Database\Factories\PalpiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Palpite vigente de um usuário numa luta (um por usuário por luta).
 *
 * Vencedor é obrigatório; método e round são opcionais. "peso_aplicado" é o percentual
 * definido pelo momento da última troca e "pontos_obtidos" já vem com ele aplicado.
 *
 * @property int $id
 * @property string $uuid
 * @property int $luta_id
 * @property int $user_id
 * @property int $vencedor_escolhido_id
 * @property MetodoPalpite|null $metodo_escolhido
 * @property int|null $round_escolhido
 * @property int $round_da_troca 0 = pré-luta; N = intervalo após o round N.
 * @property string $peso_aplicado
 * @property string|null $pontos_obtidos Nulo até a luta ser encerrada.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('palpites')]
#[Fillable([
    'luta_id',
    'user_id',
    'vencedor_escolhido_id',
    'metodo_escolhido',
    'round_escolhido',
    'round_da_troca',
    'peso_aplicado',
    'pontos_obtidos',
])]
class Palpite extends Model
{
    /** @use HasFactory<PalpiteFactory> */
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

    /**
     * @return BelongsTo<Atleta, $this>
     */
    public function vencedorEscolhido(): BelongsTo
    {
        return $this->belongsTo(Atleta::class, 'vencedor_escolhido_id');
    }

    /**
     * Trocas de palpite, da mais antiga para a mais recente.
     *
     * @return HasMany<PalpiteHistorico, $this>
     */
    public function historicos(): HasMany
    {
        return $this->hasMany(PalpiteHistorico::class)->oldest();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metodo_escolhido' => MetodoPalpite::class,
            'peso_aplicado' => 'decimal:2',
            'pontos_obtidos' => 'decimal:2',
        ];
    }
}
