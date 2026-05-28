@extends('layouts.app')
@section('page-title', 'Tableau de bord')

@section('content')

    {{-- KPI CARDS --}}
    <div class="row g-3 mb-4">
        @foreach([$kpi1, $kpi2, $kpi3, $kpi4] as $kpi)
            {{-- KPI 3 : masqué pour les rôles non admin/rh --}}
            @if(isset($kpi['show']) && !$kpi['show'])
                @continue
            @endif
            <div class="col-6 col-md-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3 p-3">
                        <div class="rounded-3 p-2" style="background:{{ $kpi['bg'] }};flex-shrink:0">
                            <i class="bi {{ $kpi['icon'] }} fs-4" style="color:{{ $kpi['color'] }}"></i>
                        </div>
                        <div>
                            <div class="text-muted" style="font-size:11px">{{ $kpi['label'] }}</div>
                            <div class="fs-3 fw-bold lh-1" id="kpi-{{ $loop->index }}">
                                {{ $kpi['value'] }}
                            </div>
                            <div class="small mt-1" style="color:{{ $kpi['color'] }}">
                                {{ $kpi['delta'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- COL GAUCHE --}}
        <div class="col-md-7">

            {{-- DEMANDES EN ATTENTE --}}
            <div class="card mb-3">
                <div class="card-header">
                    @if($isAdmin || auth()->user()->hasRole('rh'))
                        Demandes de congé en attente
                    @else
                        Mes demandes en attente
                    @endif
                    <span class="badge bg-warning text-dark">{{ $pendingLeaves }}</span>
                </div>
                <div class="card-body p-0">
                    @if($pendingLeavesList->count())
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                <tr>
                                    @if($isAdmin || auth()->user()->hasRole('rh'))
                                        <th>Employé</th>
                                    @endif
                                    <th>Type</th>
                                    <th>Période</th>
                                    <th>Durée</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($pendingLeavesList as $leave)
                                    <tr>
                                        @if($isAdmin || auth()->user()->hasRole('rh'))
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-initials"
                                                         style="background:#E6F1FB;color:#185FA5;width:30px;height:30px;font-size:11px">
                                                        {{ $leave->employee->initials }}
                                                    </div>
                                                    <span class="fw-medium">{{ $leave->employee->full_name }}</span>
                                                </div>
                                            </td>
                                        @endif
                                        <td>
                                <span class="badge bg-secondary badge-status">
                                    {{ $leave->type_label }}
                                </span>
                                        </td>
                                        <td class="small">
                                            {{ $leave->start_date->format('d M') }}
                                            → {{ $leave->end_date->format('d M Y') }}
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
                                            <a href="{{ route('leaves.show', $leave) }}"
                                               class="btn btn-sm btn-outline-secondary py-0 px-2">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Taux de présence (12 derniers mois)</span>
                    <button class="btn btn-sm btn-outline-secondary py-0"
                            onclick="refreshChart()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="card-body">
                    <canvas id="presenceChart" height="90"></canvas>
                </div>
            </div>
        </div>

        {{-- COL DROITE --}}
        <div class="col-md-5">

            {{-- ACTIVITÉ RÉCENTE --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mon activité récente</span>
                    <button class="btn btn-sm btn-outline-secondary py-0"
                            onclick="refreshActivity()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="list-group list-group-flush" id="activity-list">
                    @forelse($recentActivity as $activity)
                        <div class="list-group-item d-flex align-items-start gap-2 py-2 px-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1"
                                 style="width:26px;height:26px;background:{{ $activity['color'] }}1a">
                                <i class="bi {{ $activity['icon'] }}" style="color:{{ $activity['color'] }};font-size:13px"></i>
                            </div>
                            <div class="small flex-grow-1" style="line-height:1.4">
                                {{ $activity['text'] }}
                            </div>
                            <div class="text-muted text-nowrap" style="font-size:11px">
                                {{ $activity['time'] }}
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small text-center py-4">
                            <i class="bi bi-clock-history fs-3 d-block mb-2 opacity-25"></i>
                            Aucune activité récente
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- RÉPARTITION PAR RÔLE (admin uniquement) --}}
            @if($isAdmin && count($roleStats) > 0)
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
                                    <div class="progress-bar"
                                         style="width:{{ $stat['percent'] }}%;
                                    background:{{ $stat['color'] }};
                                    border-radius:3px">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- SOLDE CONGÉS (utilisateur standard) --}}
            @elseif($employee)
                <div class="card">
                    <div class="card-header">Mon solde de congés</div>
                    <div class="card-body text-center py-4">
                        <div class="fs-1 fw-bold" style="color:#185FA5">
                            {{ $employee->leave_balance }}
                        </div>
                        <div class="text-muted mb-3">jours disponibles</div>

                        {{-- Barre de progression --}}
                        @php
                            $total   = config('gescolab.annual_leave_days', 30);
                            $used    = $total - $employee->leave_balance;
                            $percent = round(($employee->leave_balance / max($total, 1)) * 100);
                        @endphp
                        <div class="progress mb-2" style="height:10px;border-radius:5px">
                            <div class="progress-bar"
                                 style="width:{{ $percent }}%;background:#185FA5;border-radius:5px">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>{{ $used }} jour(s) utilisé(s)</span>
                            <span>{{ $total }} jours total</span>
                        </div>

                        <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm mt-3 w-100">
                            <i class="bi bi-plus-circle me-1"></i>Nouvelle demande
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        // ── Graphique présence ────────────────────────────────────────
        const presenceChart = new Chart(document.getElementById('presenceChart'), {
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
                    y: {
                        min: 60, max: 100,
                        grid: { color: '#f0f0f0' },
                        ticks: { font: { size: 11 }, callback: v => v + '%' }
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });

        // ── Rafraîchir les KPIs toutes les 60 secondes ────────────────
        function refreshKpis() {
            fetch('/api/dashboard/stats')
                .then(r => r.json())
                .then(data => {
                    // Mettre à jour les valeurs dynamiquement
                    @if($isAdmin)
                    document.getElementById('kpi-0').textContent = data.total_employees;
                    @else
                    // pas de refresh pour les demandes (nécessite rechargement)
                    @endif
                    document.getElementById('kpi-1').textContent = data.pending_leaves;
                    document.getElementById('kpi-2').textContent = data.approved_days;
                    document.getElementById('kpi-3').textContent = data.my_leave_balance + ' j';
                })
                .catch(() => {});
        }

        // Rafraîchir toutes les 60 secondes
        setInterval(refreshKpis, 60000);

        // ── Rafraîchir le graphique ───────────────────────────────────
        function refreshChart() {
            location.reload();
        }

        // ── Rafraîchir l'activité ─────────────────────────────────────
        function refreshActivity() {
            location.reload();
        }
    </script>
@endpush
