<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action_type',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'comment',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // ── Constantes ───────────────────────────────────────────
    const CREATE        = 'CREATE';
    const UPDATE        = 'UPDATE';
    const DELETE        = 'DELETE';
    const RESTORE       = 'RESTORE';
    const STATUS_CHANGE = 'STATUS_CHANGE';
    const COMMENT       = 'COMMENT';

    const ACTION_LABELS = [
        self::CREATE        => 'Création',
        self::UPDATE        => 'Modification',
        self::DELETE        => 'Suppression',
        self::RESTORE       => 'Restauration',
        self::STATUS_CHANGE => 'Changement de statut',
        self::COMMENT       => 'Commentaire',
    ];

    const ACTION_ICONS = [
        self::CREATE        => 'bi-plus-circle-fill text-success',
        self::UPDATE        => 'bi-pencil-fill text-primary',
        self::DELETE        => 'bi-trash-fill text-danger',
        self::RESTORE       => 'bi-arrow-counterclockwise text-info',
        self::STATUS_CHANGE => 'bi-arrow-left-right text-warning',
        self::COMMENT       => 'bi-chat-fill text-secondary',
    ];

    // ── Relations ────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Scopes ───────────────────────────────────────────────
    public function scopeForEntity(Builder $query, string $type, int|string $id): Builder
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action_type', $action);
    }

    // ── Accessors ────────────────────────────────────────────
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action_type] ?? $this->action_type;
    }

    public function getActionIconAttribute(): string
    {
        return self::ACTION_ICONS[$this->action_type] ?? 'bi-circle-fill text-muted';
    }
}
