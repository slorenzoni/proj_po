<?php

namespace App\Concerns;

use App\Services\AuditContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Preenche automaticamente o bloco audit_* em toda escrita do model.
 *
 * - Criação, atualização e restauração disparam "saving".
 * - O soft delete NÃO dispara "saving" (o Laravel grava só deleted_at direto no banco),
 *   então no "deleting" os campos são gravados antes, para registrar quem excluiu.
 *
 * Os campos audit_* ficam ocultos na serialização (JSON / props do Inertia), pois contêm
 * dados sensíveis como IP e navegador.
 *
 * @mixin Model
 */
trait Auditable
{
    /**
     * @var list<string>
     */
    public const AUDIT_COLUMNS = [
        'audit_id_user',
        'audit_name_user',
        'audit_origin_url',
        'audit_request_method',
        'audit_http_referer',
        'audit_route_name',
        'audit_controller_action',
        'audit_origin_ip',
        'audit_browser',
        'audit_db_user',
    ];

    public function initializeAuditable(): void
    {
        $this->mergeHidden(self::AUDIT_COLUMNS);
    }

    public static function bootAuditable(): void
    {
        static::saving(function (Model $model): void {
            $model->forceFill(app(AuditContext::class)->attributes());
        });

        static::deleting(function (Model $model): void {
            // isForceDeleting() só existe em models com SoftDeletes.
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                $model->forceFill(app(AuditContext::class)->attributes())->saveQuietly();
            }
        });
    }
}
