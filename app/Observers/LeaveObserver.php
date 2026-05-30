<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Leave;
use App\Services\ActivityLogService;

class LeaveObserver
{
    // Colonnes qui déclenchent un STATUS_CHANGE (pas un UPDATE générique)
    private const STATE_COLUMNS = ['status', 'workflow_step'];

    private const WORKFLOW_LABELS = [
        'pending_n1' => 'En attente N+1',
        'pending_rh' => 'En attente RH',
        'approved'   => 'Approuvé',
        'rejected'   => 'Refusé',
    ];

    public function __construct(private readonly ActivityLogService $service) {}

    public function created(Leave $leave): void
    {
        $employeeName = $leave->employee?->full_name ?? "#{$leave->employee_id}";

        $this->service->log(
            ActivityLog::CREATE,
            $leave,
            "Demande {$leave->type_label} soumise — {$leave->leave_number} ({$employeeName})",
            [],
            $this->service->sanitizeForCreate($leave->getAttributes(), $leave->getAuditExcludeColumns()),
        );
    }

    public function updated(Leave $leave): void
    {
        $changes = $leave->getChanges();
        if (empty($changes)) {
            return;
        }

        [$old, $new] = $this->service->diff(
            $leave->getOriginal(),
            $changes,
            $leave->getAuditExcludeColumns(),
        );

        if (empty($new)) {
            return;
        }

        $hasStateChange = !empty(array_intersect_key($new, array_flip(self::STATE_COLUMNS)));

        if ($hasStateChange) {
            $this->service->log(
                ActivityLog::STATUS_CHANGE,
                $leave,
                $this->buildStatusDescription($leave, $old, $new),
                $old,
                $new,
            );
        } else {
            $employeeName = $leave->employee?->full_name ?? "#{$leave->employee_id}";
            $this->service->log(
                ActivityLog::UPDATE,
                $leave,
                "Modification de la demande {$leave->leave_number} ({$employeeName})",
                $old,
                $new,
            );
        }
    }

    public function deleted(Leave $leave): void
    {
        $employeeName = $leave->employee?->full_name ?? "#{$leave->employee_id}";

        $this->service->log(
            ActivityLog::DELETE,
            $leave,
            "Suppression de la demande {$leave->leave_number} ({$employeeName})",
        );
    }

    private function buildStatusDescription(Leave $leave, array $old, array $new): string
    {
        $name = $leave->employee?->full_name ?? "#{$leave->employee_id}";
        $num  = $leave->leave_number;
        $type = $leave->type_label;

        if (isset($new['workflow_step'])) {
            $from = self::WORKFLOW_LABELS[$old['workflow_step'] ?? ''] ?? ($old['workflow_step'] ?? '?');
            $to   = self::WORKFLOW_LABELS[$new['workflow_step']]        ?? $new['workflow_step'];
            return "{$type} {$num} ({$name}) : {$from} → {$to}";
        }

        $from = $old['status'] ?? '?';
        $to   = $new['status'];
        return "Statut {$type} {$num} ({$name}) : {$from} → {$to}";
    }
}
