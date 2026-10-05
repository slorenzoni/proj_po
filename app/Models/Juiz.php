<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Database\Factories\JuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Juiz ou árbitro. A função exercida em cada luta fica no vínculo com a luta, não aqui.
 *
 * @property int $id
 * @property string $uuid
 * @property string $nome
 * @property string|null $pais
 * @property string|null $certificado_por
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Table('juizes')]
#[Fillable(['nome', 'pais', 'certificado_por'])]
class Juiz extends Model
{
    /** @use HasFactory<JuizFactory> */
    use Auditable, HasFactory, HasPublicUuid, SoftDeletes;
}
