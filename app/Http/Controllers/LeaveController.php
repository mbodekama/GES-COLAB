<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Leave;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = Leave::with('employee')->latest();

        // Filtrer selon le rôle : un utilisateur basique ne voit que ses propres congés
        if (auth()->user()->hasRole('user')) {
            $query->whereHas('employee', fn($q) => $q->where('user_id', auth()->id()));
        }

        if ($request->filled('type'))   $query->where('type', $request->type);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('month')) {
            $query->whereYear('start_date', substr($request->month, 0, 4))
                  ->whereMonth('start_date', substr($request->month, 5, 2));
        }
        if ($request->filled('search')) {
            $query->whereHas('employee', fn($q) => $q->search($request->search));
        }
        if ($request->filled('employee')) {
            $query->where('employee_id', $request->employee);
        }

        $leaves       = $query->paginate(20)->withQueryString();
        $pendingCount = Leave::pending()->count();

        return view('conges.index', compact('leaves', 'pendingCount'));
    }

    public function create()
    {
        $employees = Employee::active()->orderBy('last_name')->get();
        return view('conges.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type'        => 'required|in:annual,sick,permission,exceptional,maternity,paternity',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string|max:500',
            'attachment'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end   = \Carbon\Carbon::parse($validated['end_date']);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leaves', 'public');
        }

        Leave::create([
            'employee_id'   => $validated['employee_id'],
            'leave_number'  => Leave::generateNumber(),
            'type'          => $validated['type'],
            'start_date'    => $validated['start_date'],
            'end_date'      => $validated['end_date'],
            'duration_days' => Leave::calculateDays($start, $end),
            'reason'        => $validated['reason'] ?? null,
            'attachment'    => $attachmentPath,
            'status'        => 'pending',
        ]);

        return redirect()->route('leaves.index')
                         ->with('success', 'Demande de congé soumise avec succès.');
    }

    public function show(Leave $leave)
    {
        $leave->load(['employee', 'approvedBy']);
        return view('conges.show', compact('leave'));
    }

    public function edit(Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403, 'Seules les demandes en attente peuvent être modifiées.');
        $employees = Employee::active()->orderBy('last_name')->get();
        return view('conges.edit', compact('leave', 'employees'));
    }

    public function update(Request $request, Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403);

        $validated = $request->validate([
            'type'       => 'required|in:annual,sick,permission,exceptional,maternity,paternity',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:500',
        ]);

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end   = \Carbon\Carbon::parse($validated['end_date']);

        $leave->update([
            ...$validated,
            'duration_days' => Leave::calculateDays($start, $end),
        ]);

        return redirect()->route('leaves.show', $leave)
                         ->with('success', 'Demande mise à jour.');
    }

    public function destroy(Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403);
        $leave->delete();
        return redirect()->route('leaves.index')
                         ->with('success', 'Demande supprimée.');
    }

    public function approve(Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403, 'Cette demande ne peut plus être approuvée.');

        $leave->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Déduire du solde de congés
        $leave->employee->decrement('leave_balance', $leave->duration_days);

        // Mettre à jour le statut de l'employé si congé annuel
        if (in_array($leave->type, ['annual', 'maternity', 'paternity'])) {
            $leave->employee->update(['status' => 'on_leave']);
        }

        return back()->with('success', "Congé de {$leave->employee->full_name} approuvé.");
    }

    public function reject(Request $request, Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403);

        $leave->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', "Demande de {$leave->employee->full_name} refusée.");
    }

    public function print(Leave $leave)
    {
        abort_if($leave->status !== 'approved', 403, 'Seuls les congés approuvés peuvent être imprimés.');
        $leave->load(['employee', 'approvedBy']);

        $pdf = Pdf::loadView('conges.pdf.attestation', compact('leave'))
                  ->setPaper('a4', 'portrait');

        return $pdf->stream("conge-{$leave->leave_number}.pdf");
    }
}
