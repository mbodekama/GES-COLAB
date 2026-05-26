@extends('layouts.app')
@section('page-title', $employee->full_name)

@section('header-actions')
    @can('modifier employés')
    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-pencil me-1"></i> Modifier
    </a>
    @endcan
    <a href="{{ route('employees.print', $employee) }}" class="btn btn-outline-dark btn-sm" target="_blank">
        <i class="bi bi-printer me-1"></i> PDF
    </a>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row g-3">

    {{-- CARTE PROFIL --}}
    <div class="col-md-4">
        <div class="card text-center p-4 mb-3">
            <div class="avatar-initials mx-auto mb-3"
                 style="width:72px;height:72px;font-size:26px;background:#E6F1FB;color:#185FA5">
                {{ $employee->initials }}
            </div>
            <h5 class="fw-semibold mb-0">{{ $employee->full_name }}</h5>
            <p class="text-muted mb-2">{{ $employee->position }}</p>
            {!! $employee->status_badge !!}
            <hr>
            <div class="text-start">
                @foreach([
                    ['bi-hash',            'Matricule',       $employee->matricule],
                    ['bi-building',        'Département',     $employee->department],
                    ['bi-calendar3',       'Date d\'embauche',$employee->hire_date->format('d M Y')],
                    ['bi-clock-history',   'Ancienneté',      $employee->seniority_label],
                    ['bi-calendar-check',  'Solde congés',    $employee->leave_balance.' jours'],
                ] as [$icon, $label, $value])
                <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:13px">
                    <span class="text-muted"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</span>
                    <strong>{{ $value }}</strong>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ACTIONS RAPIDES --}}
        <div class="card p-3">
            <div class="d-grid gap-2">
                <a href="{{ route('leaves.create') }}?employee={{ $employee->id }}"
                   class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-calendar-plus me-1"></i> Demande de congé
                </a>
                @can('voir fiches de paie')
                <a href="{{ route('payroll.index') }}?employee={{ $employee->id }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-receipt me-1"></i> Voir fiches de paie
                </a>
                @endcan
                <a href="{{ route('contracts.index') }}?employee={{ $employee->id }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-text me-1"></i> Voir contrats
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8">

        {{-- INFOS PERSONNELLES --}}
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="bi bi-person me-2"></i>Informations personnelles</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach([
                        ['Email',              $employee->email],
                        ['Téléphone',          $employee->phone ?? '—'],
                        ['Date de naissance',  $employee->birth_date?->format('d M Y') ?? '—'],
                        ['Lieu de naissance',  $employee->birth_place ?? '—'],
                        ['Nationalité',        $employee->nationality ?? '—'],
                        ['Situation familiale',$employee->marital_status_label],
                        ['Enfants',            $employee->children_count.' enfant(s)'],
                        ['N° CNPS',            $employee->cnps_number ?? '—'],
                    ] as [$label, $value])
                    <div class="col-md-6">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">{{ $label }}</div>
                        <div class="fw-medium" style="font-size:13.5px">{{ $value }}</div>
                    </div>
                    @endforeach
                    @if($employee->address)
                    <div class="col-12">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Adresse</div>
                        <div style="font-size:13.5px">{{ $employee->address }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- CONTRAT ACTIF --}}
        @if($employee->activeContract)
        <div class="card mb-3">
            <div class="card-header">
                <span><i class="bi bi-file-earmark-text me-2"></i>Contrat actif</span>
                <a href="{{ route('contracts.show', $employee->activeContract) }}"
                   class="btn btn-sm btn-outline-primary py-0 px-2">Détails</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase">N° Contrat</div>
                        <div class="fw-medium">{{ $employee->activeContract->contract_number }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase">Type</div>
                        <span class="badge bg-primary badge-status">{{ strtoupper($employee->activeContract->type) }}</span>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase">Salaire brut</div>
                        <div class="fw-semibold text-success">
                            {{ number_format($employee->activeContract->base_salary, 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase">Début</div>
                        <div>{{ $employee->activeContract->start_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase">Fin</div>
                        <div>{{ $employee->activeContract->end_date?->format('d M Y') ?? 'Indéterminé' }}</div>
                    </div>
                    @if($employee->activeContract->salaryGrid)
                    <div class="col-md-4">
                        <div class="text-muted" style="font-size:11px;text-transform:uppercase">Grille</div>
                        <div>{{ $employee->activeContract->salaryGrid->name }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- HISTORIQUE CONGÉS --}}
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-calendar-event me-2"></i>Historique des congés</span>
                <a href="{{ route('leaves.index') }}?employee={{ $employee->id }}"
                   class="btn btn-sm btn-outline-secondary py-0 px-2">Tout voir</a>
            </div>
            <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Du</th>
                        <th>Au</th>
                        <th>Jours</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($employee->leaves()->latest()->take(6)->get() as $leave)
                <tr>
                    <td class="small">{{ $leave->type_label }}</td>
                    <td class="small">{{ $leave->start_date->format('d M Y') }}</td>
                    <td class="small">{{ $leave->end_date->format('d M Y') }}</td>
                    <td><strong>{{ $leave->duration_days }}j</strong></td>
                    <td>{!! $leave->status_badge !!}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-muted text-center small py-3">
                        Aucun congé enregistré
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
