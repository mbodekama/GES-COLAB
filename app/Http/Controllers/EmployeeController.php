<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\SalaryGrid;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('activeContract')->latest();

        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('contract_type')) {
            $query->whereHas('activeContract', fn($q) => $q->where('type', $request->contract_type));
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $employees   = $query->paginate(20)->withQueryString();
        $departments = Employee::distinct()->orderBy('department')->pluck('department');
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();

        return view('employees.index', compact('employees', 'departments', 'salaryGrids'));
    }

    public function create()
    {
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();
        $roles       = Role::orderBy('name')->get();

        // Employés pouvant être N+1
        $supervisors = $this->getSupervisors();

        return view('employees.create', compact('salaryGrids', 'roles', 'supervisors'));
    }

    public function store(Request $request)
    {
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
            'position'          => 'required|string|max:100',
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

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'     => $validated['first_name'].' '.$validated['last_name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->assignRole($validated['role']);

            $employee = Employee::create([
                'user_id'        => $user->id,
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
                'position'       => $validated['position'],
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
                'position'        => $validated['position'],
                'department'      => $validated['department'],
                'base_salary'     => $validated['base_salary'],
                'salary_grid_id'  => $validated['salary_grid_id'] ?? null,
                'status'          => 'active',
                'signed_at'       => now(),
            ]);
        });

        return redirect()->route('employees.index')
            ->with('success', 'Employé créé avec succès.');
    }

    public function edit(Employee $employee)
    {
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();
        $roles       = Role::orderBy('name')->get();

        // Exclure l'employé lui-même de la liste des N+1
        $supervisors = $this->getSupervisors($employee->id);

        return view('employees.edit', compact(
            'employee', 'salaryGrids', 'roles', 'supervisors'
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
            'position'       => 'required|string|max:100',
            'department'     => 'required|string|max:100',
            'hire_date'      => 'required|date',
            'leave_balance'  => 'nullable|integer|min:0',
            'supervisor_id'  => 'nullable|exists:employees,id',
            'status'         => 'required|in:active,on_leave,suspended,terminated',
        ]);

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

// ── Helper : récupérer les superviseurs potentiels ─────────────
    private function getSupervisors(?int $excludeId = null): \Illuminate\Support\Collection
    {
        return Employee::with('user.roles')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->whereHas('user.roles', function ($q) {
                $q->whereIn('name', [
                    'superadmin',
                    'admin',
                    'rh',
                    'superviseur',
                    'chef d\'agence',
                    'responsable de distribution',
                    'chef de service',
                    'dgo',
                ]);
            })
            ->orderBy('last_name')
            ->get();
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'activeContract',
            'leaves',
            'payrolls',
            'supervisor',
            'subordinates',
        ]);

        return view('employees.show', compact('employee'));
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')
                         ->with('success', 'Employé archivé.');
    }

    public function print(Employee $employee)
    {
        $employee->load(['activeContract', 'leaves']);
        $pdf = Pdf::loadView('employees.pdf.fiche', compact('employee'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("fiche-{$employee->matricule}.pdf");
    }

    public function search(Request $request)
    {
        $employees = Employee::search($request->q ?? '')
            ->active()
            ->take(10)
            ->get(['id', 'first_name', 'last_name', 'position', 'department', 'matricule']);

        return response()->json($employees->map(fn($e) => [
            'id'         => $e->id,
            'full_name'  => $e->full_name,
            'position'   => $e->position,
            'department' => $e->department,
            'matricule'  => $e->matricule,
        ]));
    }
}
