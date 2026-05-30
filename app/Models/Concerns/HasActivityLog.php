<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasActivityLog
{
    // ── Relation ─────────────────────────────────────────────
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'entity')->latest();
    }

    // ── Helpers ──────────────────────────────────────────────
    public function logActivity(
        string  $actionType,
        string  $description,
        array   $oldValues  = [],
        array   $newValues  = [],
        ?string $comment    = null,
    ): ActivityLog {
        return app(ActivityLogService::class)->log(
            $actionType,
            $this,
            $description,
            $oldValues,
            $newValues,
            $comment,
        );
    }

    /**
     * Colonnes à exclure du diff en plus des colonnes système.
     * Surcharger dans le modèle pour des exclusions spécifiques.
     */
    public function getAuditExcludeColumns(): array
    {
        return [];
    }
}
