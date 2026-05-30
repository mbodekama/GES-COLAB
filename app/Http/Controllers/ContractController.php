<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\SalaryGrid;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with('employee')->latest('start_date');

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $query->where('contract_number', 'like', "%{$request->search}%")
                  ->orWhereHas('employee', fn($q) => $q->search($request->search));
        }

        $contracts      = $query->paginate(20)->withQueryString();
        $expiringCount  = Contract::expiringSoon()->count();

        return view('contracts.index', compact('contracts', 'expiringCount'));
    }

    public function create()
    {
        $employees   = Employee::active()->orderBy('last_name')->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();

        return view('contracts.create', compact('employees', 'salaryGrids'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'type'           => 'required|in:cdi,cdd,internship,consulting',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after:start_date',
            'trial_end_date' => 'nullable|date',
            'position'       => 'required|string|max:100',
            'department'     => 'required|string|max:100',
            'base_salary'    => 'required|numeric|min:0',
            'salary_grid_id' => 'nullable|exists:salary_grids,id',
            'notes'          => 'nullable|string',
        ]);

        Contract::create([
            ...$validated,
            'contract_number' => Contract::generateNumber(),
            'status'          => 'active',
            'signed_at'       => now(),
        ]);

        return redirect()->route('contracts.index')
                         ->with('success', 'Contrat créé avec succès.');
    }

    public function show(Contract $contract)
    {
        $contract->load(['employee', 'salaryGrid']);
        return view('contracts.show', compact('contract'));
    }

    public function edit(Contract $contract)
    {
        $employees   = Employee::active()->orderBy('last_name')->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();

        return view('contracts.edit', compact('contract', 'employees', 'salaryGrids'));
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'type'           => 'required|in:cdi,cdd,internship,consulting',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after:start_date',
            'position'       => 'required|string|max:100',
            'department'     => 'required|string|max:100',
            'base_salary'    => 'required|numeric|min:0',
            'salary_grid_id' => 'nullable|exists:salary_grids,id',
            'status'         => 'required|in:active,expired,terminated,renewed',
            'notes'          => 'nullable|string',
        ]);

        $contract->update($validated);

        return redirect()->route('contracts.show', $contract)
                         ->with('success', 'Contrat mis à jour.');
    }

    public function destroy(Contract $contract)
    {
        $contract->update(['status' => 'terminated']);
        return redirect()->route('contracts.index')
                         ->with('success', 'Contrat résilié.');
    }

    public function renew(Request $request, Contract $contract)
    {
        $request->validate([
            'end_date' => 'required|date|after:today',
        ]);

        $contract->update(['status' => 'renewed']);

        $newContract = $contract->replicate();
        $newContract->contract_number = Contract::generateNumber();
        $newContract->start_date      = $contract->end_date ?? now();
        $newContract->end_date        = $request->end_date;
        $newContract->status          = 'active';
        $newContract->signed_at       = now();
        $newContract->save();

        return back()->with('success', 'Contrat renouvelé avec succès.');
    }

}
