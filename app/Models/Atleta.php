<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\MetodoVitoria;
use Database\Factories\AtletaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Atleta (lutador). O vínculo com users só existe se o atleta tiver conta no sistema.
 *
 * Os totais "vitorias", "derrotas" e o indicador "invicto" não são informados: são
 * recalculados a cada gravação a partir do detalhamento (KO, submissão e decisão).
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property string|null $apelido
 * @property string $tipo
 * @property string|null $equipe
 * @property string|null $pais
 * @property string|null $cidade_natal
 * @property Carbon|null $data_nascimento
 * @property int|null $altura_cm
 * @property string|null $peso_kg
 * @property int|null $alcance_cm
 * @property string|null $stance
 * @property string|null $biografia
 * @property int $vitorias
 * @property int $vitorias_ko
 * @property int $vitorias_submissao
 * @property int $vitorias_decisao
 * @property int $empates
 * @property int $derrotas
 * @property int $derrotas_ko
 * @property int $derrotas_submissao
 * @property int $derrotas_decisao
 * @property bool $invicto
 * @property int|null $ranking
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('atletas')]
#[Fillable([
    'nome',
    'apelido',
    'tipo',
    'equipe',
    'pais',
    'cidade_natal',
    'data_nascimento',
    'altura_cm',
    'peso_kg',
    'alcance_cm',
    'stance',
    'biografia',
    'vitorias_ko',
    'vitorias_submissao',
    'vitorias_decisao',
    'empates',
    'derrotas_ko',
    'derrotas_submissao',
    'derrotas_decisao',
    'ranking',
    'user_id',
])]
class Atleta extends Model
{
    /** @use HasFactory<AtletaFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AtletaFoto, $this>
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(AtletaFoto::class)->orderBy('ordem');
    }

    /**
     * Foto exibida nos cards de luta.
     *
     * @return HasOne<AtletaFoto, $this>
     */
    public function fotoPrincipal(): HasOne
    {
        return $this->hasOne(AtletaFoto::class)->where('principal', true);
    }

    /**
     * Estilos com vínculo ativo (pivots com soft delete são ignorados).
     *
     * @return BelongsToMany<EstiloDeLuta, $this, AtletaEstilo>
     */
    public function estilos(): BelongsToMany
    {
        return $this->belongsToMany(EstiloDeLuta::class, 'atleta_estilos', 'atleta_id', 'estilo_id')
            ->using(AtletaEstilo::class)
            ->withPivot('treinador_id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * Soma uma vitória ao detalhamento do cartel; os totais são recalculados ao salvar.
     */
    public function registrarVitoria(MetodoVitoria $metodo): void
    {
        $this->somarAoCartel('vitorias_'.$metodo->tipoNoCartel());
    }

    public function registrarDerrota(MetodoVitoria $metodo): void
    {
        $this->somarAoCartel('derrotas_'.$metodo->tipoNoCartel());
    }

    public function registrarEmpate(): void
    {
        $this->somarAoCartel('empates');
    }

    private function somarAoCartel(string $coluna): void
    {
        // setAttribute + save (e não increment) para o evento "saving" recalcular os totais.
        $this->setAttribute($coluna, (int) $this->getAttribute($coluna) + 1);
        $this->save();
    }

    protected static function booted(): void
    {
        static::saving(function (Atleta $atleta): void {
            $atleta->recalcularCartel();
        });
    }

    /**
     * Deriva os totais e o "invicto" do detalhamento por método de vitória/derrota.
     */
    protected function recalcularCartel(): void
    {
        $this->vitorias = (int) $this->vitorias_ko + (int) $this->vitorias_submissao + (int) $this->vitorias_decisao;
        $this->derrotas = (int) $this->derrotas_ko + (int) $this->derrotas_submissao + (int) $this->derrotas_decisao;
        $this->invicto = $this->derrotas === 0;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'peso_kg' => 'decimal:2',
            'invicto' => 'boolean',
        ];
    }
}
