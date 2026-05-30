<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Poste;
use App\Models\SalaryGrid;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $allowed = ['contract_number', 'position', 'type', 'start_date', 'end_date', 'base_salary', 'status'];
        $sortBy  = in_array($request->get('sort_by'), $allowed) ? $request->get('sort_by') : 'start_date';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query = Contract::with('employee')->orderBy($sortBy, $sortDir);

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

    public function export(Request $request)
    {
        $query = Contract::with('employee');

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $query->where('contract_number', 'like', "%{$request->search}%")
                  ->orWhereHas('employee', fn($q) => $q->search($request->search));
        }

        $contracts = $query->orderBy('start_date', 'desc')->get();
        $filename  = 'contrats_' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($contracts) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'N° Contrat', 'Employé', 'Email', 'Département', 'Poste',
                'Type', 'Statut', 'Date début', 'Date fin', 'Salaire de base (FCFA)',
            ], ';');

            foreach ($contracts as $c) {
                $statusLabels = [
                    'active' => 'En cours', 'expired' => 'Expiré',
                    'terminated' => 'Résilié', 'renewed' => 'Renouvelé',
                ];
                $typeLabels = [
                    'cdi' => 'CDI', 'cdd' => 'CDD',
                    'internship' => 'Stage', 'consulting' => 'Consulting',
                ];
                fputcsv($out, [
                    $c->contract_number,
                    $c->employee->full_name,
                    $c->employee->email,
                    $c->department ?? '',
                    $c->position ?? '',
                    $typeLabels[$c->type] ?? $c->type,
                    $statusLabels[$c->status] ?? $c->status,
                    $c->start_date->format('d/m/Y'),
                    $c->end_date?->format('d/m/Y') ?? 'Indéterminé',
                    $c->base_salary,
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function create()
    {
        $employees   = Employee::active()->orderBy('last_name')->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();
        $postes      = Poste::active()->orderByDesc('level')->orderBy('title')->get();

        return view('contracts.create', compact('employees', 'salaryGrids', 'postes'));
    }

    public function store(Request $request)
    {
        $this->logEntry(['employee_id' => $request->employee_id, 'type' => $request->type]);
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

        $activityLogs = $contract->activityLogs()->with('user')->take(30)->get();

        $previousContracts = Contract::with('salaryGrid')
            ->where('employee_id', $contract->employee_id)
            ->where('id', '!=', $contract->id)
            ->orderByDesc('start_date')
            ->get();

        return view('contracts.show', compact('contract', 'activityLogs', 'previousContracts'));
    }

    public function edit(Contract $contract)
    {
        $employees   = Employee::active()->orderBy('last_name')->get();
        $salaryGrids = SalaryGrid::active()->orderByDesc('level')->get();
        $postes      = Poste::active()->orderByDesc('level')->orderBy('title')->get();

        return view('contracts.edit', compact('contract', 'employees', 'salaryGrids', 'postes'));
    }

    public function update(Request $request, Contract $contract)
    {
        $this->logEntry(['contract_number' => $contract->contract_number]);
        $validated = $request->validate([
            'type'                => 'required|in:cdi,cdd,internship,consulting',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after:start_date',
            'position'            => 'required|string|max:100',
            'department'          => 'required|string|max:100',
            'base_salary'         => 'required|numeric|min:0',
            'salary_grid_id'      => 'nullable|exists:salary_grids,id',
            'status'              => 'required|in:active,expired,terminated,renewed',
            'notes'               => 'nullable|string',
            'date_resiliation'    => 'nullable|date',
            'date_renouvellement' => 'nullable|date',
        ]);

        // Auto-renseigner la date si non fournie et statut change
        if ($validated['status'] === 'terminated' && empty($validated['date_resiliation'])) {
            $validated['date_resiliation'] = now()->toDateString();
        }
        if ($validated['status'] === 'renewed' && empty($validated['date_renouvellement'])) {
            $validated['date_renouvellement'] = now()->toDateString();
        }

        $contract->update($validated);

        return redirect()->route('contracts.show', $contract)
                         ->with('success', 'Contrat mis à jour.');
    }

    public function destroy(Contract $contract)
    {
        $this->logEntry(['contract_number' => $contract->contract_number]);
        $contract->update([
            'status'           => 'terminated',
            'date_resiliation' => now()->toDateString(),
        ]);
        return redirect()->route('contracts.index')
                         ->with('success', 'Contrat résilié.');
    }

    public function renew(Request $request, Contract $contract)
    {
        $this->logEntry(['contract_number' => $contract->contract_number]);
        $request->validate([
            'end_date' => 'required|date|after:today',
        ]);

        $contract->update([
            'status'              => 'renewed',
            'date_renouvellement' => now()->toDateString(),
        ]);

        $newContract = $contract->replicate();
        $newContract->contract_number = Contract::generateNumber();
        $newContract->start_date      = $contract->end_date ?? now();
        $newContract->end_date        = $request->end_date;
        $newContract->status          = 'active';
        $newContract->signed_at       = now();
        $newContract->save();

        return back()->with('success', 'Contrat renouvelé avec succès.');
    }

    public function printDesign(Contract $contract)
    {
        $contract->load(['employee', 'salaryGrid']);

        $statusLabels = [
            'active'     => 'En cours',
            'expired'    => 'Expiré',
            'terminated' => 'Résilié',
            'renewed'    => 'Renouvelé',
        ];

        $data = [
            'company_name'      => setting('company_name', 'GES-COLAB'),
            'company_initials'  => setting('company_initials', ''),
            'company_address'   => setting('company_address', ''),
            'company_phone'     => setting('company_phone', ''),
            'company_website'   => setting('company_website', ''),
            'reference'         => $contract->contract_number,
            'generated_date'    => now()->isoFormat('D MMMM YYYY'),
            'generated_at'      => now()->format('d/m/Y à H:i'),
            'type_label'        => $contract->type_label,
            'type'              => $contract->type,
            'contract_number'   => $contract->contract_number,
            'position'          => $contract->position,
            'department'        => $contract->department,
            'start_date'        => $contract->start_date->isoFormat('D MMMM YYYY'),
            'end_date'          => $contract->end_date?->isoFormat('D MMMM YYYY') ?? 'Indéterminé',
            'trial_end_date'    => $contract->trial_end_date?->isoFormat('D MMMM YYYY'),
            'base_salary'       => $contract->base_salary,
            'salary_grid'       => $contract->salaryGrid?->name,
            'signed_at'         => $contract->signed_at?->isoFormat('D MMMM YYYY') ?? '—',
            'status'            => $contract->status,
            'status_label'      => $statusLabels[$contract->status] ?? $contract->status,
            'notes'             => $contract->notes,
            'employee_name'     => $contract->employee->full_name,
            'employee_matricule'=> $contract->employee->matricule,
            'employee_email'    => $contract->employee->email,
        ];

        ob_start();
        $content = (new \App\Pdf\ContratTravail($data))->build()->Output('S', '');
        ob_end_clean();

        return response()->make($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"contrat-{$contract->contract_number}.pdf\"",
            'Content-Length'      => strlen($content),
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

}
