<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\StatusEvento;
use App\Enums\TipoTransmissao;
use Database\Factories\EventoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Evento de luta de uma organização (ex.: "UFC 300"), com o seu card de lutas.
 *
 * @property int $id
 * @property string $uuid
 * @property int $organizacao_id
 * @property string $nome
 * @property Carbon $data
 * @property string|null $local
 * @property string|null $cidade
 * @property string|null $pais
 * @property string|null $link_canal_youtube
 * @property TipoTransmissao|null $tipo_transmissao
 * @property StatusEvento $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('eventos')]
#[Fillable([
    'organizacao_id',
    'nome',
    'data',
    'local',
    'cidade',
    'pais',
    'link_canal_youtube',
    'tipo_transmissao',
    'status',
])]
class Evento extends Model
{
    /** @use HasFactory<EventoFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Organizacao, $this>
     */
    public function organizacao(): BelongsTo
    {
        return $this->belongsTo(Organizacao::class);
    }

    /**
     * Card do evento, da luta principal (ordem 1) para o começo do card.
     *
     * @return HasMany<Luta, $this>
     */
    public function lutas(): HasMany
    {
        return $this->hasMany(Luta::class)->orderBy('ordem_na_card');
    }

    /**
     * Endereço para incorporar o vídeo na página, quando o link é de um vídeo ou
     * transmissão do YouTube. Links de canal não podem ser incorporados: devolve nulo.
     */
    public function youtubeEmbedUrl(): ?string
    {
        $link = (string) $this->link_canal_youtube;

        $encontrou = preg_match(
            '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|live/|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~',
            $link,
            $partes,
        );

        return $encontrou === 1 ? 'https://www.youtube-nocookie.com/embed/'.$partes[1] : null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'datetime',
            'tipo_transmissao' => TipoTransmissao::class,
            'status' => StatusEvento::class,
        ];
    }
}
