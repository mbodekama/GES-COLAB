@extends('layouts.app')
@section('page-title', 'Tableau de bord')

@section('content')

{{-- KPI CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 p-2" style="background:#E6F1FB;flex-shrink:0">
                    <i class="bi bi-people-fill fs-4" style="color:#185FA5"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:11px">Total employés</div>
                    <div class="fs-3 fw-bold lh-1">{{ $totalEmployees }}</div>
                    <div class="small text-success mt-1">
                        <i class="bi bi-arrow-up"></i> {{ $newEmployeesThisMonth }} ce mois
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 p-2" style="background:#FAEEDA;flex-shrink:0">
                    <i class="bi bi-calendar-x fs-4" style="color:#BA7517"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:11px">Congés en attente</div>
                    <div class="fs-3 fw-bold lh-1">{{ $pendingLeaves }}</div>
                    <div class="small text-warning mt-1">À valider</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 p-2" style="background:#EAF3DE;flex-shrink:0">
                    <i class="bi bi-cash-stack fs-4" style="color:#3B6D11"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:11px">Masse salariale</div>
                    <div class="fs-3 fw-bold lh-1">{{ number_format($masseSalariale/1000000, 1) }}M</div>
                    <div class="small text-muted mt-1">FCFA / mois</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 p-2" style="background:#EEEDFE;flex-shrink:0">
                    <i class="bi bi-file-earmark-check fs-4" style="color:#534AB7"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:11px">Fiches de paie</div>
                    <div class="fs-3 fw-bold lh-1">{{ $payrollsThisMonth }}</div>
                    <div class="small text-success mt-1">{{ now()->format('M Y') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- COL GAUCHE --}}
    <div class="col-md-7">

        {{-- DEMANDES EN ATTENTE --}}
        <div class="card mb-3">
            <div class="card-header">
                Demandes de congé en attente
                <span class="badge bg-warning text-dark">{{ $pendingLeaves }}</span>
            </div>
            <div class="card-body p-0">
                @if($pendingLeavesList->count())
                    <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Durée</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($pendingLeavesList as $leave)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-initials" style="background:#E6F1FB;color:#185FA5;width:30px;height:30px;font-size:11px">
                                        {{ $leave->employee->initials }}
                                    </div>
                                    <span class="fw-medium">{{ $leave->employee->full_name }}</span>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary badge-status">{{ $leave->type_label }}</span></td>
                            <td class="small">
                                {{ $leave->start_date->format('d M') }} → {{ $leave->end_date->format('d M Y') }}
                            </td>
                            <td><strong>{{ $leave->duration_days }}j</strong></td>
                            <td class="text-center">
                                @can('valider congés')
                                <form method="POST" action="{{ route('leaves.approve', $leave) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success py-0 px-2" title="Approuver">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('leaves.reject', $leave) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2" title="Refuser">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                @endcan
                                <a href="{{ route('leaves.show', $leave) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-calendar-check fs-2 d-block mb-2 text-success"></i>
                        Aucune demande en attente
                    </div>
                @endif
            </div>
        </div>

        {{-- GRAPHIQUE PRÉSENCE --}}
        <div class="card">
            <div class="card-header">Présence mensuelle (12 derniers mois)</div>
            <div class="card-body">
                <canvas id="presenceChart" height="90"></canvas>
            </div>
        </div>
    </div>

    {{-- COL DROITE --}}
    <div class="col-md-5">

        {{-- ACTIVITÉ RÉCENTE --}}
        <div class="card mb-3">
            <div class="card-header">Activité récente</div>
            <div class="list-group list-group-flush">
                @forelse($recentActivity as $activity)
                <div class="list-group-item d-flex align-items-start gap-2 py-2 px-3">
                    <span style="width:8px;height:8px;border-radius:50%;background:{{ $activity['color'] }};flex-shrink:0;margin-top:5px;display:inline-block"></span>
                    <div class="small flex-grow-1" style="line-height:1.4">{{ $activity['text'] }}</div>
                    <div class="text-muted text-nowrap" style="font-size:11px">{{ $activity['time'] }}</div>
                </div>
                @empty
                <div class="list-group-item text-muted small text-center py-3">
                    Aucune activité récente
                </div>
                @endforelse
            </div>
        </div>

        {{-- RÉPARTITION PAR RÔLE --}}
        <div class="card">
            <div class="card-header">Répartition par rôle</div>
            <div class="card-body">
                @foreach($roleStats as $stat)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:13px">
                        <span>{{ $stat['name'] }}</span>
                        <strong>{{ $stat['count'] }}</strong>
                    </div>
                    <div class="progress" style="height:6px;border-radius:3px">
                        <div class="progress-bar" style="width:{{ $stat['percent'] }}%;background:{{ $stat['color'] }};border-radius:3px"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
new Chart(document.getElementById('presenceChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($presenceLabels) !!},
        datasets: [{
            label: 'Présence (%)',
            data: {!! json_encode($presenceData) !!},
            backgroundColor: '#185FA5',
            borderRadius: 5,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { min: 60, max: 100, grid: { color: '#f0f0f0' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});
</script>
@endpush
