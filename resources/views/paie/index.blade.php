@extends('layouts.app')
@section('page-title', 'Gestion de la paie')

@section('header-actions')
    @can('générer fiches de paie')
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generateModal">
        <i class="bi bi-file-earmark-plus me-1"></i> Générer fiches
    </button>
    @endcan
@endsection

@section('content')

{{-- KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Période</div>
            <div class="fs-5 fw-bold">{{ $currentPeriod }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Fiches générées</div>
            <div class="fs-3 fw-bold text-success">{{ $generatedCount }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Masse salariale brute</div>
            <div class="fw-bold">{{ number_format($totalGross, 0, ',', ' ') }}</div>
            <div class="text-muted small">FCFA</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Total net à payer</div>
            <div class="fw-bold text-primary">{{ number_format($totalNet, 0, ',', ' ') }}</div>
            <div class="text-muted small">FCFA</div>
        </div>
    </div>
</div>

{{-- FILTRE --}}
<div class="filter-card">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label>Période</label>
            <input type="month" name="period" value="{{ request('period', date('Y-m')) }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3">
            <label>Département</label>
            <x-select
                name="department"
                :options="$departments->mapWithKeys(fn($d) => [$d => $d])->all()"
                :value="request('department')"
                placeholder="Tous"
                class="form-select-sm"
            />
        </div>
        <div class="col-12 col-md-auto ms-auto d-flex justify-content-end gap-2 align-items-end">
            <button class="btn btn-primary btn-sm">
                <i class="bi bi-search me-1"></i> Lancer la recherche
            </button>
            <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
            </a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <span>Fiches de paie — {{ $currentPeriod }}</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success">{{ $generatedCount }} générées</span>
            <a href="{{ route('payroll.export', request()->query()) }}"
               class="btn btn-outline-success btn-sm"
               title="Télécharger les fiches de la période en Excel">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Employé</th>
                <th>Département</th>
                <x-sort-th column="gross_salary" label="Brut" />
                <x-sort-th column="cnps_employee" label="CNPS salarié" />
                <x-sort-th column="igr" label="IGR" />
                <x-sort-th column="net_salary" label="Net à payer" />
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payrolls as $payroll)
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-initials" style="width:30px;height:30px;font-size:11px;background:#E6F1FB;color:#185FA5">
                        {{ $payroll->employee->initials }}
                    </div>
                    <div>
                        <div class="fw-medium">{{ $payroll->employee->full_name }}</div>
                        <div class="small text-muted">{{ $payroll->employee->position }}</div>
                    </div>
                </div>
            </td>
            <td class="small">{{ $payroll->employee->department }}</td>
            <td>{{ number_format($payroll->gross_salary, 0, ',', ' ') }}</td>
            <td class="text-danger small">- {{ number_format($payroll->cnps_employee, 0, ',', ' ') }}</td>
            <td class="text-danger small">- {{ number_format($payroll->igr, 0, ',', ' ') }}</td>
            <td><strong class="text-success">{{ number_format($payroll->net_salary, 0, ',', ' ') }} FCFA</strong></td>
            <td class="text-center">
                <div class="btn-group btn-group-md d-flex justify-content-start gap-2">
                    <div>
                        <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-outline-primary" title="Voir">
                            <i class="bi bi-eye"></i> &nbsp; Voir
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('payroll.print.design', $payroll) }}" class="btn btn-primary" title="Bulletin PDF" target="_blank">
                            <i class="bi bi-file-earmark-richtext"></i> &nbsp; PDF
                        </a>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center text-muted py-5">
                <i class="bi bi-receipt fs-1 d-block mb-2 opacity-25"></i>
                Aucune fiche pour cette période
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-muted">
            {{ $payrolls->firstItem() ?? 0 }}–{{ $payrolls->lastItem() ?? 0 }} sur {{ $payrolls->total() }}
        </small>
        {{ $payrolls->links() }}
    </div>
</div>

{{-- MODAL GÉNÉRATION --}}
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('payroll.generate') }}">
        @csrf
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear me-2"></i>Générer les fiches de paie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Période <span class="text-danger">*</span></label>
                    <input type="month" name="period" value="{{ date('Y-m') }}" class="form-control" required>
                </div>
                <div class="mb-3">
                    <x-select
                        name="department"
                        label="Département"
                        :options="$departments->mapWithKeys(fn($d) => [$d => $d])->all()"
                        placeholder="Tous les départements"
                    />
                </div>
                <div class="alert alert-warning small py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Les fiches existantes pour cette période seront recalculées.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-play-fill me-1"></i>Lancer la génération
                </button>
            </div>
        </div>
        </form>
    </div>
</div>

@endsection
