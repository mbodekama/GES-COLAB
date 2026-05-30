@extends('layouts.app')
@section('page-title', 'Gestion des employés')

@section('header-actions')
    @can('créer employés')
    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus-fill me-1"></i> Nouvel employé
    </a>
    @endcan
@endsection

@section('content')

<div class="filter-card">
    <form method="GET" action="{{ route('employees.index') }}" class="row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label>Département</label>
            <x-select
                name="department"
                :options="$departments->mapWithKeys(fn($d) => [$d => $d])->all()"
                :value="request('department')"
                placeholder="Tous"
                class="form-select-sm"
            />
        </div>
        <div class="col-6 col-md-2">
            <label>Statut</label>
            <x-select
                name="status"
                :options="['active' => 'Actif', 'on_leave' => 'En congé', 'suspended' => 'Suspendu', 'terminated' => 'Parti']"
                :value="request('status')"
                placeholder="Tous"
                class="form-select-sm"
            />
        </div>
        <div class="col-6 col-md-2">
            <label>Contrat</label>
            <x-select
                name="contract_type"
                :options="['cdi' => 'CDI', 'cdd' => 'CDD', 'internship' => 'Stage']"
                :value="request('contract_type')"
                placeholder="Tous"
                class="form-select-sm"
            />
        </div>
        <div class="col-6 col-md-4">
            <label>Recherche</label>
            <div class="search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Nom, prénom, matricule, poste...">
            </div>
        </div>
        <div class="col-12 col-md-auto ms-auto d-flex justify-content-end gap-2">
            <button class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i> Lancer la recherche
            </button>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span>Employés <span class="text-muted fw-normal">({{ $employees->total() }})</span></span>
        <a href="{{ route('employees.export', request()->query()) }}"
           class="btn btn-outline-success btn-sm"
           title="Télécharger la liste filtrée en Excel">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
        </a>
    </div>

    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <x-sort-th column="matricule" label="Matricule" />
                <x-sort-th column="last_name" label="Nom & Prénom" />
                <x-sort-th column="position" label="Poste" />
                <x-sort-th column="department" label="Département" />
                <th>Contrat</th>
                <x-sort-th column="hire_date" label="Ancienneté" />
                <x-sort-th column="status" label="Statut" />
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($employees as $employee)
        <tr>
            <td class="text-muted small fw-medium">{{ $employee->matricule }}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-initials" style="background:#E6F1FB;color:#185FA5">
                        {{ $employee->initials }}
                    </div>
                    <div>
                        <div class="fw-medium">{{ $employee->full_name }}</div>
                        <div class="small text-muted">{{ $employee->email }}</div>
                    </div>
                </div>
            </td>
            <td>{{ $employee->position }}</td>
            <td>
                <span class="badge bg-light text-dark border">{{ $employee->department }}</span>
            </td>
            <td>
                @if($employee->activeContract)
                    <span class="badge {{ $employee->activeContract->type === 'cdi' ? 'bg-primary' : 'bg-secondary' }} badge-status">
                        {{ strtoupper($employee->activeContract->type) }}
                    </span>
                @else
                    <span class="text-muted small">—</span>
                @endif
            </td>
            <td class="small">{{ $employee->seniority_label }}</td>
            <td>{!! $employee->status_badge !!}</td>
            <td class="text-center">
                <div class="btn-group btn-group-md d-flex justify-content-start gap-2">
                    <div>
                        <a href="{{ route('employees.show', $employee) }}"
                           class="btn btn-outline-secondary" title="Voir fiche">
                            <i class="bi bi-eye"></i> &nbsp; Voir
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('employees.print.design', $employee) }}"
                           class="btn btn-primary" title="Fiche PDF" target="_blank">
                            <i class="bi bi-file-earmark-person"></i> &nbsp; PDF
                        </a>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                Aucun employé trouvé
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-muted">
            Affichage {{ $employees->firstItem() ?? 0 }}–{{ $employees->lastItem() ?? 0 }}
            sur {{ $employees->total() }}
        </small>
        {{ $employees->links() }}
    </div>
</div>

@endsection
