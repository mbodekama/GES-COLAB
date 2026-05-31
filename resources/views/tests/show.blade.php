@extends('layouts.test-runner')

@section('title', $scenario['id'] . ' — ' . $scenario['name'])

@section('content')

{{-- ── Fil d'Ariane ────────────────────────────────────────────── --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:13px">
        <li class="breadcrumb-item">
            <a href="{{ route('tests.index') }}" class="text-decoration-none text-muted">
                Scénarios de test
            </a>
        </li>
        <li class="breadcrumb-item text-muted">{{ $scenario['module'] }}</li>
        <li class="breadcrumb-item active fw-semibold">{{ $scenario['id'] }}</li>
    </ol>
</nav>
<div class="row g-4 justify-content-center">
    <div class="col-12 col-xl-10">

        {{-- ── En-tête du scénario ─────────────────────────────────── --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body px-4 py-3">
                <div class="d-flex flex-wrap align-items-start gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <code class="text-muted">{{ $scenario['id'] }}</code>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $scenario['module'] }}</span>
                        </div>
                        <h5 class="mb-0 fw-semibold">{{ $scenario['name'] }}</h5>
                    </div>
                    <div id="current-result-badge"></div>
                </div>
            </div>
        </div>

        {{-- ── Préconditions ───────────────────────────────────────── --}}
        <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #185FA5 !important;">
            <div class="card-body px-4 py-3">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle-fill text-primary mt-1 flex-shrink-0"></i>
                    <div>
                        <div class="fw-semibold small text-primary mb-1">PRÉCONDITIONS</div>
                        <p class="mb-0 text-dark">{{ $scenario['pre'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Étapes ──────────────────────────────────────────────── --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom px-4 py-2">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-list-check me-2 text-primary"></i>
                    Étapes — {{ count($scenario['steps']) }} étape{{ count($scenario['steps']) > 1 ? 's' : '' }}
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:44px" class="text-center ps-4">#</th>
                                <th style="width:50%">Action à effectuer</th>
                                <th>Résultat attendu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scenario['steps'] as $i => $step)
                            <tr class="step-row" id="step-{{ $i }}">
                                <td class="text-center ps-4">
                                    <span class="badge rounded-pill bg-primary-subtle text-primary fw-semibold">
                                        {{ $i + 1 }}
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span class="step-action">{{ $step['action'] }}</span>
                                </td>
                                <td class="py-3">
                                    <span class="text-success-emphasis">
                                        <i class="bi bi-check2 me-1 text-success"></i>{{ $step['expected'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Navigation scénarios ────────────────────────────────── --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            @if($prevId)
            <a href="{{ route('tests.show', $prevId) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-left me-1"></i> {{ $prevId }}
            </a>
            @else
            <span></span>
            @endif

            <a href="{{ route('tests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-grid-3x3-gap me-1"></i> Tous les scénarios
            </a>

            @if($nextId)
            <a href="{{ route('tests.show', $nextId) }}" class="btn btn-outline-secondary btn-sm">
                {{ $nextId }} <i class="bi bi-chevron-right ms-1"></i>
            </a>
            @else
            <span></span>
            @endif
        </div>

    </div>
</div>

{{-- ── Barre d'actions sticky ──────────────────────────────────── --}}
<div class="form-sticky-actions" style="margin:0">
    <span class="form-sticky-hint text-muted" style="font-size:13px">
        Marquer le résultat après avoir exécuté tous les pas
    </span>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="setResult('none')" id="btn-reset-single">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
        </button>
        <button class="btn btn-danger btn-sm px-4" onclick="setResult('fail')" id="btn-fail">
            <i class="bi bi-x-circle me-2"></i> Échoué
        </button>
        <button class="btn btn-success btn-sm px-4" onclick="setResult('pass')" id="btn-pass">
            <i class="bi bi-check-circle me-2"></i> Réussi
        </button>
    </div>
</div>
@endsection

@push('styles')
<style>
.step-row { transition: background .1s; }
.step-row:hover { background: #f8fafc; }
.step-action { font-weight: 500; color: #1e293b; }
.result-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 20px;
    font-size: 13px; font-weight: 600;
}
.result-pill-pass { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.result-pill-fail { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.result-pill-none { background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const KEY = 'gescolab_test_results_v1';
    const ID  = '{{ $scenario['id'] }}';

    function getResults() {
        try { return JSON.parse(localStorage.getItem(KEY) || '{}'); }
        catch { return {}; }
    }

    function renderBadge() {
        var r   = getResults()[ID] || 'none';
        var el  = document.getElementById('current-result-badge');
        if (!el) return;
        if (r === 'pass') {
            el.innerHTML = '<span class="result-pill result-pill-pass"><i class="bi bi-check-circle-fill"></i> Réussi</span>';
        } else if (r === 'fail') {
            el.innerHTML = '<span class="result-pill result-pill-fail"><i class="bi bi-x-circle-fill"></i> Échoué</span>';
        } else {
            el.innerHTML = '<span class="result-pill result-pill-none"><i class="bi bi-circle"></i> Non testé</span>';
        }
    }

    window.setResult = function (value) {
        var results = getResults();
        if (value === 'none') {
            delete results[ID];
        } else {
            results[ID] = value;
        }
        localStorage.setItem(KEY, JSON.stringify(results));
        renderBadge();

        var msg = value === 'pass'
            ? 'Scénario marqué Réussi ✓'
            : value === 'fail'
                ? 'Scénario marqué Échoué ✗'
                : 'Résultat réinitialisé';
        var type = value === 'pass' ? 'success' : value === 'fail' ? 'error' : 'info';
        showToast(msg, type);

        @if($nextId)
        if (value !== 'none') {
            setTimeout(function () {
                window.location.href = '{{ route('tests.show', $nextId) }}';
            }, 800);
        }
        @endif
    };

    renderBadge();
})();
</script>
@endpush