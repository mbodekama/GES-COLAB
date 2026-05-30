<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', now()->format('Y-m'));

        $query = Payroll::with('employee')->forPeriod($period);

        if ($request->filled('department')) {
            $query->whereHas('employee', fn($q) => $q->where('department', $request->department));
        }

        // Un utilisateur basique ne voit que sa propre fiche
        if (auth()->user()->hasRole('user')) {
            $query->whereHas('employee', fn($q) => $q->where('user_id', auth()->id()));
        }

        $payrolls      = $query->paginate(20)->withQueryString();
        $currentPeriod = \Carbon\Carbon::parse($period . '-01')->isoFormat('MMMM YYYY');
        $generatedCount = Payroll::forPeriod($period)->count();
        $totalGross    = Payroll::forPeriod($period)->sum('gross_salary');
        $totalNet      = Payroll::forPeriod($period)->sum('net_salary');
        $departments   = Employee::distinct()->orderBy('department')->pluck('department');

        return view('paie.index', compact(
            'payrolls', 'currentPeriod', 'generatedCount',
            'totalGross', 'totalNet', 'departments', 'period'
        ));
    }

    public function show(Payroll $payroll)
    {
        // Un employé ne peut voir que sa propre fiche
        if (auth()->user()->hasRole('user')) {
            abort_if($payroll->employee->user_id !== auth()->id(), 403);
        }

        $payroll->load('employee');
        return view('paie.show', compact('payroll'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'period'     => 'required|date_format:Y-m',
            'department' => 'nullable|string',
        ]);

        $period = $request->period;

        $query = Employee::with('activeContract')->active();
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        $employees = $query->get();
        $generated = 0;

        DB::transaction(function () use ($employees, $period, &$generated) {
            foreach ($employees as $employee) {
                $this->generateForEmployee($employee, $period);
                $generated++;
            }
        });

        return redirect()->route('payroll.index', ['period' => $period])
                         ->with('success', "{$generated} fiche(s) de paie générée(s) pour {$period}.");
    }

    // ── Logique de génération ────────────────────────────────
    private function generateForEmployee(Employee $employee, string $period): Payroll
    {
        $contract = $employee->activeContract;
        $baseSalary = $contract?->base_salary ?? 0;

        // Ancienneté
        $seniorityYears = $employee->seniority_years;
        $seniorityRate  = Payroll::seniorityRate($seniorityYears);
        $seniorityBonus = round($baseSalary * $seniorityRate / 100);

        // Indemnités
        $transport = (float) config('gescolab.transport_allowance', 30000);
        $housing   = (float) config('gescolab.housing_allowance', 25000);

        // Brut
        $grossSalary = $baseSalary + $seniorityBonus + $transport + $housing;

        // CNPS salarié
        $cnpsEmployeeRate = (float) config('gescolab.cnps_employee_rate', 6.3);
        $cnpsEmployee     = round($grossSalary * $cnpsEmployeeRate / 100);

        // CNPS employeur
        $cnpsEmployerRate = (float) config('gescolab.cnps_employer_rate', 12);
        $cnpsEmployer     = round($grossSalary * $cnpsEmployerRate / 100);

        // IGR (base imposable = brut - CNPS salarié)
        $imposable = $grossSalary - $cnpsEmployee;
        $igr       = Payroll::calculateIGR($imposable);

        // Net
        $netSalary = $grossSalary - $cnpsEmployee - $igr;

        // Jours de congé pris sur la période
        $leaveDays = $employee->leaves()
            ->approved()
            ->where(function ($q) use ($period) {
                $q->whereYear('start_date', substr($period, 0, 4))
                  ->whereMonth('start_date', substr($period, 5, 2));
            })
            ->sum('duration_days');

        return Payroll::updateOrCreate(
            ['employee_id' => $employee->id, 'period' => $period],
            [
                'base_salary'         => $baseSalary,
                'seniority_bonus'     => $seniorityBonus,
                'seniority_rate'      => $seniorityRate,
                'transport_allowance' => $transport,
                'housing_allowance'   => $housing,
                'gross_salary'        => $grossSalary,
                'cnps_employee'       => $cnpsEmployee,
                'cnps_employer'       => $cnpsEmployer,
                'igr'                 => $igr,
                'net_salary'          => $netSalary,
                'worked_days'         => 26 - $leaveDays,
                'leave_days'          => $leaveDays,
            ]
        );
    }
}
