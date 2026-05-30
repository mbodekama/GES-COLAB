<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Services\ActivityLogService;

class ContractObserver
{
    private const STATUS_LABELS = [
        'active'     => 'En cours',
        'expired'    => 'Expiré',
        'terminated' => 'Résilié',
        'renewed'    => 'Renouvelé',
    ];

    public function __construct(private readonly ActivityLogService $service) {}

    public function created(Contract $contract): void
    {
        $employeeName = $contract->employee?->full_name ?? "#{$contract->employee_id}";

        $this->service->log(
            ActivityLog::CREATE,
            $contract,
            "Contrat {$contract->type_label} créé — {$contract->contract_number} ({$employeeName})",
            [],
            $this->service->sanitizeForCreate($contract->getAttributes(), $contract->getAuditExcludeColumns()),
        );
    }

    public function updated(Contract $contract): void
    {
        $changes = $contract->getChanges();
        if (empty($changes)) {
            return;
        }

        [$old, $new] = $this->service->diff(
            $contract->getOriginal(),
            $changes,
            $contract->getAuditExcludeColumns(),
        );

        if (empty($new)) {
            return;
        }

        if (array_key_exists('status', $new)) {
            $employeeName = $contract->employee?->full_name ?? "#{$contract->employee_id}";
            $from = self::STATUS_LABELS[$old['status'] ?? ''] ?? ($old['status'] ?? '?');
            $to   = self::STATUS_LABELS[$new['status']]        ?? $new['status'];

            $this->service->log(
                ActivityLog::STATUS_CHANGE,
                $contract,
                "Contrat {$contract->contract_number} ({$employeeName}) : {$from} → {$to}",
                $old,
                $new,
            );
            return;
        }

        $employeeName = $contract->employee?->full_name ?? "#{$contract->employee_id}";
        $this->service->log(
            ActivityLog::UPDATE,
            $contract,
            "Modification du contrat {$contract->contract_number} ({$employeeName})",
            $old,
            $new,
        );
    }
}
