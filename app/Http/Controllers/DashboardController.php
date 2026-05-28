<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $employee = $user->employee;
        $isAdmin  = $user->hasRole(['superadmin', 'admin']);

        // ── KPI 1 — Total employés (admin) ou Mes demandes (user) ──
        if ($isAdmin) {
            $kpi1 = [
                'label'  => 'Total employés',
                'value'  => Employee::count(),
                'delta'  => '+'.Employee::whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count().' ce mois',
                'color'  => '#185FA5',
                'bg'     => '#E6F1FB',
                'icon'   => 'bi-people-fill',
            ];
        } else {
            $myLeaves = $employee
                ? Leave::where('employee_id', $employee->id)->count()
                : 0;
            $kpi1 = [
                'label'  => 'Mes demandes',
                'value'  => $myLeaves,
                'delta'  => 'Total soumises',
                'color'  => '#185FA5',
                'bg'     => '#E6F1FB',
                'icon'   => 'bi-calendar-event-fill',
            ];
        }

        // ── KPI 2 — Congés en attente ─────────────────────────────
        if ($isAdmin || $user->hasRole(['rh'])) {
            $pendingLeaves = Leave::pending()->count();
            $kpi2 = [
                'label' => 'Congés en attente',
                'value' => $pendingLeaves,
                'delta' => 'À valider',
                'color' => '#BA7517',
                'bg'    => '#FAEEDA',
                'icon'  => 'bi-calendar-x',
            ];
        } else {
            $pendingLeaves = $employee
                ? Leave::where('employee_id', $employee->id)->pending()->count()
                : 0;
            $kpi2 = [
                'label' => 'Mes demandes en attente',
                'value' => $pendingLeaves,
                'delta' => 'En attente de validation',
                'color' => '#BA7517',
                'bg'    => '#FAEEDA',
                'icon'  => 'bi-hourglass-split',
            ];
        }

        // ── KPI 3 — Jours de congés validés (tous employés) ───────
        $totalApprovedDays = Leave::approved()
            ->whereYear('start_date', now()->year)
            ->sum('duration_days');

        $kpi3 = [
            'label' => 'Jours congés validés',
            'value' => $totalApprovedDays,
            'delta' => 'En '.now()->year,
            'color' => '#3B6D11',
            'bg'    => '#EAF3DE',
            'icon'  => 'bi-calendar-check-fill',
        ];

        // ── KPI 4 — Solde congés de l'utilisateur connecté ────────
        $leaveBalance  = $employee?->leave_balance ?? 0;
        $approvedThisYear = $employee
            ? Leave::where('employee_id', $employee->id)
                ->approved()
                ->whereYear('start_date', now()->year)
                ->sum('duration_days')
            : 0;

        $kpi4 = [
            'label' => 'Mon solde de congés',
            'value' => $leaveBalance,
            'delta' => $approvedThisYear.' jour(s) pris cette année',
            'color' => '#534AB7',
            'bg'    => '#EEEDFE',
            'icon'  => 'bi-bag-check-fill',
        ];

        // ── Demandes en attente (pour validation) ─────────────────
        if ($isAdmin || $user->hasRole(['rh'])) {
            $pendingLeavesList = Leave::with('employee')
                ->pending()
                ->latest()
                ->take(8)
                ->get();
        } else {
            $pendingLeavesList = $employee
                ? Leave::with('employee')
                    ->where('employee_id', $employee->id)
                    ->pending()
                    ->latest()
                    ->take(8)
                    ->get()
                : collect();
        }

        // ── Activité récente (uniquement l'utilisateur connecté) ───
        $recentActivity = $this->buildUserActivity($user, $employee);

        // ── Graphique présence ─────────────────────────────────────
        $presenceLabels = [];
        $presenceData   = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $presenceLabels[] = $month->isoFormat('MMM');

            // Taux de présence réel basé sur les congés approuvés
            $totalEmployees = max(Employee::count(), 1);
            $workingDays    = 22; // jours ouvrables moyens
            $leaveDays      = Leave::approved()
                ->whereYear('start_date', $month->year)
                ->whereMonth('start_date', $month->month)
                ->sum('duration_days');

            $absenceRate    = ($leaveDays / ($totalEmployees * $workingDays)) * 100;
            $presenceData[] = round(max(60, min(100, 100 - $absenceRate)));
        }

        // ── Répartition par rôle (admin uniquement) ───────────────
        $roleStats = [];
        if ($isAdmin) {
            $colors = [
                'superadmin'   => '#dc3545',
                'admin'        => '#185FA5',
                'rh'           => '#534AB7',
                'comptable'    => '#BA7517',
                'informaticien'=> '#3B6D11',
                'user'         => '#6c757d',
            ];
            $totalUsers = max(User::count(), 1);
            $grouped    = [];
            foreach (User::with('roles')->get() as $u) {
                $roleName = $u->roles->first()?->name ?? 'user';
                if (!isset($grouped[$roleName])) {
                    $grouped[$roleName] = [
                        'name'  => ucfirst($roleName),
                        'count' => 0,
                        'color' => $colors[$roleName] ?? '#6c757d',
                    ];
                }
                $grouped[$roleName]['count']++;
            }
            foreach ($grouped as &$stat) {
                $stat['percent'] = round($stat['count'] / $totalUsers * 100);
            }
            usort($grouped, fn($a, $b) => $b['count'] <=> $a['count']);
            $roleStats = $grouped;
        }

        return view('dashboard.index', compact(
            'kpi1', 'kpi2', 'kpi3', 'kpi4',
            'pendingLeaves', 'pendingLeavesList',
            'recentActivity',
            'presenceLabels', 'presenceData',
            'roleStats', 'isAdmin', 'employee'
        ));
    }

    public function stats()
    {
        $user     = auth()->user();
        $employee = $user->employee;

        return response()->json([
            'total_employees'    => Employee::count(),
            'pending_leaves'     => Leave::pending()->count(),
            'approved_days'      => Leave::approved()->whereYear('start_date', now()->year)->sum('duration_days'),
            'my_leave_balance'   => $employee?->leave_balance ?? 0,
        ]);
    }

    // ── Activité de l'utilisateur connecté uniquement ─────────────
    private function buildUserActivity($user, $employee): array
    {
        $activity = [];

        // ── Dernière connexion ────────────────────────────────
        if ($user->last_login_at) {
            $activity[] = [
                'text'  => "Dernière connexion depuis "
                    .($user->last_login_ip ?? 'adresse inconnue'),
                'time'  => $user->last_login_at->diffForHumans(),
                'color' => '#185FA5',
                'icon'  => 'bi-box-arrow-in-right',
            ];
        }

        // ── Mes fiches de paie ────────────────────────────────
        if ($employee) {
            foreach ($employee->payrolls()->latest()->take(2)->get() as $p) {
                $activity[] = [
                    'text'  => "Fiche de paie disponible — {$p->period}",
                    'time'  => $p->created_at->diffForHumans(),
                    'color' => '#3B6D11',
                    'icon'  => 'bi-receipt',
                ];
            }

            // ── Mes congés ────────────────────────────────────
            foreach ($employee->leaves()->latest()->take(3)->get() as $l) {
                $label = match ($l->status) {
                    'approved' => 'approuvée ✅',
                    'rejected' => 'refusée ❌',
                    default    => 'soumise 🕐',
                };
                $color = match ($l->status) {
                    'approved' => '#3B6D11',
                    'rejected' => '#dc3545',
                    default    => '#BA7517',
                };
                $activity[] = [
                    'text'  => "Demande de {$l->type_label} {$label}",
                    'time'  => $l->created_at->diffForHumans(),
                    'color' => $color,
                    'icon'  => 'bi-calendar-event',
                ];
            }
        }

        // ── Mes messages reçus ────────────────────────────────
        foreach ($user->receivedMessages()->with('sender')->latest()->take(2)->get() as $m) {
            $activity[] = [
                'text'  => "Message reçu de {$m->sender->name}",
                'time'  => $m->created_at->diffForHumans(),
                'color' => '#534AB7',
                'icon'  => 'bi-chat-dots',
            ];
        }

        return array_slice($activity, 0, 7);
    }
}
