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

        $allowed = ['gross_salary', 'cnps_employee', 'igr', 'net_salary'];
        $sortBy  = in_array($request->get('sort_by'), $allowed) ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query = Payroll::with('employee')->forPeriod($period)->orderBy($sortBy, $sortDir);

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

    public function printDesign(Payroll $payroll)
    {
        if (auth()->user()->hasRole('user')) {
            abort_if($payroll->employee->user_id !== auth()->id(), 403);
        }

        $payroll->load(['employee.activeContract']);

        $data = [
            'company_name'        => setting('company_name', 'GES-COLAB'),
            'company_initials'    => setting('company_initials', ''),
            'company_address'     => setting('company_address', ''),
            'company_phone'       => setting('company_phone', ''),
            'company_website'     => setting('company_website', ''),
            'reference'           => 'PAY-' . $payroll->period . '-' . $payroll->employee->matricule,
            'generated_date'      => now()->isoFormat('D MMMM YYYY'),
            'generated_at'        => now()->format('d/m/Y à H:i'),
            'period_label'        => \Carbon\Carbon::parse($payroll->period . '-01')->isoFormat('MMMM YYYY'),
            'employee_name'       => $payroll->employee->full_name,
            'employee_matricule'  => $payroll->employee->matricule,
            'employee_position'   => $payroll->employee->position,
            'employee_department' => $payroll->employee->department,
            'employee_seniority'  => $payroll->employee->seniority_label,
            'contract_type'       => strtoupper($payroll->employee->activeContract?->type ?? 'CDI'),
            'worked_days'         => $payroll->worked_days,
            'leave_days'          => $payroll->leave_days,
            'base_salary'         => $payroll->base_salary,
            'seniority_bonus'     => $payroll->seniority_bonus,
            'seniority_rate'      => $payroll->seniority_rate,
            'transport_allowance' => $payroll->transport_allowance,
            'housing_allowance'   => $payroll->housing_allowance,
            'meal_allowance'      => $payroll->meal_allowance ?? 0,
            'gross_salary'        => $payroll->gross_salary,
            'cnps_employee'       => $payroll->cnps_employee,
            'cnps_employee_rate'  => setting('cnps_employee_rate', 6.3),
            'igr'                 => $payroll->igr,
            'other_deductions'    => $payroll->other_deductions ?? 0,
            'net_salary'          => $payroll->net_salary,
            'cnps_employer'       => $payroll->cnps_employer,
            'cnps_employer_rate'  => setting('cnps_employer_rate', 12),
            'total_employer_cost' => $payroll->gross_salary + $payroll->cnps_employer,
        ];

        ob_start();
        $content = (new \App\Pdf\BulletinPaie($data))->build()->Output('S', '');
        ob_end_clean();

        return response()->make($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"bulletin-{$payroll->employee->matricule}-{$payroll->period}.pdf\"",
            'Content-Length'      => strlen($content),
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
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
