<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\CategoriaPesoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Divisão de peso dentro de uma modalidade (ex.: Peso Leve do MMA).
 *
 * @property int $id
 * @property string $uuid
 * @property int $categoria_id
 * @property string $nome
 * @property string|null $peso_minimo_kg
 * @property string|null $peso_maximo_kg
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('categorias_peso')]
#[Fillable(['categoria_id', 'nome', 'peso_minimo_kg', 'peso_maximo_kg'])]
class CategoriaPeso extends Model
{
    /** @use HasFactory<CategoriaPesoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

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
            'peso_minimo_kg' => 'decimal:2',
            'peso_maximo_kg' => 'decimal:2',
        ];
    }
}
