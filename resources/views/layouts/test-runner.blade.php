<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tests') — GES-COLAB</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/gescolab.css') }}" rel="stylesheet">

    @stack('styles')
    <style>
        body { background: #f1f5f9; min-height: 100vh; }

        .tr-navbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0 24px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .tr-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .tr-brand-logo {
            width: 32px; height: 32px;
            background: #185FA5;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: -.5px;
        }
        .tr-brand-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
        }
        .tr-brand-tag {
            font-size: 11px;
            background: #eff6ff;
            color: #185FA5;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 1px 8px;
            font-weight: 600;
        }
        .tr-main {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 20px 80px;
        }
    </style>
</head>
<body>

{{-- ── Barre de navigation publique ─────────────────────────────── --}}
<nav class="tr-navbar">
    <a href="{{ route('tests.index') }}" class="tr-brand">
        <div class="tr-brand-logo">GC</div>
        <span class="tr-brand-name">GES-COLAB</span>
        <span class="tr-brand-tag">
            <i class="bi bi-clipboard2-check me-1"></i>Test Runner
        </span>
    </a>

    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small" id="nav-progress" style="display:none">
            <span id="nav-pass" class="text-success fw-semibold">0</span> réussis ·
            <span id="nav-fail" class="text-danger fw-semibold">0</span> échoués
        </span>
        @auth
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Retour à l'app
        </a>
        @endauth
    </div>
</nav>

{{-- ── Toast container ───────────────────────────────────────────── --}}
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>

{{-- ── Contenu ────────────────────────────────────────────────────── --}}
<div class="tr-main">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Système de toasts (repris de app.blade.php) ─────────────────
(function () {
    var ICONS = {
        success: 'bi-check-circle-fill',
        error:   'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info:    'bi-info-circle-fill',
    };

    window.showToast = function (message, type, duration) {
        type     = type     || 'success';
        duration = duration || 5000;

        var container = document.getElementById('toast-container');
        var el = document.createElement('div');
        el.className = 'toast-item toast-' + type;
        el.setAttribute('role', 'status');

        var icon = document.createElement('i');
        icon.className = 'bi ' + (ICONS[type] || ICONS.info);
        icon.setAttribute('aria-hidden', 'true');

        var span = document.createElement('span');
        span.textContent = message;

        var btn = document.createElement('button');
        btn.className = 'toast-close';
        btn.setAttribute('aria-label', 'Fermer');
        btn.innerHTML = '&times;';
        btn.addEventListener('click', function () { el.remove(); });

        el.append(icon, span, btn);
        container.appendChild(el);

        setTimeout(function () {
            el.classList.add('toast-out');
            setTimeout(function () { el.remove(); }, 320);
        }, duration);
    };

    // Compteur dans la navbar
    (function syncNav() {
        var KEY = 'gescolab_test_results_v1';
        var total = parseInt(document.body.dataset.total || '0', 10);
        function update() {
            try {
                var r    = JSON.parse(localStorage.getItem(KEY) || '{}');
                var pass = Object.values(r).filter(function(v){ return v === 'pass'; }).length;
                var fail = Object.values(r).filter(function(v){ return v === 'fail'; }).length;
                if (pass + fail > 0) {
                    document.getElementById('nav-progress').style.display = '';
                    document.getElementById('nav-pass').textContent = pass;
                    document.getElementById('nav-fail').textContent = fail;
                }
            } catch(e) {}
        }
        update();
        window.addEventListener('storage', update);
        window.addEventListener('test-result-changed', update);
    })();
})();
</script>

@stack('scripts')
</body>
</html>