<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\ConfiguracaoPontuacaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Pontos do palpite e prazo do placar dos fãs.
 *
 * A linha sem categoria é o padrão geral. Uma linha com categoria substitui o padrão
 * inteiro para aquela modalidade (não há mistura campo a campo).
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $categoria_id
 * @property int $pontos_vencedor
 * @property int $pontos_vencedor_metodo
 * @property int $pontos_vencedor_round
 * @property int $pontos_perfeito Vencedor + método + round.
 * @property int $prazo_placar_fans_minutos
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('configuracoes_pontuacao')]
#[Fillable([
    'categoria_id',
    'pontos_vencedor',
    'pontos_vencedor_metodo',
    'pontos_vencedor_round',
    'pontos_perfeito',
    'prazo_placar_fans_minutos',
])]
class ConfiguracaoPontuacao extends Model
{
    /** @use HasFactory<ConfiguracaoPontuacaoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * Configuração que vale para a categoria: a específica dela, ou o padrão geral se não houver.
     *
     * @throws ModelNotFoundException se nem o padrão geral estiver cadastrado.
     */
    public static function paraCategoria(Categoria $categoria): self
    {
        return self::query()
            ->where(fn ($query) => $query
                ->where('categoria_id', $categoria->getKey())
                ->orWhereNull('categoria_id'))
            // A linha da categoria (não nula) vem antes do padrão geral.
            ->orderByRaw('categoria_id is null')
            ->firstOrFail();
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}
