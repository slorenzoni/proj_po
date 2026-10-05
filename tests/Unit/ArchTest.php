<?php

use App\Concerns\Auditable;
use App\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
| Convenção do dicionário de dados: toda tabela tem uuid público, soft delete e auditoria.
| Um model novo que esqueça algum desses traits quebra este teste.
*/
arch('every model has a public uuid, soft deletes and auditing')
    ->expect('App\Models')
    ->classes()
    ->toUseTraits([HasPublicUuid::class, SoftDeletes::class, Auditable::class]);
