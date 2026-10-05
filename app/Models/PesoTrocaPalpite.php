<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\PesoTrocaPalpiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Peso (percentual) aplicado à pontuação do palpite conforme o momento da última troca.
 *
 * As linhas de mesma categoria e mesmo "numero_rounds" formam uma grade. As linhas sem
 * categoria são o padrão geral; uma categoria que tenha grade própria para aquele número
 * de rounds usa só a dela (não há mistura linha a linha).
 *
 * @property int $id
 * @property string $uuid
 * @property int|null $categoria_id
 * @property int $numero_rounds
 * @property int $round_da_troca 0 = pré-luta; N = intervalo após o round N.
 * @property string $peso
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('pesos_troca_palpite')]
#[Fillable(['categoria_id', 'numero_rounds', 'round_da_troca', 'peso'])]
class PesoTrocaPalpite extends Model
{
    /** @use HasFactory<PesoTrocaPalpiteFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * Durações de luta que têm grade de pesos.
     */
    public const NUMEROS_DE_ROUNDS = [3, 5];

    /**
     * Momentos em que o palpite pode ser trocado numa luta com esse número de rounds:
     * 0 (pré-luta) e o intervalo após cada round, menos o último.
     *
     * @return list<int>
     */
    public static function momentosDaTroca(int $numeroRounds): array
    {
        return $numeroRounds < 1 ? [] : range(0, $numeroRounds - 1);
    }

    /**
     * Grade de pesos que vale para a categoria numa luta com esse número de rounds:
     * a específica dela, ou o padrão geral se não houver.
     *
     * @return Collection<int, string> Peso por "round_da_troca"; vazia se não houver grade cadastrada.
     */
    public static function gradePara(Categoria $categoria, int $numeroRounds): Collection
    {
        $linhas = self::query()
            ->where('numero_rounds', $numeroRounds)
            ->where(fn ($query) => $query
                ->where('categoria_id', $categoria->getKey())
                ->orWhereNull('categoria_id'))
            ->orderBy('round_da_troca')
            ->get();

        $daCategoria = $linhas->whereNotNull('categoria_id');
        $vigentes = $daCategoria->isNotEmpty() ? $daCategoria : $linhas;

        return $vigentes->pluck('peso', 'round_da_troca');
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
        ];
    }
}
