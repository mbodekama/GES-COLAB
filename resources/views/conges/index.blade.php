@extends('layouts.app')
@section('page-title', 'Congés & Permissions')

@section('header-actions')
    @can('créer congés')
    <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Nouvelle demande
    </a>
    @endcan
@endsection

@section('content')

<div class="filter-card">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label>Type</label>
            <x-select
                name="type"
                :options="['annual' => 'Congé annuel', 'sick' => 'Maladie', 'permission' => 'Permission', 'exceptional' => 'Exceptionnel', 'maternity' => 'Maternité', 'paternity' => 'Paternité']"
                :value="request('type')"
                placeholder="Tous"
                class="form-select-sm"
            />
        </div>
        <div class="col-6 col-md-2">
            <label>Statut</label>
            <x-select
                name="status"
                :options="['pending' => 'En attente', 'approved' => 'Approuvé', 'rejected' => 'Refusé']"
                :value="request('status')"
                placeholder="Tous"
                class="form-select-sm"
            />
        </div>
        <div class="col-6 col-md-2">
            <label>Étape</label>
            <x-select
                name="workflow_step"
                :options="['pending_n1' => 'En attente N+1', 'pending_rh' => 'En attente RH', 'approved' => 'Approuvé', 'rejected' => 'Refusé']"
                :value="request('workflow_step')"
                placeholder="Toutes"
                class="form-select-sm"
            />
        </div>
        <div class="col-6 col-md-2">
            <label>Mois</label>
            <input type="month" name="month" value="{{ request('month') }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-4">
            <label>Employé</label>
            <div class="search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Nom de l'employé...">
            </div>
        </div>
        <div class="col-12 col-md-auto ms-auto d-flex justify-content-end gap-2">
            <button class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i> Lancer la recherche
            </button>
            <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span>Demandes <span class="text-muted fw-normal">({{ $leaves->total() }})</span></span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark">{{ $pendingCount }} en attente</span>
            <a href="{{ route('leaves.export', request()->query()) }}"
               class="btn btn-outline-success btn-sm"
               title="Télécharger la liste filtrée en Excel">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <x-sort-th column="leave_number" label="N°" />
                <th>Employé</th>
                <x-sort-th column="type" label="Type" />
                <x-sort-th column="start_date" label="Début" />
                <x-sort-th column="end_date" label="Fin" />
                <x-sort-th column="duration_days" label="Durée" />
                <th>Étapes</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($leaves as $leave)
        <tr>
            <td class="text-muted small">{{ $leave->leave_number }}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <x-avatar :initials="$leave->employee->initials" size="sm" />
                    <span class="fw-medium">{{ $leave->employee->full_name }}</span>
                </div>
            </td>
            <td><span class="badge bg-secondary badge-status">{{ $leave->type_label }}</span></td>
            <td class="small">{{ $leave->start_date->format('d M Y') }}</td>
            <td class="small">{{ $leave->end_date->format('d M Y') }}</td>
            <td><strong>{{ $leave->duration_days }}j</strong></td>
            <td>{!! $leave->workflow_badge !!}</td>
            <td class="text-center">
                <div class="btn-group btn-group-md d-flex justify-content-start gap-2">
                    <div>
                        <a href="{{ route('leaves.show', $leave) }}"
                           class="btn btn-outline-secondary" title="Voir">
                            <i class="bi bi-eye"></i> &nbsp; Voir
                        </a>
                    </div>


                    {{-- Boutons N+1 : uniquement sur les demandes des subalternes,
                         PAS sur ses propres demandes --}}

                    @if($leave->workflow_step === 'pending_n1'
                        && $leave->employee->user_id !== auth()->id())

                        @php $canValidateN1 = auth()->user()->hasRole(['superadmin','admin'])
                       || auth()->user()->employee?->poste?->can_be_n1 === true; @endphp

                        @if($canValidateN1)
                            <form method="POST"
                                  action="{{ route('leaves.approve.n1', $leave) }}"
                                  class="d-inline">
                                @csrf
                                <button class="btn btn-outline-info" title="Valider (N+1)">
                                    <i class="bi bi-check-lg"></i> &nbsp; Valider
                                </button>
                            </form>

                        @endif

                    @endif

                    {{-- Boutons RH : uniquement sur les demandes en pending_rh --}}
                    @if($leave->workflow_step === 'pending_rh')
                        @can('valider congés')
                            <form method="POST"
                                  action="{{ route('leaves.approve', $leave) }}"
                                  class="d-inline">
                                @csrf
                                <button class="btn btn-outline-success" title="Approuver (RH)">
                                    <i class="bi bi-check-circle"></i> &nbsp; Approuver
                                </button>
                            </form>

                        @endcan
                    @endif

                    {{-- Impression : uniquement si approuvé --}}
                    @if($leave->status === 'approved')
                        <div>
                            <a href="{{ route('leaves.print.design', $leave) }}"
                               class="btn btn-primary "
                               title="Attestation PDF" target="_blank">
                                <i class="bi bi-file-earmark-richtext"></i> PDF
                            </a>
                        </div>

                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-25"></i>
                Aucune demande trouvée
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-muted">
            {{ $leaves->firstItem() ?? 0 }}–{{ $leaves->lastItem() ?? 0 }} sur {{ $leaves->total() }}
        </small>
        {{ $leaves->links() }}
    </div>
</div>

@endsection
