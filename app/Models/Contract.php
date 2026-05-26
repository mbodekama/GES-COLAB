<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'salary_grid_id', 'contract_number', 'type',
        'start_date', 'end_date', 'trial_end_date', 'position',
        'department', 'base_salary', 'status', 'signed_at', 'notes',
    ];

    protected $casts = [
        'start_date'     => 'date',
        'end_date'       => 'date',
        'trial_end_date' => 'date',
        'signed_at'      => 'datetime',
        'base_salary'    => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryGrid(): BelongsTo
    {
        return $this->belongsTo(SalaryGrid::class);
    }

    // ── Scopes ───────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    // ── Accessors ────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'cdi'        => 'CDI',
            'cdd'        => 'CDD',
            'internship' => 'Stage',
            'consulting' => 'Consulting',
            default      => strtoupper($this->type),
        };
    }

    // ── Helpers ──────────────────────────────────────────────
    public static function generateNumber(): string
    {
        $last = static::latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        return 'CTR-' . date('Y') . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
