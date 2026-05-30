@extends('layouts.app')
@section('page-title', 'Contrats de travail')

@section('header-actions')
    @can('créer contrats')
        <a href="{{ route('contracts.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Nouveau contrat
        </a>
    @endcan
@endsection

@section('content')

    @if(isset($expiringCount) && $expiringCount > 0)
        <div class="alert alert-warning alert-dismissible fade show py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>{{ $expiringCount }} contrat(s)</strong> arrive(nt) à expiration dans les 30 prochains jours.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="filter-card">
        <form method="GET" action="{{ route('contracts.index') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label>Type</label>
                <x-select
                    name="type"
                    :options="['cdi' => 'CDI', 'cdd' => 'CDD', 'internship' => 'Stage', 'consulting' => 'Consulting']"
                    :value="request('type')"
                    placeholder="Tous"
                    class="form-select-sm"
                />
            </div>
            <div class="col-6 col-md-2">
                <label>Statut</label>
                <x-select
                    name="status"
                    :options="['active' => 'En cours', 'expired' => 'Expiré', 'terminated' => 'Résilié', 'renewed' => 'Renouvelé']"
                    :value="request('status')"
                    placeholder="Tous"
                    class="form-select-sm"
                />
            </div>
            <div class="col-12 col-md-5">
                <label>Recherche</label>
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm"
                           placeholder="Nom employé, N° contrat...">
                </div>
            </div>
            <div class="col-12 col-md-auto ms-auto d-flex justify-content-end gap-2">
                <button class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i> Lancer la recherche
                </button>
                <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <span>Contrats <span class="text-muted fw-normal">({{ $contracts->total() }})</span></span>
            <div class="d-flex align-items-center gap-2">
                @if(isset($expiringCount) && $expiringCount > 0)
                    <span class="badge bg-warning text-dark">{{ $expiringCount }} expirent bientôt</span>
                @endif
                <a href="{{ route('contracts.export', request()->query()) }}"
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
                    <x-sort-th column="contract_number" label="N° Contrat" />
                    <th>Employé</th>
                    <x-sort-th column="position" label="Poste" />
                    <x-sort-th column="type" label="Type" />
                    <x-sort-th column="start_date" label="Début" />
                    <x-sort-th column="end_date" label="Fin" />
                    <x-sort-th column="base_salary" label="Salaire brut" />
                    <x-sort-th column="status" label="Statut" />
                    <th class="text-center">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($contracts as $contract)
                    <tr>
                        <td class="text-muted small fw-medium">{{ $contract->contract_number }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <x-avatar :initials="$contract->employee->initials" size="sm" />
                                <div>
                                    <div class="fw-medium">{{ $contract->employee->full_name }}</div>
                                    <div class="small text-muted">{{ $contract->employee->department }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $contract->position }}</td>
                        <td>
                <span class="badge {{ $contract->type === 'cdi' ? 'bg-primary' : 'bg-secondary' }} badge-status">
                    {{ strtoupper($contract->type) }}
                </span>
                        </td>
                        <td class="small">{{ $contract->start_date->format('d M Y') }}</td>
                        <td class="small">
                            @if($contract->end_date)
                                @if($contract->end_date->isPast())
                                    <span class="text-danger fw-medium">
                            {{ $contract->end_date->format('d M Y') }}
                        </span>
                                @elseif($contract->end_date->diffInDays() <= 30)
                                    <span class="text-warning fw-medium">
                            <i class="bi bi-clock me-1"></i>{{ $contract->end_date->format('d M Y') }}
                        </span>
                                @else
                                    {{ $contract->end_date->format('d M Y') }}
                                @endif
                            @else
                                <span class="text-muted">Indéterminé</span>
                            @endif
                        </td>
                        <td class="fw-medium">
                            {{ number_format($contract->base_salary, 0, ',', ' ') }}
                            <small class="text-muted">FCFA</small>
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'active'     => ['success',   'En cours'],
                                    'expired'    => ['danger',    'Expiré'],
                                    'terminated' => ['secondary', 'Résilié'],
                                    'renewed'    => ['info',      'Renouvelé'],
                                ];
                                [$color, $label] = $statusMap[$contract->status] ?? ['secondary', $contract->status];
                            @endphp
                            <span class="badge bg-{{ $color }} badge-status">{{ $label }}</span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-md d-flex justify-content-start gap-2">
                                <div>
                                    <a href="{{ route('contracts.show', $contract) }}"
                                       class="btn btn-outline-secondary" title="Voir">
                                        <i class="bi bi-eye"></i> &nbsp; Voir
                                    </a>
                                </div>
                                @if($contract->type !== 'cdi' && $contract->status === 'active')
                                <div>
                                    <button class="btn btn-outline-warning" title="Renouveler"
                                            data-bs-toggle="modal"
                                            data-bs-target="#renewModal"
                                            data-url="{{ route('contracts.renew', $contract) }}"
                                            onclick="setRenewUrl(this)">
                                        <i class="bi bi-arrow-clockwise"></i> &nbsp; Renouveler
                                    </button>
                                </div>
                                @endif
                                <div>
                                    <a href="{{ route('contracts.print.design', $contract) }}"
                                       class="btn btn-primary" title="Contrat PDF" target="_blank">
                                        <i class="bi bi-file-earmark-richtext"></i> &nbsp; PDF
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-file-earmark-text fs-1 d-block mb-2 opacity-25"></i>
                            Aucun contrat trouvé
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center py-2">
            <small class="text-muted">
                {{ $contracts->firstItem() ?? 0 }}–{{ $contracts->lastItem() ?? 0 }}
                sur {{ $contracts->total() }}
            </small>
            {{ $contracts->links() }}
        </div>
    </div>

    {{-- MODAL RENOUVELLEMENT --}}
    <div class="modal fade" id="renewModal" tabindex="-1"
         aria-labelledby="renewModalTitle" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-sm">
            <form method="POST" id="renew-form" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="renewModalTitle">Renouveler le contrat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small fw-medium">
                            Nouvelle date de fin <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="end_date" class="form-control"
                               required min="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>Renouveler
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function setRenewUrl(btn) {
            document.getElementById('renew-form').action = btn.dataset.url;
        }
    </script>
@endpush
