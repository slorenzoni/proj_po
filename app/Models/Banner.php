<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use App\Enums\ModeloCobranca;
use App\Enums\PosicaoBanner;
use App\Enums\StatusBanner;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Banner de um patrocinador, exibido numa área do site durante um período.
 * "impressoes" e "cliques" são métricas, não informadas pelo cadastro.
 *
 * @property int $id
 * @property string $uuid
 * @property int $patrocinador_id
 * @property string $imagem_url Caminho do arquivo no disco de mídia, não a URL completa.
 * @property string|null $link_destino
 * @property PosicaoBanner $posicao
 * @property Carbon $data_inicio
 * @property Carbon|null $data_fim
 * @property StatusBanner $status
 * @property ModeloCobranca $modelo_cobranca
 * @property string|null $valor_contrato
 * @property int $impressoes
 * @property int $cliques
 * @property int $ordem_exibicao
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('banners')]
#[Fillable([
    'patrocinador_id',
    'imagem_url',
    'link_destino',
    'posicao',
    'data_inicio',
    'data_fim',
    'status',
    'modelo_cobranca',
    'valor_contrato',
    'ordem_exibicao',
])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;

    /**
     * @return BelongsTo<Patrocinador, $this>
     */
    public function patrocinador(): BelongsTo
    {
        return $this->belongsTo(Patrocinador::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'posicao' => PosicaoBanner::class,
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'status' => StatusBanner::class,
            'modelo_cobranca' => ModeloCobranca::class,
            'valor_contrato' => 'decimal:2',
        ];
    }
}
