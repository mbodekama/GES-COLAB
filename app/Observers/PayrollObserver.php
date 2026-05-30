<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Payroll;
use App\Services\ActivityLogService;
use Carbon\Carbon;

class PayrollObserver
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function created(Payroll $payroll): void
    {
        $employeeName = $payroll->employee?->full_name ?? "#{$payroll->employee_id}";
        $period       = $this->periodLabel($payroll->period);
        $net          = number_format((float) $payroll->net_salary, 0, ',', ' ');

        $this->service->log(
            ActivityLog::CREATE,
            $payroll,
            "Fiche de paie générée — {$period} · {$employeeName} · Net : {$net} FCFA",
            [],
            $this->service->sanitizeForCreate($payroll->getAttributes(), $payroll->getAuditExcludeColumns()),
        );
    }

    public function updated(Payroll $payroll): void
    {
        $changes = $payroll->getChanges();
        if (empty($changes)) {
            return;
        }

        [$old, $new] = $this->service->diff(
            $payroll->getOriginal(),
            $changes,
            $payroll->getAuditExcludeColumns(),
        );

        if (empty($new)) {
            return;
        }

        $employeeName = $payroll->employee?->full_name ?? "#{$payroll->employee_id}";
        $period       = $this->periodLabel($payroll->period);

        $this->service->log(
            ActivityLog::UPDATE,
            $payroll,
            "Fiche de paie régénérée — {$period} · {$employeeName}",
            $old,
            $new,
        );
    }

    private function periodLabel(string $period): string
    {
        return Carbon::parse($period . '-01')->isoFormat('MMMM YYYY');
    }
}
