<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poste extends Model
{
    use HasFactory;

    protected $table = 'postes';

    protected $fillable = [
        'title',
        'code',
        'department',
        'level',
        'can_be_n1',
        'description',
        'is_active',
    ];

    protected $casts = [
        'can_be_n1' => 'boolean',
        'is_active' => 'boolean',
        'level'     => 'integer',
    ];

    // ── Relations ────────────────────────────────────────────
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'poste_id');
    }

    // ── Scopes ───────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCanBeN1($query)
    {
        return $query->where('can_be_n1', true);
    }

    public function scopeOrderedByLevel($query)
    {
        return $query->orderByDesc('level');
    }

    public function scopeAboveLevel($query, int $level)
    {
        return $query->where('level', '>', $level);
    }

    // ── Accessors ────────────────────────────────────────────
    public function getLevelLabelAttribute(): string
    {
        return match (true) {
            $this->level >= 9 => 'Direction',
            $this->level >= 7 => 'Management supérieur',
            $this->level >= 5 => 'Management intermédiaire',
            $this->level >= 3 => 'Supervision',
            default           => 'Exécution',
        };
    }

    public function getLevelBadgeAttribute(): string
    {
        $color = match (true) {
            $this->level >= 9 => 'danger',
            $this->level >= 7 => 'warning',
            $this->level >= 5 => 'primary',
            $this->level >= 3 => 'info',
            default           => 'secondary',
        };

        return "<span class=\"badge bg-{$color} badge-status\">"
             . "Niv. {$this->level} — {$this->level_label}"
             . "</span>";
    }

    // Retourner les postes pouvant être N+1 d'un niveau donné
    public static function getN1Postes(int $currentLevel): \Illuminate\Support\Collection
    {
        return static::active()
                     ->canBeN1()
                     ->aboveLevel($currentLevel)
                     ->orderedByLevel()
                     ->get();
    }
}
