<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Services\ActivityLogService;

class EmployeeObserver
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function created(Employee $employee): void
    {
        $this->service->log(
            ActivityLog::CREATE,
            $employee,
            "Création de l'employé {$employee->full_name} ({$employee->matricule})",
            [],
            $this->service->sanitizeForCreate($employee->getAttributes(), $employee->getAuditExcludeColumns()),
        );
    }

    public function updated(Employee $employee): void
    {
        $changes = $employee->getChanges();
        if (empty($changes)) {
            return;
        }

        // getOriginal() contient encore les anciennes valeurs (syncOriginal() n'est appelé qu'après)
        [$old, $new] = $this->service->diff(
            $employee->getOriginal(),
            $changes,
            $employee->getAuditExcludeColumns(),
        );

        if (empty($new)) {
            return;
        }

        // Changement de statut → log dédié
        if (array_key_exists('status', $new)) {
            $this->service->log(
                ActivityLog::STATUS_CHANGE,
                $employee,
                "Statut modifié : {$old['status']} → {$new['status']}",
                ['status' => $old['status']],
                ['status' => $new['status']],
            );
            unset($old['status'], $new['status']);
        }

        // Changement de workflow_step (congés liés)
        if (array_key_exists('workflow_step', $new)) {
            unset($old['workflow_step'], $new['workflow_step']);
        }

        if (!empty($new)) {
            $this->service->log(
                ActivityLog::UPDATE,
                $employee,
                "Modification de l'employé {$employee->full_name} ({$employee->matricule})",
                $old,
                $new,
            );
        }
    }

    public function deleted(Employee $employee): void
    {
        $this->service->log(
            ActivityLog::DELETE,
            $employee,
            "Archivage de l'employé {$employee->full_name} ({$employee->matricule})",
        );
    }

    public function restored(Employee $employee): void
    {
        $this->service->log(
            ActivityLog::RESTORE,
            $employee,
            "Restauration de l'employé {$employee->full_name} ({$employee->matricule})",
        );
    }
}
