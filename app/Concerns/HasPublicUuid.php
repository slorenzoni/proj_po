<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Gera a coluna "uuid" automaticamente e a usa no route model binding.
 *
 * A PK continua sendo o "id" BIGINT auto-incremento (usado nas FKs); o UUID existe
 * apenas para não expor IDs sequenciais nas URLs.
 *
 * Atenção: a geração acontece no evento "creating" — seeders com WithoutModelEvents
 * ou inserts via query builder não preenchem o UUID.
 *
 * @mixin Model
 */
trait HasPublicUuid
{
    use HasUuids;

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
