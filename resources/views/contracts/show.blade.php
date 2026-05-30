@extends('layouts.app')
@section('page-title', 'Contrat — '.$contract->contract_number)

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
    <div class="row g-3">

        {{-- CARTE STATUT --}}
        <div class="col-md-4">
            <div class="card text-center p-4 mb-3">
                <div class="avatar-initials mx-auto mb-3"
                     style="width:64px;height:64px;font-size:22px;background:#E6F1FB;color:#185FA5">
                    {{ $contract->employee->initials }}
                </div>
                <h5 class="fw-semibold mb-0">{{ $contract->employee->full_name }}</h5>
                <p class="text-muted mb-2">{{ $contract->employee->position }}</p>
                <span class="badge {{ $contract->type === 'cdi' ? 'bg-primary' : 'bg-secondary' }} badge-status">
                {{ strtoupper($contract->type) }}
            </span>
                <hr>
                <div class="text-start">
                    @foreach([
                        ['bi-hash',          'N° Contrat',    $contract->contract_number],
                        ['bi-calendar3',     'Date de début', $contract->start_date->format('d M Y')],
                        ['bi-calendar-x',    'Date de fin',   $contract->end_date?->format('d M Y') ?? 'Indéterminé'],
                        ['bi-cash',          'Salaire brut',  number_format($contract->base_salary, 0, ',', ' ').' FCFA'],
                    ] as [$icon, $label, $value])
                        <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:13px">
                            <span class="text-muted"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</span>
                            <strong>{{ $value }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- STATUT DU CONTRAT --}}
            <div class="card p-3 mb-3">
                <div class="fw-semibold mb-2 small text-muted text-uppercase" style="letter-spacing:.05em">Statut</div>
                @php
                    $statusMap = [
                        'active'     => ['success', 'En cours'],
                        'expired'    => ['danger',  'Expiré'],
                        'terminated' => ['secondary','Résilié'],
                        'renewed'    => ['info',    'Renouvelé'],
                    ];
                    [$color, $label] = $statusMap[$contract->status] ?? ['secondary', $contract->status];
                @endphp
                <span class="badge bg-{{ $color }} badge-status">{{ $label }}</span>

                @if($contract->end_date && $contract->status === 'active')
                    <div class="mt-2 small text-muted">
                        @if($contract->end_date->isPast())
                            <span class="text-danger">
                            <i class="bi bi-exclamation-triangle me-1"></i>Expiré depuis {{ $contract->end_date->diffForHumans() }}
                        </span>
                        @elseif($contract->end_date->diffInDays() <= 30)
                            <span class="text-warning">
                            <i class="bi bi-clock me-1"></i>Expire {{ $contract->end_date->diffForHumans() }}
                        </span>
                        @else
                            <span class="text-success">
                            <i class="bi bi-check-circle me-1"></i>Expire {{ $contract->end_date->diffForHumans() }}
                        </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ACTIONS --}}
            @if($contract->type !== 'cdi' && $contract->status === 'active')
                <div class="card p-3">
                    <button class="btn btn-outline-warning btn-sm w-100"
                            data-bs-toggle="modal" data-bs-target="#renewModal">
                        <i class="bi bi-arrow-clockwise me-1"></i>Renouveler le contrat
                    </button>
                </div>
            @endif
        </div>

        {{-- DÉTAILS --}}
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <span><i class="bi bi-file-earmark-text me-2"></i>Détails du contrat</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">N° Contrat</div>
                            <div class="fw-medium">{{ $contract->contract_number }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Type</div>
                            <span class="badge {{ $contract->type === 'cdi' ? 'bg-primary' : 'bg-secondary' }} badge-status">
                            {{ strtoupper($contract->type) }}
                        </span>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Poste</div>
                            <div class="fw-medium">{{ $contract->position }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Département</div>
                            <div class="fw-medium">{{ $contract->department }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Date de début</div>
                            <div class="fw-medium">{{ $contract->start_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Date de fin</div>
                            <div class="fw-medium">
                                {{ $contract->end_date?->format('d M Y') ?? 'Indéterminé' }}
                            </div>
                        </div>
                        @if($contract->trial_end_date)
                            <div class="col-md-4">
                                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Fin période d'essai</div>
                                <div class="fw-medium">{{ $contract->trial_end_date->format('d M Y') }}</div>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Salaire de base</div>
                            <div class="fw-semibold text-success fs-6">
                                {{ number_format($contract->base_salary, 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                        @if($contract->salaryGrid)
                            <div class="col-md-4">
                                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Grille salariale</div>
                                <div class="fw-medium">{{ $contract->salaryGrid->name }}</div>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Signé le</div>
                            <div class="fw-medium">{{ $contract->signed_at?->format('d M Y') ?? '—' }}</div>
                        </div>
                        @if($contract->notes)
                            <div class="col-12">
                                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Notes</div>
                                <div class="p-3 bg-light rounded" style="font-size:13.5px">{{ $contract->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- FICHE EMPLOYÉ --}}
            <div class="card">
                <div class="card-header">
                    <span><i class="bi bi-person me-2"></i>Employé concerné</span>
                    <a href="{{ route('employees.show', $contract->employee) }}"
                       class="btn btn-sm btn-outline-primary py-0 px-2">Voir fiche</a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Matricule</div>
                            <div class="fw-medium">{{ $contract->employee->matricule }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Date d'embauche</div>
                            <div>{{ $contract->employee->hire_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Ancienneté</div>
                            <div>{{ $contract->employee->seniority_label }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Email</div>
                            <div>{{ $contract->employee->email }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Téléphone</div>
                            <div>{{ $contract->employee->phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase">Statut</div>
                            {!! $contract->employee->status_badge !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL RENOUVELLEMENT --}}
    @if($contract->type !== 'cdi' && $contract->status === 'active')
        <div class="modal fade" id="renewModal" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <form method="POST" action="{{ route('contracts.renew', $contract) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Renouveler le contrat</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
