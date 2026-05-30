@extends('layouts.app')
@section('page-title', 'Contrat — '.$contract->contract_number)

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Contrats', 'url' => route('contracts.index')],
    ['label' => $contract->contract_number],
]" />
@endsection

@section('header-actions')
    @can('modifier contrats')
        <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Modifier
        </a>
    @endcan
    <a href="{{ route('contracts.print.design', $contract) }}" class="btn btn-primary btn-sm" target="_blank">
        <i class="bi bi-file-earmark-richtext me-1"></i> Contrat PDF
    </a>
    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
@php
    $statusMap = [
        'active'     => ['success',   'En cours'],
        'expired'    => ['danger',    'Expiré'],
        'terminated' => ['secondary', 'Résilié'],
        'renewed'    => ['info',      'Renouvelé'],
    ];
    [$statusColor, $statusLabel] = $statusMap[$contract->status] ?? ['secondary', $contract->status];
@endphp
<div class="row g-3">

    {{-- DÉTAILS DU CONTRAT (pleine largeur) --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-file-earmark-text me-2"></i>Détails du contrat</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span>
                    @if($contract->end_date && $contract->status === 'active')
                        @if($contract->end_date->isPast())
                            <span class="small text-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i>Expiré {{ $contract->end_date->diffForHumans() }}
                            </span>
                        @elseif($contract->end_date->diffInDays() <= 30)
                            <span class="small text-warning">
                                <i class="bi bi-clock me-1"></i>Expire {{ $contract->end_date->diffForHumans() }}
                            </span>
                        @endif
                    @endif
                    @if($contract->type !== 'cdi' && $contract->status === 'active')
                        <button class="btn btn-outline-warning btn-sm py-0"
                                data-bs-toggle="modal" data-bs-target="#renewModal">
                            <i class="bi bi-arrow-clockwise me-1"></i>Renouveler
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">

                {{-- Identité employé --}}
                <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                    <x-avatar :initials="$contract->employee->initials" size="lg" />
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $contract->employee->full_name }}</div>
                        <div class="text-muted small">{{ $contract->employee->position }} · {{ $contract->employee->department }}</div>
                    </div>
                    <span class="badge {{ $contract->type === 'cdi' ? 'bg-primary' : 'bg-secondary' }} badge-status">
                        {{ strtoupper($contract->type) }}
                    </span>
                </div>

                {{-- Champs --}}
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">N° Contrat</div>
                        <div class="fw-medium">{{ $contract->contract_number }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Poste</div>
                        <div class="fw-medium">{{ $contract->position }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Département</div>
                        <div class="fw-medium">{{ $contract->department }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Salaire de base</div>
                        <div class="fw-semibold text-success">{{ number_format($contract->base_salary, 0, ',', ' ') }} FCFA</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Date de début</div>
                        <div class="fw-medium">{{ $contract->start_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Date de fin</div>
                        <div class="fw-medium">{{ $contract->end_date?->format('d M Y') ?? 'Indéterminé' }}</div>
                    </div>
                    @if($contract->trial_end_date)
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Fin période d'essai</div>
                        <div class="fw-medium">{{ $contract->trial_end_date->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($contract->salaryGrid)
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Grille salariale</div>
                        <div class="fw-medium">{{ $contract->salaryGrid->name }}</div>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Signé le</div>
                        <div class="fw-medium">{{ $contract->signed_at?->format('d M Y') ?? '—' }}</div>
                    </div>
                    @if($contract->date_renouvellement)
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                            <i class="bi bi-arrow-clockwise me-1 text-warning"></i>Date de renouvellement
                        </div>
                        <div class="fw-medium">{{ $contract->date_renouvellement->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($contract->date_resiliation)
                    <div class="col-md-3">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                            <i class="bi bi-calendar-x me-1 text-danger"></i>Date de résiliation
                        </div>
                        <div class="fw-medium text-danger">{{ $contract->date_resiliation->format('d M Y') }}</div>
                    </div>
                    @endif
                    @if($contract->notes)
                    <div class="col-12">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Notes</div>
                        <div class="p-3 bg-light rounded mt-1" style="font-size:13.5px">{{ $contract->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

    {{-- PLEINE LARGEUR --}}
    <div class="row g-3 mt-0">

        {{-- FICHE EMPLOYÉ --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-person me-2"></i>Employé concerné</span>
                    <a href="{{ route('employees.show', $contract->employee) }}"
                       class="btn btn-sm btn-outline-primary py-0 px-2">Voir fiche</a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Matricule</div>
                            <div class="fw-medium">{{ $contract->employee->matricule }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Date d'embauche</div>
                            <div>{{ $contract->employee->hire_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Ancienneté</div>
                            <div>{{ $contract->employee->seniority_label }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Email</div>
                            <div>{{ $contract->employee->email }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Téléphone</div>
                            <div>{{ $contract->employee->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-1">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Statut</div>
                            {!! $contract->employee->status_badge !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- AUTRES CONTRATS DE L'EMPLOYÉ --}}
        @if($previousContracts->count())
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-clock-history me-2"></i>Historique des contrats — {{ $contract->employee->full_name }}</span>
                    <span class="badge bg-secondary rounded-pill">{{ $previousContracts->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>N° Contrat</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th>Salaire brut</th>
                                <th>Résiliation / Renouvellement</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($previousContracts as $prev)
                            @php
                                $statusMap = [
                                    'active'     => ['success',   'En cours'],
                                    'expired'    => ['danger',    'Expiré'],
                                    'terminated' => ['secondary', 'Résilié'],
                                    'renewed'    => ['info',      'Renouvelé'],
                                ];
                                [$sc, $sl] = $statusMap[$prev->status] ?? ['secondary', $prev->status];
                            @endphp
                            <tr>
                                <td class="small fw-medium text-muted">{{ $prev->contract_number }}</td>
                                <td>
                                    <span class="badge {{ $prev->type === 'cdi' ? 'bg-primary' : 'bg-secondary' }} badge-status">
                                        {{ strtoupper($prev->type) }}
                                    </span>
                                </td>
                                <td><span class="badge bg-{{ $sc }} badge-status">{{ $sl }}</span></td>
                                <td class="small">{{ $prev->start_date->format('d M Y') }}</td>
                                <td class="small">{{ $prev->end_date?->format('d M Y') ?? '—' }}</td>
                                <td class="small fw-medium">
                                    {{ number_format($prev->base_salary, 0, ',', ' ') }}
                                    <span class="text-muted">FCFA</span>
                                </td>
                                <td class="small">
                                    @if($prev->date_resiliation)
                                        <span class="text-danger">
                                            <i class="bi bi-calendar-x me-1"></i>{{ $prev->date_resiliation->format('d M Y') }}
                                        </span>
                                    @elseif($prev->date_renouvellement)
                                        <span class="text-info">
                                            <i class="bi bi-arrow-clockwise me-1"></i>{{ $prev->date_renouvellement->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('contracts.show', $prev) }}"
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-eye"></i> &nbsp; Voir
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer py-2">
                    <small class="text-muted">{{ $previousContracts->count() }} contrat(s) précédent(s)</small>
                </div>
            </div>
        </div>
        @endif

        {{-- HISTORIQUE DES MODIFICATIONS --}}
        <div class="col-12">
            @include('partials.activity-log', ['activityLogs' => $activityLogs])
        </div>

    </div>

    {{-- MODAL RENOUVELLEMENT --}}
    @if($contract->type !== 'cdi' && $contract->status === 'active')
        <div class="modal fade" id="renewModal" tabindex="-1"
             aria-labelledby="renewModalTitle" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-sm">
                <form method="POST" action="{{ route('contracts.renew', $contract) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="renewModalTitle">Renouveler le contrat</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label small fw-medium">Nouvelle date de fin <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control"
                                   required min="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="bi bi-arrow-clockwise me-1"></i>Renouveler
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endsection
