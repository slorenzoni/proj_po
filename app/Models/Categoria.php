<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Modalidade (ex.: MMA, Boxe, Judô). Define o comportamento do palpite por modalidade.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property bool $usa_rounds Falso para modalidades sem rounds (ex.: Judô).
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('categorias')]
#[Fillable(['nome', 'usa_rounds'])]
class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return HasMany<CategoriaPeso, $this>
     */
    public function categoriasPeso(): HasMany
    {
        return $this->hasMany(CategoriaPeso::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usa_rounds' => 'boolean',
        ];
    }
}
