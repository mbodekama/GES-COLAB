@extends('layouts.test-runner')

@section('title', 'Scénarios de test E2E')

@section('content')

{{-- ── Titre de page ───────────────────────────────────────────── --}}
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="bi bi-clipboard2-check text-primary me-2"></i>Scénarios de test — GES-COLAB
        </h4>
        <p class="text-muted mb-0 small">
            52 scénarios couvrant 13 modules · Résultats conservés localement dans votre navigateur
        </p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="confirmReset()">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser tout
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="openRapportModal()">
            <i class="bi bi-file-earmark-richtext me-1"></i> Générer le rapport PDF
        </button>
    </div>
</div>

{{-- ── Barre de stats ──────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3 px-4">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="d-flex align-items-center gap-2">
                <div class="stat-circle bg-primary-subtle text-primary"><span id="stat-total">{{ $total }}</span></div>
                <small class="text-muted fw-medium">Total</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="stat-circle bg-success-subtle text-success"><span id="stat-pass">0</span></div>
                <small class="text-muted fw-medium">Réussis</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="stat-circle bg-danger-subtle text-danger"><span id="stat-fail">0</span></div>
                <small class="text-muted fw-medium">Échoués</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="stat-circle bg-warning-subtle" style="color:#92400e"><span id="stat-skip">{{ $total }}</span></div>
                <small class="text-muted fw-medium">Non testés</small>
            </div>
            <div class="flex-grow-1" style="min-width:140px">
                <div class="d-flex justify-content-between mb-1">
                    <small class="text-muted">Progression</small>
                    <small class="fw-semibold text-primary" id="stat-pct">0%</small>
                </div>
                <div class="progress" style="height:8px;border-radius:4px">
                    <div class="progress-bar bg-success" id="bar-pass" style="width:0" role="progressbar"></div>
                    <div class="progress-bar bg-danger"  id="bar-fail" style="width:0" role="progressbar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Compte de référence ─────────────────────────────────────── --}}
<div class="alert alert-info border-0 py-2 px-3 mb-4 small">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Comptes de test :</strong>
    admin@gescolab.ci &nbsp;·&nbsp; rh@gescolab.ci &nbsp;·&nbsp;
    comptable@gescolab.ci &nbsp;·&nbsp; employe@gescolab.ci &nbsp;·&nbsp;
    <em>Mot de passe : <code>password</code></em>
</div>

{{-- ── Modules ─────────────────────────────────────────────────── --}}
@foreach($modules as $moduleName => $scenarios)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom py-2 px-4">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">
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
                    <span id="badge-{{ $s['id'] }}">
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
@endforeach

{{-- ── Modal rapport ────────────────────────────────────────────── --}}
<div class="modal fade" id="modalRapport" tabindex="-1" aria-labelledby="modalRapportLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('tests.rapport') }}" id="form-rapport">
            @csrf
            <input type="hidden" name="results" id="input-results">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold" id="modalRapportLabel">
                        <i class="bi bi-file-earmark-richtext text-primary me-2"></i>Générer le rapport PDF
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Le rapport reprend tous les résultats enregistrés dans votre navigateur
                        et les présente par module au format PDF.
                    </p>
                    <div class="mb-3">
                        <label for="tester_name" class="form-label small fw-medium">
                            Nom du testeur <span class="text-muted">(optionnel)</span>
                        </label>
                        <input type="text" name="tester_name" id="tester_name"
                               class="form-control"
                               placeholder="Ex. : Jean Dupont — Équipe QA"
                               value="{{ auth()->check() ? auth()->user()->name : '' }}">
                    </div>
                    <div id="rapport-summary" class="rounded p-3 bg-light small"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-download me-1"></i> Télécharger le PDF
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>
.stat-circle {
    width:42px;height:42px;border-radius:50%;
    display:flex;align-items:center;justify-content:center;
    font-size:15px;font-weight:700;
}
.test-item { transition:background .12s; }
.test-item:hover { background:#f8fafc; }
.result-label {
    font-size:11px;font-weight:600;
    padding:2px 8px;border-radius:20px;white-space:nowrap;
}
.result-pass { background:#f0fdf4;color:#15803d; }
.result-fail { background:#fef2f2;color:#dc2626; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var KEY   = 'gescolab_test_results_v1';
    var total = {{ $total }};
    var allIds = @json($allIds);

    function getResults() {
        try { return JSON.parse(localStorage.getItem(KEY) || '{}'); } catch { return {}; }
    }

    function renderAll() {
        var results = getResults();
        var pass = 0, fail = 0;

        allIds.forEach(function (id) {
            var r     = results[id] || 'none';
            var badge = document.getElementById('badge-' + id);
            var label = document.getElementById('label-' + id);
            if (!badge) return;
            if (r === 'pass') {
                badge.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                label.textContent = 'Réussi'; label.className = 'result-label result-pass'; pass++;
            } else if (r === 'fail') {
                badge.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
                label.textContent = 'Échoué'; label.className = 'result-label result-fail'; fail++;
            } else {
                badge.innerHTML = '<i class="bi bi-circle text-secondary fs-5"></i>';
                label.textContent = ''; label.className = 'result-label';
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

        // Compteurs par module
        var mods = {};
        allIds.forEach(function (id) {
            var el = document.querySelector('[data-id="' + id + '"]');
            if (!el) return;
            var mod = el.dataset.module;
            if (!mods[mod]) mods[mod] = { done: 0, total: 0 };
            mods[mod].total++;
            if ((results[id] || 'none') !== 'none') mods[mod].done++;
        });
        document.querySelectorAll('.module-count').forEach(function (el) {
            var m = mods[el.dataset.module];
            if (m) el.textContent = m.done + ' / ' + m.total;
        });
    }

    window.confirmReset = function () {
        if (confirm('Réinitialiser tous les résultats ? Cette action est irréversible.')) {
            localStorage.removeItem(KEY);
            renderAll();
            showToast('Résultats réinitialisés.', 'info');
        }
    };

    window.openRapportModal = function () {
        var results = getResults();
        var pass  = Object.values(results).filter(function(v){ return v==='pass'; }).length;
        var fail  = Object.values(results).filter(function(v){ return v==='fail'; }).length;
        var skip  = total - pass - fail;
        document.getElementById('rapport-summary').innerHTML =
            '<div class="d-flex gap-3">' +
            '<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>' + pass + ' réussis</span>' +
            '<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>' + fail + ' échoués</span>' +
            '<span style="color:#92400e" class="fw-semibold"><i class="bi bi-circle me-1"></i>' + skip + ' non testés</span>' +
            '</div>';
        document.getElementById('input-results').value = JSON.stringify(results);
        var modal = new bootstrap.Modal(document.getElementById('modalRapport'));
        modal.show();
    };

    renderAll();
    window.addEventListener('storage', renderAll);
    window.addEventListener('test-result-changed', renderAll);
})();
</script>
@endpush