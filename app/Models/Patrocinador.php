<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\StatusPatrocinador;
use Database\Factories\PatrocinadorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Patrocinador do site, dono de banners e de postagens patrocinadas.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property string|null $logo_url Caminho do arquivo no disco de mídia, não a URL completa.
 * @property string|null $link_site
 * @property string|null $email_contato
 * @property StatusPatrocinador $status
 * @property Carbon|null $data_inicio_contrato
 * @property Carbon|null $data_fim_contrato
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('patrocinadores')]
#[Fillable([
    'nome',
    'logo_url',
    'link_site',
    'email_contato',
    'status',
    'data_inicio_contrato',
    'data_fim_contrato',
])]
class Patrocinador extends Model
{
    /** @use HasFactory<PatrocinadorFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return HasMany<Banner, $this>
     */
    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    /**
     * @return HasMany<Postagem, $this>
     */
    public function postagens(): HasMany
    {
        return $this->hasMany(Postagem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StatusPatrocinador::class,
            'data_inicio_contrato' => 'date',
            'data_fim_contrato' => 'date',
        ];
    }
}
