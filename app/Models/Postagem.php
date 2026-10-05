<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\StatusPostagem;
use Database\Factories\PostagemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Postagem do blog. O autor é um administrador; o patrocinador é opcional.
 *
 * "patrocinado" não é informado: é recalculado a cada gravação a partir de "patrocinador_id".
 *
 * @property int $id
 * @property string $uuid
 * @property string $titulo
 * @property string $slug
 * @property string $conteudo
 * @property string|null $meta_description
 * @property string|null $imagem_capa Caminho do arquivo no disco de mídia, não a URL completa.
 * @property int $user_id
 * @property int|null $patrocinador_id
 * @property bool $patrocinado
 * @property string|null $fonte_original_url
 * @property int|null $categoria_id
 * @property StatusPostagem $status
 * @property Carbon|null $data_publicacao
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('postagens')]
#[Fillable([
    'titulo',
    'slug',
    'conteudo',
    'meta_description',
    'imagem_capa',
    'user_id',
    'patrocinador_id',
    'fonte_original_url',
    'categoria_id',
    'status',
    'data_publicacao',
])]
class Postagem extends Model
{
    /** @use HasFactory<PostagemFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * Autor/publicador da postagem.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Patrocinador, $this>
     */
    public function patrocinador(): BelongsTo
    {
        return $this->belongsTo(Patrocinador::class);
    }

    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Postagem $postagem): void {
            $postagem->patrocinado = $postagem->patrocinador_id !== null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'patrocinado' => 'boolean',
            'status' => StatusPostagem::class,
            'data_publicacao' => 'date',
        ];
    }
}
