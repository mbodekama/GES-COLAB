<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Poste;
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
        $query = Employee::with(['activeContract', 'poste'])->latest();

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

        $employees   = $query->paginate(20)->withQueryString();
        $departments = Employee::distinct()->orderBy('department')->pluck('department');
        $postes      = Poste::active()->orderedByLevel()->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();

        return view('employees.index', compact(
            'employees', 'departments', 'postes', 'salaryGrids'
        ));
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

        DB::transaction(function () use ($validated, $poste) {

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
        $employee->delete();

        return redirect()->route('employees.index')
                         ->with('success', 'Employé archivé.');
    }

    public function print(Employee $employee)
    {
        $employee->load(['activeContract', 'leaves', 'poste', 'supervisor']);

        $pdf = Pdf::loadView('employees.pdf.fiche', compact('employee'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("fiche-{$employee->matricule}.pdf");
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
