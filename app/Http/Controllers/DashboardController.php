<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // ── KPIs ─────────────────────────────────────────────
        $totalEmployees      = Employee::count();
        $newEmployeesThisMonth = Employee::whereMonth('created_at', now()->month)
                                         ->whereYear('created_at', now()->year)
                                         ->count();

        $pendingLeaves       = Leave::pending()->count();
        $payrollsThisMonth   = Payroll::forPeriod(now()->format('Y-m'))->count();

        $masseSalariale      = Payroll::forPeriod(now()->format('Y-m'))->sum('gross_salary');

        // ── Demandes en attente (pour validation) ────────────
        $pendingLeavesList = Leave::with('employee')
            ->pending()
            ->latest()
            ->take(8)
            ->get();

        // ── Activité récente ─────────────────────────────────
        $recentActivity = $this->buildRecentActivity();

        // ── Graphique présence ───────────────────────────────
        $presenceLabels = [];
        $presenceData   = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $presenceLabels[] = $month->isoFormat('MMM');
            // Simulation : taux de présence (en prod, calculer depuis les pointages)
            $presenceData[] = rand(82, 97);
        }

        // ── Répartition par rôle ─────────────────────────────
        $colors = [
            'superadmin'  => '#dc3545',
            'admin'       => '#185FA5',
            'rh'          => '#534AB7',
            'comptable'   => '#BA7517',
            'informaticien' => '#3B6D11',
            'user'        => '#6c757d',
        ];

        $totalUsers = User::count();
        $roleStats  = [];
        foreach (User::all() as $u) {
            $roleName = $u->roles->first()?->name ?? 'user';
            if (!isset($roleStats[$roleName])) {
                $roleStats[$roleName] = ['name' => ucfirst($roleName), 'count' => 0, 'color' => $colors[$roleName] ?? '#6c757d'];
            }
            $roleStats[$roleName]['count']++;
        }
        foreach ($roleStats as &$stat) {
            $stat['percent'] = $totalUsers > 0 ? round($stat['count'] / $totalUsers * 100) : 0;
        }
        usort($roleStats, fn($a, $b) => $b['count'] <=> $a['count']);

        return view('dashboard.index', compact(
            'totalEmployees', 'newEmployeesThisMonth',
            'pendingLeaves', 'pendingLeavesList',
            'payrollsThisMonth', 'masseSalariale',
            'recentActivity', 'presenceLabels', 'presenceData',
            'roleStats'
        ));
    }

    public function stats()
    {
        return response()->json([
            'total_employees'   => Employee::count(),
            'pending_leaves'    => Leave::pending()->count(),
            'masse_salariale'   => Payroll::forPeriod(now()->format('Y-m'))->sum('gross_salary'),
            'payrolls_generated'=> Payroll::forPeriod(now()->format('Y-m'))->count(),
        ]);
    }

    private function buildRecentActivity(): array
    {
        $activity = [];

        // Dernières fiches de paie
        foreach (Payroll::with('employee')->latest()->take(2)->get() as $p) {
            $activity[] = [
                'text'  => "Fiche de paie générée pour {$p->employee->full_name}",
                'time'  => $p->created_at->diffForHumans(),
                'color' => '#3B6D11',
            ];
        }

        // Derniers congés
        foreach (Leave::with('employee')->latest()->take(3)->get() as $l) {
            $label = match ($l->status) {
                'approved' => 'approuvée',
                'rejected' => 'refusée',
                default    => 'soumise',
            };
            $color = match ($l->status) {
                'approved' => '#3B6D11',
                'rejected' => '#dc3545',
                default    => '#BA7517',
            };
            $activity[] = [
                'text'  => "Demande de congé {$label} — {$l->employee->full_name}",
                'time'  => $l->created_at->diffForHumans(),
                'color' => $color,
            ];
        }

        // Derniers employés
        foreach (Employee::latest()->take(2)->get() as $e) {
            $activity[] = [
                'text'  => "Nouvel employé enregistré — {$e->full_name}",
                'time'  => $e->created_at->diffForHumans(),
                'color' => '#185FA5',
            ];
        }

        // Trier par date (approximatif via diffForHumans)
        return array_slice($activity, 0, 8);
    }
}
