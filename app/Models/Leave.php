<?php

namespace App\Models;

use App\Models\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    use HasFactory, HasActivityLog;

    protected $fillable = [
        'employee_id', 'approved_by', 'leave_number', 'type',
        'start_date', 'end_date', 'duration_days', 'reason',
        'attachment', 'status', 'rejection_reason', 'approved_at',
        'workflow_step', 'n1_validator_id', 'n1_validated_at', 'n1_comment',
        'date_approbation', 'date_rejet',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'approved_at'      => 'datetime',
        'n1_validated_at'  => 'datetime',
        'date_approbation' => 'date',
        'date_rejet'       => 'date',
    ];

    // Types nécessitant validation N+1
    const NEEDS_N1 = ['permission'];

    // Types allant directement au RH
    const DIRECT_RH = ['annual', 'sick', 'exceptional', 'maternity', 'paternity'];

    // FK et timestamps redondants avec user_id/created_at du log
    public function getAuditExcludeColumns(): array
    {
        return ['n1_validator_id', 'n1_validated_at', 'approved_by', 'approved_at'];
    }

    // ── Relations ────────────────────────────────────────────
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function n1Validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'n1_validator_id');
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

    public function scopePendingN1($query)
    {
        return $query->where('workflow_step', 'pending_n1');
    }

    public function scopePendingRh($query)
    {
        return $query->where('workflow_step', 'pending_rh');
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

    public function getWorkflowBadgeAttribute(): string
    {
        return match ($this->workflow_step) {
            'pending_n1' => '<span class="badge bg-info badge-status">En attente N+1</span>',
            'pending_rh' => '<span class="badge bg-warning text-dark badge-status">En attente RH</span>',
            'approved' => '<span class="badge bg-success badge-status">Approuvé</span>',
            'rejected' => '<span class="badge bg-danger badge-status">Refusé</span>',
            default => '<span class="badge bg-secondary badge-status">' . $this->workflow_step . '</span>',
        };
    }

    public function getWorkflowStepLabelAttribute(): string
    {
        return match ($this->workflow_step) {
            'pending_n1' => 'En attente validation N+1',
            'pending_rh' => 'En attente validation RH',
            'approved'   => 'Approuvé',
            'rejected'   => 'Refusé',
            default      => $this->workflow_step,
        };
    }

    // ── Helpers ──────────────────────────────────────────────
    public static function generateNumber(): string
    {
        $last = static::latest('id')->first();
        $next = $last ? ($last->id + 1) : 1;
        return 'LVE-' . date('Y') . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public static function calculateDays(
        \Carbon\Carbon $start,
        \Carbon\Carbon $end
    ): int {
        return max(1, $start->diffInDays($end) + 1);
    }

    // Déterminer l'étape initiale selon le type
    public static function initialWorkflowStep(string $type): string
    {
        return in_array($type, self::NEEDS_N1) ? 'pending_n1' : 'pending_rh';
    }
}
