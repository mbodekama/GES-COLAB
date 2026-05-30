<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    // Colonnes jamais enregistrées dans les diffs
    const NEVER_LOG = [
        'updated_at', 'created_at', 'deleted_at',
        'remember_token', 'password', 'email_verified_at',
    ];

    public function log(
        string  $actionType,
        Model   $entity,
        string  $description,
        array   $oldValues  = [],
        array   $newValues  = [],
        ?string $comment    = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'     => auth()->id(),
            'action_type' => $actionType,
            'entity_type' => $entity->getMorphClass(),
            'entity_id'   => $entity->getKey(),
            'description' => $description,
            'old_values'  => $oldValues  ?: null,
            'new_values'  => $newValues  ?: null,
            'comment'     => $comment,
        ]);
    }

    /**
     * Calcule le diff entre les anciennes et nouvelles valeurs d'un modèle.
     * Retourne [old, new] filtrés (sans les colonnes exclues ni les valeurs identiques).
     */
    public function diff(array $original, array $changes, array $extraExclude = []): array
    {
        $exclude = array_merge(self::NEVER_LOG, $extraExclude);

        $old = [];
        $new = [];

        foreach ($changes as $field => $newVal) {
            if (in_array($field, $exclude, true)) {
                continue;
            }
            $old[$field] = $original[$field] ?? null;
            $new[$field] = $newVal;
        }

        return [$old, $new];
    }

    /**
     * Filtre un tableau d'attributs pour la création (retire les colonnes jamais loggées).
     */
    public function sanitizeForCreate(array $attributes, array $extraExclude = []): array
    {
        $exclude = array_merge(self::NEVER_LOG, $extraExclude);
        return array_diff_key($attributes, array_flip($exclude));
    }
}
