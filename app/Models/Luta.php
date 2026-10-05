<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\MetodoVitoria;
use App\Enums\StatusLuta;
use App\Enums\TipoCard;
use Database\Factories\LutaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Luta entre dois atletas dentro de um evento.
 *
 * "round_atual" e "em_intervalo" são o andamento ao vivo, informado manualmente pelo
 * administrador; é com eles que o servidor libera ou bloqueia a troca do palpite.
 *
 * @property int $id
 * @property string $uuid
 * @property int $evento_id
 * @property int $categoria_id
 * @property int $categoria_peso_id
 * @property int $participante_a_id
 * @property int $participante_b_id
 * @property int $ordem_na_card
 * @property TipoCard|null $tipo_card
 * @property int|null $numero_rounds Nulo em modalidades sem rounds (ex.: Judô).
 * @property string|null $chance_do_a
 * @property string|null $chance_do_b
 * @property StatusLuta $status
 * @property int|null $round_atual
 * @property bool $em_intervalo
 * @property int|null $vencedor_id
 * @property MetodoVitoria|null $metodo_vitoria
 * @property int|null $round_fim
 * @property string|null $tempo_fim Formato mm:ss.
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('lutas')]
#[Fillable([
    'evento_id',
    'categoria_id',
    'categoria_peso_id',
    'participante_a_id',
    'participante_b_id',
    'ordem_na_card',
    'tipo_card',
    'numero_rounds',
    'chance_do_a',
    'chance_do_b',
    'status',
    'round_atual',
    'em_intervalo',
    'vencedor_id',
    'metodo_vitoria',
    'round_fim',
    'tempo_fim',
])]
class Luta extends Model
{
    /** @use HasFactory<LutaFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Evento, $this>
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @return BelongsTo<CategoriaPeso, $this>
     */
    public function categoriaPeso(): BelongsTo
    {
        return $this->belongsTo(CategoriaPeso::class);
    }

    /**
     * @return BelongsTo<Atleta, $this>
     */
    public function participanteA(): BelongsTo
    {
        return $this->belongsTo(Atleta::class, 'participante_a_id');
    }

    /**
     * @return BelongsTo<Atleta, $this>
     */
    public function participanteB(): BelongsTo
    {
        return $this->belongsTo(Atleta::class, 'participante_b_id');
    }

    /**
     * @return BelongsTo<Atleta, $this>
     */
    public function vencedor(): BelongsTo
    {
        return $this->belongsTo(Atleta::class, 'vencedor_id');
    }

    /**
     * Juízes escalados (pivots com soft delete são ignorados). A função de cada um fica no pivot.
     *
     * @return BelongsToMany<Juiz, $this, LutaJuiz>
     */
    public function juizes(): BelongsToMany
    {
        return $this->belongsToMany(Juiz::class, 'luta_juizes')
            ->using(LutaJuiz::class)
            ->withPivot('funcao')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * Placar oficial, round a round, de cada juiz lateral.
     *
     * @return HasMany<Placar, $this>
     */
    public function placares(): HasMany
    {
        return $this->hasMany(Placar::class);
    }

    /**
     * @return HasMany<PlacarFan, $this>
     */
    public function placaresFans(): HasMany
    {
        return $this->hasMany(PlacarFan::class);
    }

    /**
     * @return HasMany<Palpite, $this>
     */
    public function palpites(): HasMany
    {
        return $this->hasMany(Palpite::class);
    }

    /**
     * @return HasMany<Mensagem, $this>
     */
    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo_card' => TipoCard::class,
            'chance_do_a' => 'decimal:2',
            'chance_do_b' => 'decimal:2',
            'status' => StatusLuta::class,
            'em_intervalo' => 'boolean',
            'metodo_vitoria' => MetodoVitoria::class,
        ];
    }
}
