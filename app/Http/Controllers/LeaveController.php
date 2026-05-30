<?php

namespace App\Http\Controllers;

use App\Mail\CongeApprouve;
use App\Mail\CongeRefuse;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LeaveController extends Controller
{
    // ── Liste des demandes ────────────────────────────────────
    public function index(Request $request)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        $query = Leave::with(['employee', 'n1Validator', 'approvedBy'])->latest();
        //dd($this->isN1($user));
        if ($user->hasRole(['superadmin', 'admin'])) {
            // Tout voir sans restriction

        } elseif ($user->hasRole('rh')) {
            // RH : demandes qui lui sont destinées (pending_rh + clôturées)
            $query->whereIn('workflow_step', [
                'pending_rh', 'approved', 'rejected',
            ]);

        } elseif ($this->isN1($user) && $employee) {
            // N+1 : SES demandes + celles de ses subalternes directs
            $subordinateIds = $this->getSubordinateIds($employee);

            $query->where(function ($q) use ($user, $subordinateIds) {
                // Ses propres demandes
                $q->whereHas('employee',
                    fn($sub) => $sub->where('user_id', $user->id)
                );

                // OU les demandes de ses subalternes
                if (!empty($subordinateIds)) {
                    $q->orWhereHas('employee',
                        fn($sub) => $sub->whereIn('id', $subordinateIds)
                    );
                }
            });

        } else {
            // Employé standard : ses demandes uniquement
            $query->whereHas('employee',
                fn($q) => $q->where('user_id', $user->id)
            );
        }

        // ── Filtres ───────────────────────────────────────────────
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('workflow_step')) {
            $query->where('workflow_step', $request->workflow_step);
        }
        if ($request->filled('month')) {
            $query->whereYear('start_date',  substr($request->month, 0, 4))
                ->whereMonth('start_date', substr($request->month, 5, 2));
        }
        if ($request->filled('search')) {
            $query->whereHas('employee', fn($q) => $q->search($request->search));
        }
        if ($request->filled('employee')) {
            $query->where('employee_id', $request->employee);
        }

        $leaves       = $query->paginate(20)->withQueryString();
        $pendingCount = $this->getPendingCount($user, $employee);

        return view('conges.index', compact('leaves', 'pendingCount'));
    }

    // ── Formulaire de création ────────────────────────────────
    public function create()
    {
        $user      = auth()->user();
        $employees = Employee::active()->orderBy('last_name')->get();

        return view('conges.create', compact('employees'));
    }

    // ── Enregistrer une nouvelle demande ──────────────────────
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

        $employee = Employee::findOrFail($validated['employee_id']);

        // Vérifier que l'employé a un N+1 pour les permissions
        if ($validated['type'] === 'permission' && !$employee->supervisor_id) {
            return back()
                ->withInput()
                ->with('error',
                    'Cet employé n\'a pas de supérieur hiérarchique (N+1) assigné. '
                    .'Contactez le service RH.'
                );
        }

        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end   = \Carbon\Carbon::parse($validated['end_date']);

        // Vérifier le solde de congés
        $duration = Leave::calculateDays($start, $end);
        if (in_array($validated['type'], ['annual', 'maternity', 'paternity'])
            && $employee->leave_balance < $duration) {
            return back()
                ->withInput()
                ->with('error',
                    "Solde insuffisant. Solde disponible : {$employee->leave_balance} jour(s), "
                    ."demande : {$duration} jour(s)."
                );
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')
                ->store('leaves', 'public');
        }

        // Déterminer l'étape initiale du workflow
        $workflowStep = Leave::initialWorkflowStep($validated['type']);

        Leave::create([
            'employee_id'   => $validated['employee_id'],
            'leave_number'  => Leave::generateNumber(),
            'type'          => $validated['type'],
            'start_date'    => $validated['start_date'],
            'end_date'      => $validated['end_date'],
            'duration_days' => $duration,
            'reason'        => $validated['reason'] ?? null,
            'attachment'    => $attachmentPath,
            'status'        => 'pending',
            'workflow_step' => $workflowStep,
        ]);

        $message = $workflowStep === 'pending_n1'
            ? 'Demande soumise. En attente de validation par votre N+1 ('
            .$employee->supervisor->full_name.').'
            : 'Demande soumise. En attente de validation par le service RH.';

        return redirect()->route('leaves.index')->with('success', $message);
    }

    // ── Détail d'une demande ──────────────────────────────────
    public function show(Leave $leave)
    {
        // Vérifier les droits d'accès
        $this->authorizeView($leave);

        $leave->load(['employee', 'approvedBy', 'n1Validator',
            'employee.supervisor']);

        return view('conges.show', compact('leave'));
    }

    // ── Formulaire d'édition ──────────────────────────────────
    public function edit(Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403,
            'Seules les demandes en attente peuvent être modifiées.');

        // Seul l'employé concerné ou un admin peut modifier
        $user = auth()->user();
        if (!$user->hasRole(['superadmin', 'admin', 'rh'])) {
            abort_if(
                $leave->employee->user_id !== $user->id,
                403, 'Accès non autorisé.'
            );
        }

        $employees = Employee::active()->orderBy('last_name')->get();

        return view('conges.edit', compact('leave', 'employees'));
    }

    // ── Mettre à jour une demande ─────────────────────────────
    public function update(Request $request, Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403);

        $validated = $request->validate([
            'type'       => 'required|in:annual,sick,permission,exceptional,maternity,paternity',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:500',
        ]);

        $start    = \Carbon\Carbon::parse($validated['start_date']);
        $end      = \Carbon\Carbon::parse($validated['end_date']);
        $duration = Leave::calculateDays($start, $end);

        $leave->update([
            ...$validated,
            'duration_days' => $duration,
            'workflow_step' => Leave::initialWorkflowStep($validated['type']),
        ]);

        return redirect()->route('leaves.show', $leave)
            ->with('success', 'Demande mise à jour.');
    }

    // ── Supprimer une demande ─────────────────────────────────
    public function destroy(Leave $leave)
    {
        abort_if($leave->status !== 'pending', 403,
            'Seules les demandes en attente peuvent être supprimées.');

        $user = auth()->user();
        if (!$user->hasRole(['superadmin', 'admin'])) {
            abort_if(
                $leave->employee->user_id !== $user->id,
                403, 'Accès non autorisé.'
            );
        }

        $leave->delete();

        return redirect()->route('leaves.index')
            ->with('success', 'Demande supprimée.');
    }

    // ── Validation N+1 ────────────────────────────────────────
    public function approveN1(Request $request, Leave $leave)
    {
        $user     = auth()->user();
        $employee = $user->employee;

        // Superadmin et admin peuvent toujours valider
        if (!$user->hasRole(['superadmin', 'admin'])) {
            // Vérifier que c'est bien le N+1 de cet employé
            abort_unless(
                $this->isN1OfEmployee($employee, $leave->employee),
                403,
                'Vous n\'êtes pas le N+1 de cet employé.'
            );
        }

        abort_if($leave->workflow_step !== 'pending_n1', 403,
            'Cette demande n\'est pas à l\'étape de validation N+1.');

        $leave->update([
            'workflow_step'   => 'pending_rh',
            'n1_validator_id' => $user->id,
            'n1_validated_at' => now(),
            'n1_comment'      => $request->comment,
        ]);

        return back()->with('success',
            "Permission de {$leave->employee->full_name} "
            ."validée et transmise au RH."
        );
    }

    // ── Refus N+1 ─────────────────────────────────────────────
    public function rejectN1(Request $request, Leave $leave)
    {
        $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        $user     = auth()->user();
        $employee = $user->employee;

        if (!$user->hasRole(['superadmin', 'admin'])) {
            abort_unless(
                $this->isN1OfEmployee($employee, $leave->employee),
                403,
                'Vous n\'êtes pas le N+1 de cet employé.'
            );
        }

        abort_if($leave->workflow_step !== 'pending_n1', 403);

        $leave->update([
            'workflow_step'    => 'rejected',
            'status'           => 'rejected',
            'n1_validator_id'  => $user->id,
            'n1_validated_at'  => now(),
            'n1_comment'       => $request->comment,
            'rejection_reason' => $request->comment,
        ]);

        Mail::to($leave->employee->email)->send(new CongeRefuse($leave, $request->comment));

        return back()->with('success',
            "Demande de {$leave->employee->full_name} refusée."
        );
    }

    // ── Validation RH (validateur final) ──────────────────────
    public function approve(Leave $leave)
    {
        abort_unless(
            auth()->user()->hasRole(['rh', 'admin', 'superadmin']),
            403, 'Action réservée au service RH.'
        );

        abort_if($leave->workflow_step !== 'pending_rh', 403,
            'Cette demande doit d\'abord être validée par le N+1.'
        );

        $leave->update([
            'status'        => 'approved',
            'workflow_step' => 'approved',
            'approved_by'   => auth()->id(),
            'approved_at'   => now(),
        ]);

        // Déduire du solde de congés
        $leave->employee->decrement('leave_balance', $leave->duration_days);

        // Mettre le statut employé à "en congé"
        if (in_array($leave->type, ['annual', 'maternity', 'paternity'])) {
            $leave->employee->update(['status' => 'on_leave']);
        }

        Mail::to($leave->employee->email)->send(new CongeApprouve($leave->fresh('employee')));

        return back()->with('success',
            "Congé de {$leave->employee->full_name} approuvé."
        );
    }

    // ── Refus RH ──────────────────────────────────────────────
    public function reject(Request $request, Leave $leave)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        abort_unless(
            auth()->user()->hasRole(['rh', 'admin', 'superadmin']),
            403, 'Action réservée au service RH.'
        );

        abort_if(!in_array($leave->workflow_step, ['pending_rh', 'pending_n1']),
            403, 'Cette demande ne peut plus être refusée.'
        );

        $leave->update([
            'status'           => 'rejected',
            'workflow_step'    => 'rejected',
            'approved_by'      => auth()->id(),
            'rejection_reason' => $request->reason,
        ]);

        Mail::to($leave->employee->email)->send(new CongeRefuse($leave, $request->reason));

        return back()->with('success',
            "Demande de {$leave->employee->full_name} refusée."
        );
    }

    // ── Imprimer l'attestation design ────────────────────────
    public function printDesign(Leave $leave)
    {
        abort_if($leave->status !== 'approved', 403,
            'Seuls les congés approuvés peuvent être imprimés.'
        );

        $leave->load(['employee', 'approvedBy', 'n1Validator']);

        $data = [
            'reference'           => $leave->leave_number,
            'type_label'          => $leave->type_label,
            'start_date'          => $leave->start_date->format('d/m/Y'),
            'end_date'            => $leave->end_date->format('d/m/Y'),
            'start_iso'           => $leave->start_date->isoFormat('D MMMM YYYY'),
            'end_iso'             => $leave->end_date->isoFormat('D MMMM YYYY'),
            'duration_days'       => $leave->duration_days,
            'employee_name'       => $leave->employee->full_name,
            'employee_matricule'  => $leave->employee->matricule,
            'employee_position'   => $leave->employee->position,
            'employee_department' => $leave->employee->department,
            'approved_by'         => $leave->approvedBy?->name ?? '—',
            'approved_at'         => $leave->approved_at?->format('d/m/Y') ?? '—',
            'n1_validator'        => $leave->n1Validator?->name,
            'n1_validated_at'     => $leave->n1_validated_at?->format('d/m/Y'),
            'company_name'        => setting('company_name', 'GES-COLAB'),
            'company_initials'    => setting('company_initials', ''),
            'company_address'     => setting('company_address', ''),
            'company_phone'       => setting('company_phone', ''),
            'company_website'     => setting('company_website', ''),
            'generated_at'        => now()->format('d/m/Y à H:i'),
            'generated_date'      => now()->isoFormat('D MMMM YYYY'),
        ];

        ob_start();
        $content = (new \App\Pdf\CongeAttestation($data))->build()->Output('S', '');
        ob_end_clean();

        return response()->make($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"attestation-design-{$leave->leave_number}.pdf\"",
            'Content-Length'      => strlen($content),
            'Cache-Control'       => 'private, max-age=0, must-revalidate',
            'Pragma'              => 'public',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // HELPERS PRIVÉS
    // ─────────────────────────────────────────────────────────

    // Vérifier si un user a un rôle N+1
    private function isN1($user): bool
    {
        // Admins : toujours N+1
        if ($user->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        // Vérifier si l'employé a un poste marqué can_be_n1 = true
        return $user->employee?->poste?->can_be_n1 === true;
    }

    // Vérifier si $supervisor est bien le N+1 direct de $employee
    private function isN1OfEmployee(
        ?Employee $supervisor,
        Employee $employee
    ): bool {
        if (!$supervisor) return false;

        // Superadmin et admin peuvent toujours
        if (auth()->user()->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        // Vérifier le lien supervisor_id
        return $employee->supervisor_id === $supervisor->id;
    }

    // Récupérer les IDs des subalternes directs
    private function getSubordinateIds(?Employee $supervisor): array
    {
        if (!$supervisor) return [];

        return Employee::where('supervisor_id', $supervisor->id)
            ->pluck('id')
            ->toArray();
    }

    // Compter les demandes en attente selon le rôle
    private function getPendingCount($user, $employee): int
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            return Leave::whereIn('workflow_step', [
                'pending_n1', 'pending_rh',
            ])->count();
        }

        if ($user->hasRole('rh')) {
            return Leave::where('workflow_step', 'pending_rh')->count();
        }

        if ($this->isN1($user) && $employee) {
            $subordinateIds = $this->getSubordinateIds($employee);
            return Leave::where('workflow_step', 'pending_n1')
                ->whereHas('employee', function ($q) use ($subordinateIds) {
                    $q->whereIn('id', $subordinateIds);
                })
                ->count();
        }

        return $employee
            ? Leave::where('employee_id', $employee->id)
                ->pending()
                ->count()
            : 0;
    }

    // Vérifier les droits de consultation d'une demande
    private function authorizeView(Leave $leave): void
    {
        //dd($leave);
        $user     = auth()->user();
        $employee = $user->employee;

        // Admin et RH voient tout
        if ($user->hasRole(['superadmin', 'admin', 'rh'])) return;

        // N+1 : voit ses propres demandes + celles de ses subalternes
        if ($this->isN1($user) && $employee) {
            // Sa propre demande
            if ($leave->employee->user_id === $user->id) return;

            // Demande d'un subalterne direct
            $subordinateIds = $this->getSubordinateIds($employee);
            if (in_array($leave->employee_id, $subordinateIds)) return;
        }

        // Employé standard : ses propres demandes uniquement
        abort_if(
            $leave->employee->user_id !== $user->id,
            403, 'Accès non autorisé.'
        );
    }
}
