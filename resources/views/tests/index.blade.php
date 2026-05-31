@extends('layouts.app')

@section('title', 'Scénarios de test')

@section('breadcrumb')
<x-breadcrumb :items="[['label' => 'Scénarios de test']]" />
@endsection

@section('content')
<div class="row g-4">

    {{-- ── Statistiques ───────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-wrap align-items-center gap-3">

                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-circle bg-primary-subtle text-primary">
                            <span id="stat-total">{{ $total }}</span>
                        </div>
                        <small class="text-muted fw-medium">Total</small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-circle bg-success-subtle text-success">
                            <span id="stat-pass">0</span>
                        </div>
                        <small class="text-muted fw-medium">Réussis</small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-circle bg-danger-subtle text-danger">
                            <span id="stat-fail">0</span>
                        </div>
                        <small class="text-muted fw-medium">Échoués</small>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-circle bg-warning-subtle text-warning">
                            <span id="stat-skip">{{ $total }}</span>
                        </div>
                        <small class="text-muted fw-medium">Non testés</small>
                    </div>

                    <div class="flex-grow-1 mx-2" style="min-width:120px">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Progression</small>
                            <small class="fw-semibold text-primary" id="stat-pct">0%</small>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar bg-success" id="bar-pass" style="width:0" role="progressbar"></div>
                            <div class="progress-bar bg-danger"  id="bar-fail" style="width:0" role="progressbar"></div>
                        </div>
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" id="btn-reset"
                                onclick="confirmReset()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                        </button>
                        <form method="POST" action="{{ route('tests.rapport') }}" id="form-rapport">
                            @csrf
                            <input type="hidden" name="results" id="input-results">
                            <button type="button" class="btn btn-primary btn-sm" onclick="submitRapport()">
                                <i class="bi bi-file-earmark-richtext me-1"></i> Générer le rapport PDF
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Modules ────────────────────────────────────────────────── --}}
    @foreach($modules as $moduleName => $scenarios)
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-2 px-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="bi bi-folder2-open me-2 text-primary"></i>{{ $moduleName }}
                    </h6>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill module-count"
                          data-module="{{ $moduleName }}">
                        0 / {{ count($scenarios) }}
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($scenarios as $s)
                    <a href="{{ route('tests.show', $s['id']) }}"
                       class="list-group-item list-group-item-action test-item px-4 py-3"
                       data-id="{{ $s['id'] }}"
                       data-module="{{ $s['module'] }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="test-badge" id="badge-{{ $s['id'] }}">
                                <i class="bi bi-circle text-secondary fs-5"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <code class="text-muted small">{{ $s['id'] }}</code>
                                    <span class="fw-medium">{{ $s['name'] }}</span>
                                </div>
                                <small class="text-muted">
                                    {{ count($s['steps']) }} étape{{ count($s['steps']) > 1 ? 's' : '' }}
                                </small>
                            </div>
                            <span class="result-label" id="label-{{ $s['id'] }}"></span>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection

@push('styles')
<style>
.stat-circle {
    width: 42px; height: 42px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 700;
}
.test-item { transition: background .12s; }
.test-item:hover { background: #f8fafc; }
.result-label {
    font-size: 11px; font-weight: 600;
    padding: 2px 8px; border-radius: 20px;
    white-space: nowrap;
}
.result-pass  { background:#f0fdf4; color:#15803d; }
.result-fail  { background:#fef2f2; color:#dc2626; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const KEY   = 'gescolab_test_results_v1';
    const total = {{ $total }};
    const allIds = @json($allIds);

    function getResults() {
        try { return JSON.parse(localStorage.getItem(KEY) || '{}'); }
        catch { return {}; }
    }

    function renderAll() {
        const results = getResults();
        let pass = 0, fail = 0;

        allIds.forEach(function (id) {
            var r     = results[id] || 'none';
            var badge = document.getElementById('badge-' + id);
            var label = document.getElementById('label-' + id);
            if (!badge) return;

            if (r === 'pass') {
                badge.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                label.textContent = 'Réussi';
                label.className = 'result-label result-pass';
                pass++;
            } else if (r === 'fail') {
                badge.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
                label.textContent = 'Échoué';
                label.className = 'result-label result-fail';
                fail++;
            } else {
                badge.innerHTML = '<i class="bi bi-circle text-secondary fs-5"></i>';
                label.textContent = '';
                label.className = 'result-label';
            }
        });

        var skip = total - pass - fail;
        document.getElementById('stat-pass').textContent = pass;
        document.getElementById('stat-fail').textContent = fail;
        document.getElementById('stat-skip').textContent = skip;

        var pct = total > 0 ? Math.round(pass / total * 100) : 0;
        document.getElementById('stat-pct').textContent = pct + '%';
        document.getElementById('bar-pass').style.width = (pass / total * 100) + '%';
        document.getElementById('bar-fail').style.width = (fail / total * 100) + '%';

        // Module counters
        var moduleCounts = {};
        allIds.forEach(function (id) {
            var el = document.querySelector('[data-id="' + id + '"]');
            if (!el) return;
            var mod = el.dataset.module;
            if (!moduleCounts[mod]) moduleCounts[mod] = { done: 0, total: 0 };
            moduleCounts[mod].total++;
            var r = results[id] || 'none';
            if (r !== 'none') moduleCounts[mod].done++;
        });
        document.querySelectorAll('.module-count').forEach(function (el) {
            var mod = el.dataset.module;
            if (moduleCounts[mod]) {
                el.textContent = moduleCounts[mod].done + ' / ' + moduleCounts[mod].total;
            }
        });
    }

    window.confirmReset = function () {
        if (confirm('Réinitialiser tous les résultats de test ? Cette action est irréversible.')) {
            localStorage.removeItem(KEY);
            renderAll();
            showToast('Résultats réinitialisés.', 'info');
        }
    };

    window.submitRapport = function () {
        var results = getResults();
        document.getElementById('input-results').value = JSON.stringify(results);
        document.getElementById('form-rapport').submit();
    };

    renderAll();
    window.addEventListener('storage', renderAll);
})();
</script>
@endpush