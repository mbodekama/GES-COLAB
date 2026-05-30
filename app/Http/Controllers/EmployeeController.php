<?php

namespace App\Http\Controllers;

use App\Mail\CompteCree;
use App\Models\Employee;
use App\Models\Poste;
use App\Models\SalaryGrid;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $this->logEntry();
        $allowed = ['matricule', 'last_name', 'position', 'department', 'hire_date', 'status'];
        $sortBy  = in_array($request->get('sort_by'), $allowed) ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query = Employee::with(['activeContract', 'poste'])->orderBy($sortBy, $sortDir);

        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('contract_type')) {
            $query->whereHas('activeContract',
                fn($q) => $q->where('type', $request->contract_type)
            );
        }
        if ($request->filled('poste_id')) {
            $query->where('poste_id', $request->poste_id);
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $employees   = $query->paginate(5)->withQueryString();
        $departments = Employee::distinct()->orderBy('department')->pluck('department');
        $postes      = Poste::active()->orderedByLevel()->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();

        return view('employees.index', compact(
            'employees', 'departments', 'postes', 'salaryGrids'
        ));
    }

    public function export(Request $request)
    {
        $this->logEntry();

        $query = Employee::with(['activeContract', 'poste']);

        if ($request->filled('department'))    $query->byDepartment($request->department);
        if ($request->filled('status'))        $query->where('status', $request->status);
        if ($request->filled('contract_type')) $query->whereHas('activeContract',
            fn($q) => $q->where('type', $request->contract_type));
        if ($request->filled('search'))        $query->search($request->search);

        $employees = $query->orderBy('last_name')->orderBy('first_name')->get();

        $filename = 'employes_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->streamDownload(function () use ($employees) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 pour ouverture correcte dans Excel (Windows)
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Matricule', 'Prénom', 'Nom', 'Email', 'Téléphone',
                'Département', 'Poste', 'Date d\'embauche',
                'Ancienneté (ans)', 'Statut', 'Type contrat', 'Solde congés',
            ], ';');

            foreach ($employees as $emp) {
                fputcsv($out, [
                    $emp->matricule,
                    $emp->first_name,
                    $emp->last_name,
                    $emp->email,
                    $emp->phone ?? '',
                    $emp->department ?? '',
                    $emp->position_label,
                    $emp->hire_date?->format('d/m/Y') ?? '',
                    $emp->seniority_years,
                    $emp->status,
                    $emp->activeContract?->type ?? '',
                    $emp->leave_balance ?? 0,
                ], ';');
            }

            fclose($out);
        }, $filename, $headers);
    }

    public function create()
    {
        $postes      = Poste::active()->orderedByLevel()->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();
        $roles       = Role::orderBy('name')->get();

        return view('employees.create', compact('postes', 'salaryGrids', 'roles'));
    }

    public function store(Request $request)
    {
        $this->logEntry(['email' => $request->input('email')]);

        $validated = $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'email'             => 'required|email|unique:employees,email|unique:users,email',
            'phone'             => 'nullable|string|max:20',
            'birth_date'        => 'nullable|date',
            'birth_place'       => 'nullable|string|max:100',
            'nationality'       => 'nullable|string|max:60',
            'marital_status'    => 'required|in:single,married,divorced,widowed',
            'children_count'    => 'nullable|integer|min:0',
            'address'           => 'nullable|string',
            'cnps_number'       => 'nullable|string|max:30',
            'poste_id'          => 'required|exists:postes,id',
            'department'        => 'required|string|max:100',
            'hire_date'         => 'required|date',
            'leave_balance'     => 'nullable|integer|min:0',
            'supervisor_id'     => 'nullable|exists:employees,id',
            'contract_type'     => 'required|in:cdi,cdd,internship,consulting',
            'base_salary'       => 'required|numeric|min:0',
            'salary_grid_id'    => 'nullable|exists:salary_grids,id',
            'contract_end_date' => 'nullable|date|after:hire_date',
            'role'              => 'required|string|exists:roles,name',
            'password'          => 'required|string|min:8|confirmed',
        ]);

        // Récupérer le titre du poste
        $poste = Poste::findOrFail($validated['poste_id']);

        $createdUser = null;

        DB::transaction(function () use ($validated, $poste, &$createdUser) {

            $createdUser = User::create([
                'name'     => $validated['first_name'].' '.$validated['last_name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $createdUser->assignRole($validated['role']);

            $employee = Employee::create([
                'user_id'        => $createdUser->id,
                'matricule'      => Employee::generateMatricule(),
                'first_name'     => $validated['first_name'],
                'last_name'      => $validated['last_name'],
                'email'          => $validated['email'],
                'phone'          => $validated['phone']          ?? null,
                'birth_date'     => $validated['birth_date']     ?? null,
                'birth_place'    => $validated['birth_place']    ?? null,
                'nationality'    => $validated['nationality']    ?? 'Ivoirienne',
                'marital_status' => $validated['marital_status'],
                'children_count' => $validated['children_count'] ?? 0,
                'address'        => $validated['address']        ?? null,
                'cnps_number'    => $validated['cnps_number']    ?? null,
                'position'       => $poste->title,
                'poste_id'       => $validated['poste_id'],
                'department'     => $validated['department'],
                'hire_date'      => $validated['hire_date'],
                'leave_balance'  => $validated['leave_balance']  ?? 30,
                'supervisor_id'  => $validated['supervisor_id']  ?? null,
                'status'         => 'active',
            ]);

            $employee->contracts()->create([
                'contract_number' => \App\Models\Contract::generateNumber(),
                'type'            => $validated['contract_type'],
                'start_date'      => $validated['hire_date'],
                'end_date'        => $validated['contract_end_date'] ?? null,
                'position'        => $poste->title,
                'department'      => $validated['department'],
                'base_salary'     => $validated['base_salary'],
                'salary_grid_id'  => $validated['salary_grid_id'] ?? null,
                'status'          => 'active',
                'signed_at'       => now(),
            ]);
        });

        Mail::to($createdUser->email)->send(new CompteCree($createdUser, $validated['password']));

        Log::info('Nouvel employé créé', [
            'matricule'  => Employee::latest()->value('matricule'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('employees.index')
                         ->with('success', 'Employé créé avec succès.');
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'activeContract',
            'leaves',
            'payrolls',
            'poste',
            'supervisor',
            'supervisor.poste',
            'subordinates',
            'subordinates.poste',
        ]);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $postes      = Poste::active()->orderedByLevel()->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();
        $roles       = Role::orderBy('name')->get();

        return view('employees.edit', compact(
            'employee', 'postes', 'salaryGrids', 'roles'
        ));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|unique:employees,email,'.$employee->id,
            'phone'          => 'nullable|string|max:20',
            'birth_date'     => 'nullable|date',
            'birth_place'    => 'nullable|string|max:100',
            'nationality'    => 'nullable|string|max:60',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'children_count' => 'nullable|integer|min:0',
            'address'        => 'nullable|string',
            'cnps_number'    => 'nullable|string|max:30',
            'poste_id'       => 'required|exists:postes,id',
            'department'     => 'required|string|max:100',
            'hire_date'      => 'required|date',
            'leave_balance'  => 'nullable|integer|min:0',
            'supervisor_id'  => 'nullable|exists:employees,id',
            'status'         => 'required|in:active,on_leave,suspended,terminated',
        ]);

        // Mettre à jour le champ position texte depuis le poste
        $poste = Poste::find($validated['poste_id']);
        if ($poste) {
            $validated['position'] = $poste->title;
        }

        DB::transaction(function () use ($employee, $validated, $request) {
            $employee->update($validated);

            $employee->user?->update([
                'name'  => $validated['first_name'].' '.$validated['last_name'],
                'email' => $validated['email'],
            ]);

            if ($request->filled('role')) {
                $employee->user?->syncRoles([$request->role]);
            }
        });

        return redirect()->route('employees.show', $employee)
                         ->with('success', 'Employé mis à jour.');
    }

    public function destroy(Employee $employee)
    {
        $this->logEntry(['matricule' => $employee->matricule]);

        $employee->delete();

        return redirect()->route('employees.index')
                         ->with('success', 'Employé archivé.');
    }

    public function printDesign(Employee $employee)
    {
        $this->logEntry(['matricule' => $employee->matricule]);

        $employee->load(['activeContract', 'leaves', 'poste', 'supervisor']);

        $data = [
            'company_name'     => setting('company_name', 'GES-COLAB'),
            'company_initials' => setting('company_initials', ''),
            'company_address'  => setting('company_address', ''),
            'company_phone'    => setting('company_phone', ''),
            'company_website'  => setting('company_website', ''),
            'generated_at'     => now()->format('d/m/Y à H:i'),
            'generated_date'  => now()->isoFormat('D MMMM YYYY'),

            'matricule'       => $employee->matricule,
            'full_name'       => $employee->full_name,
            'initials'        => $employee->initials,
            'position'        => $employee->position,
            'department'      => $employee->department,
            'status'          => $employee->status,
            'status_label'    => ucfirst($employee->status),

            'birth_date'      => $employee->birth_date?->format('d/m/Y') ?? '—',
            'birth_place'     => $employee->birth_place ?? '—',
            'nationality'     => $employee->nationality ?? '—',
            'marital_status'  => $employee->marital_status_label,
            'children_count'  => (string) $employee->children_count,
            'cnps_number'     => $employee->cnps_number ?? '—',
            'phone'           => $employee->phone ?? '—',
            'email'           => $employee->email,
            'address'         => $employee->address ?? '—',

            'hire_date'       => $employee->hire_date->format('d/m/Y'),
            'seniority'       => $employee->seniority_label,
            'leave_balance'   => $employee->leave_balance . ' jours',

            'contract_number' => $employee->activeContract?->contract_number ?? '—',
            'contract_type'   => $employee->activeContract ? strtoupper($employee->activeContract->type) : '—',
            'contract_start'  => $employee->activeContract?->start_date->format('d/m/Y') ?? '—',
            'contract_end'    => $employee->activeContract?->end_date?->format('d/m/Y') ?? 'Indéterminé',
            'base_salary'     => $employee->activeContract
                ? number_format($employee->activeContract->base_salary, 0, ',', ' ') . ' FCFA'
                : '—',

            'leaves'          => $employee->leaves()
                ->latest()
                ->take(6)
                ->get()
                ->map(fn($l) => [
                    'number' => $l->leave_number,
                    'type'   => $l->type_label,
                    'start'  => $l->start_date->format('d/m/Y'),
                    'end'    => $l->end_date->format('d/m/Y'),
                    'days'   => (string) $l->duration_days,
                    'status' => ucfirst($l->status),
                ])
                ->toArray(),
        ];

        ob_start();
        $content = (new \App\Pdf\EmployeeFiche($data))->build()->Output('S', '');
        ob_end_clean();

        return response()->make($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"fiche-design-{$employee->matricule}.pdf\"",
            'Content-Length'      => strlen($content),
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    public function search(Request $request)
    {
        $employees = Employee::search($request->q ?? '')
            ->active()
            ->with('poste')
            ->take(10)
            ->get(['id', 'first_name', 'last_name', 'position',
                   'department', 'matricule', 'poste_id']);

        return response()->json(
            $employees->map(fn($e) => [
                'id'         => $e->id,
                'full_name'  => $e->full_name,
                'position'   => $e->position_label,
                'department' => $e->department,
                'matricule'  => $e->matricule,
            ])
        );
    }
}
