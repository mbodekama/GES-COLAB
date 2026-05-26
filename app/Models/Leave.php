<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'approved_by', 'leave_number', 'type',
        'start_date', 'end_date', 'duration_days', 'reason',
        'attachment', 'status', 'rejection_reason', 'approved_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ───────────────────────────────────────────────
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // ── Accessors ────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'annual'      => 'Congé annuel',
            'sick'        => 'Congé maladie',
            'permission'  => 'Permission',
            'exceptional' => 'Congé exceptionnel',
            'maternity'   => 'Congé maternité',
            'paternity'   => 'Congé paternité',
            default       => $this->type,
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending'  => '<span class="badge bg-warning text-dark badge-status">En attente</span>',
            'approved' => '<span class="badge bg-success badge-status">Approuvé</span>',
            'rejected' => '<span class="badge bg-danger badge-status">Refusé</span>',
            default    => '<span class="badge bg-secondary badge-status">'.$this->status.'</span>',
        };
    }

    // ── Helpers ──────────────────────────────────────────────
    public static function generateNumber(): string
    {
        $last = static::latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        return 'LVE-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public static function calculateDays(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        return max(1, $start->diffInDays($end) + 1);
    }
}
