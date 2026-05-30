<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GES-COLAB') — GES-COLAB RH</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/gescolab.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>
<script>if(localStorage.getItem('gescolab_sidebar_collapsed')==='1')document.body.classList.add('sidebar-collapsed');</script>

{{-- ── SIDEBAR ────────────────────────────────────────────── --}}
<nav id="sidebar" role="navigation" aria-label="Menu principal">
    <div class="sidebar-brand">
        <div class="brand-icon" aria-hidden="true">G</div>
        <div>
            <div class="brand-name">GES-COLAB</div>
            <div class="brand-sub">Gestion RH v1.0</div>
        </div>
    </div>

    <div class="sidebar-nav">

        <div class="nav-section">Principal</div>

        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Tableau de bord">
            <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i>
            <span class="nav-label">Tableau de bord</span>
        </a>

        <a href="{{ route('messages.index') }}"
           class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}"
           data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Messagerie">
            <i class="bi bi-chat-dots-fill" aria-hidden="true"></i>
            <span class="nav-label">Messagerie</span>
            @php $unread = auth()->user()->unreadMessagesCount(); @endphp
            @if($unread > 0)
                <span class="nav-badge" aria-label="{{ $unread }} messages non lus">{{ $unread }}</span>
            @endif
        </a>

        <div class="nav-section">Ressources Humaines</div>

        @can('voir employés')
            <a href="{{ route('employees.index') }}"
               class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Employés">
                <i class="bi bi-people-fill" aria-hidden="true"></i>
                <span class="nav-label">Employés</span>
            </a>
        @endcan

        @can('voir contrats')
            <a href="{{ route('contracts.index') }}"
               class="nav-item {{ request()->routeIs('contracts.*') ? 'active' : '' }}"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Contrats">
                <i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i>
                <span class="nav-label">Contrats</span>
            </a>
        @endcan

        @can('voir congés')
            <a href="{{ route('leaves.index') }}"
               class="nav-item {{ request()->routeIs('leaves.*') ? 'active' : '' }}"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Congés &amp; Permissions">
                <i class="bi bi-calendar-check-fill" aria-hidden="true"></i>
                <span class="nav-label">Congés &amp; Permissions</span>

                @php
                    $user     = auth()->user();
                    $employee = $user->employee;

                    if ($user->hasRole(['superadmin', 'admin'])) {
                        $badgeCount = \App\Models\Leave::whereIn('workflow_step', [
                            'pending_n1', 'pending_rh'
                        ])->count();

                    } elseif ($user->hasRole('rh')) {
                        $badgeCount = \App\Models\Leave::where('workflow_step', 'pending_rh')
                                                       ->count();

                    } elseif ($employee?->poste?->can_be_n1) {
                        $subordinateIds = \App\Models\Employee::where('supervisor_id', $employee->id)
                                                              ->pluck('id')
                                                              ->toArray();
                        $badgeCount = empty($subordinateIds) ? 0
                                    : \App\Models\Leave::where('workflow_step', 'pending_n1')
                                                       ->whereIn('employee_id', $subordinateIds)
                                                       ->count();
                    } else {
                        $badgeCount = $employee
                            ? \App\Models\Leave::where('employee_id', $employee->id)
                                               ->where('status', 'pending')
                                               ->count()
                            : 0;
                    }
                @endphp

                @if($badgeCount > 0)
                    <span class="nav-badge" aria-label="{{ $badgeCount }} demandes en attente">{{ $badgeCount }}</span>
                @endif
            </a>
        @endcan

        @role('superadmin|admin|rh')
        <a href="{{ route('postes.index') }}"
           class="nav-item {{ request()->routeIs('postes.*') ? 'active' : '' }}"
           data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Postes &amp; Hiérarchie">
            <i class="bi bi-diagram-3-fill" aria-hidden="true"></i>
            <span class="nav-label">Postes &amp; Hiérarchie</span>
        </a>
        @endrole

        <div class="nav-section">Paie</div>

        @can('voir fiches de paie')
            <a href="{{ route('payroll.index') }}"
               class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Gestion de la paie">
                <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                <span class="nav-label">Gestion de la paie</span>
            </a>
        @endcan

        @can('voir grilles salariales')
            <a href="{{ route('salary-grids.index') }}"
               class="nav-item {{ request()->routeIs('salary-grids.*') ? 'active' : '' }}"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Grilles salariales">
                <i class="bi bi-table" aria-hidden="true"></i>
                <span class="nav-label">Grilles salariales</span>
            </a>
        @endcan

        @role('superadmin|admin')
        <div class="nav-section">Administration</div>

        <a href="{{ route('roles.index') }}"
           class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}"
           data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Rôles &amp; Permissions">
            <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
            <span class="nav-label">Rôles &amp; Permissions</span>
        </a>

        <a href="{{ route('config.index') }}"
           class="nav-item {{ request()->routeIs('config.*') ? 'active' : '' }}"
           data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Configuration">
            <i class="bi bi-gear-fill" aria-hidden="true"></i>
            <span class="nav-label">Configuration</span>
        </a>
        @endrole

    </div>

    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="user-avatar" aria-hidden="true">{{ auth()->user()->initials }}</div>
            <div style="flex:1;min-width:0">
                <div class="user-name text-truncate">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ auth()->user()->primary_role_label }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="btn-sidebar-logout"
                        title="Déconnexion"
                        aria-label="Se déconnecter">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ── TOPBAR ──────────────────────────────────────────────── --}}
<header id="topbar">
    <button class="btn-mobile-menu d-md-none me-2"
            onclick="document.getElementById('sidebar').classList.toggle('show')"
            aria-label="Ouvrir le menu"
            aria-expanded="false"
            aria-controls="sidebar">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>

    <button id="sidebar-toggle"
            class="sidebar-toggle-btn sidebar-open me-1"
            aria-label="Réduire la barre latérale"
            data-open="true">
        <i class="bi bi-layout-sidebar-reverse" aria-hidden="true"></i>
    </button>

    <span class="page-title @hasSection('breadcrumb') d-none @endif">@yield('page-title', 'Tableau de bord')</span>
    @hasSection('breadcrumb')
        @yield('breadcrumb')
    @endif

    <div class="d-flex align-items-center gap-2">

        {{-- Notifications --}}
        <div class="dropdown">
            <button class="btn btn-light btn-sm position-relative"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Notifications">
                <i class="bi bi-bell" style="font-size:16px" aria-hidden="true"></i>
                @if(isset($unread) && $unread > 0)
                    <span class="position-absolute top-0 start-100 translate-middle
                                 badge rounded-pill bg-danger" style="font-size:10px"
                          aria-label="{{ $unread }} notifications">
                        {{ $unread }}
                    </span>
                @endif
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                style="width:290px;padding:8px"
                role="menu"
                aria-label="Liste des notifications">
                <li><h6 class="dropdown-header" style="font-size:12px">Notifications</h6></li>
                @php $pendingLeaves = \App\Models\Leave::pending()->count(); @endphp
                @if($pendingLeaves)
                    <li>
                        <a class="dropdown-item py-2" style="font-size:13.5px"
                           href="{{ route('leaves.index') }}?status=pending"
                           role="menuitem">
                            <i class="bi bi-calendar-x text-warning me-2" aria-hidden="true"></i>
                            {{ $pendingLeaves }} demande(s) de congé en attente
                        </a>
                    </li>
                @else
                    <li>
                    <span class="dropdown-item-text text-muted" style="font-size:13.5px">
                        Aucune notification
                    </span>
                    </li>
                @endif
            </ul>
        </div>

        {{-- Menu profil --}}
        <div class="dropdown">
            <button class="btn btn-light btn-sm d-flex align-items-center gap-2"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Menu utilisateur">
                <div class="avatar-initials avatar-sm avatar-blue" aria-hidden="true">
                    {{ auth()->user()->initials }}
                </div>
                <span class="d-none d-md-inline" style="font-size:13.5px">
                    {{ auth()->user()->name }}
                </span>
                <i class="bi bi-chevron-down" style="font-size:11px" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" role="menu">
                <li>
                    <a class="dropdown-item" style="font-size:14px"
                       href="{{ route('profile.edit') }}"
                       role="menuitem">
                        <i class="bi bi-person me-2" aria-hidden="true"></i>Mon profil
                    </a>
                </li>
                @can('voir fiches de paie')
                    <li>
                        <a class="dropdown-item" style="font-size:14px"
                           href="{{ route('payroll.index') }}"
                           role="menuitem">
                            <i class="bi bi-receipt me-2" aria-hidden="true"></i>Mes fiches de paie
                        </a>
                    </li>
                @endcan
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="dropdown-item text-danger"
                                style="font-size:14px"
                                role="menuitem">
                            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Déconnexion
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        @yield('header-actions')
    </div>
</header>

{{-- ── TOAST CONTAINER ─────────────────────────────────────── --}}
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>

{{-- ── CONTENT ─────────────────────────────────────────────── --}}
<div id="content-wrapper">
    <div class="content-inner">

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
(function () {
    var STORAGE_KEY = 'gescolab_sidebar_collapsed';
    var body   = document.body;
    var toggle = document.getElementById('sidebar-toggle');

    // Init Bootstrap tooltips on nav items — disabled by default, enabled when collapsed
    var navTooltipEls = document.querySelectorAll('.nav-item[data-bs-toggle="tooltip"]');
    var navTooltips   = Array.from(navTooltipEls).map(function (el) {
        return new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    function syncTooltips(collapsed) {
        navTooltips.forEach(function (t) { collapsed ? t.enable() : t.disable(); });
    }

    function syncIcon(collapsed) {
        if (!toggle) return;
        var open = !collapsed;
        toggle.dataset.open = open;
        toggle.setAttribute('aria-label',
            open ? 'Réduire la barre latérale' : 'Ouvrir la barre latérale');
        toggle.classList.toggle('sidebar-open', open);
        toggle.querySelector('i').className =
            open ? 'bi bi-layout-sidebar-reverse' : 'bi bi-layout-sidebar';
    }

    // Apply initial state (body class was set by inline script; sync UI)
    var isCollapsed = body.classList.contains('sidebar-collapsed');
    syncTooltips(isCollapsed);
    syncIcon(isCollapsed);

    if (toggle) {
        toggle.addEventListener('click', function () {
            isCollapsed = !isCollapsed;
            body.classList.toggle('sidebar-collapsed', isCollapsed);
            localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
            syncTooltips(isCollapsed);
            syncIcon(isCollapsed);
        });
    }

    // Fermer la sidebar mobile en cliquant en dehors
    document.addEventListener('click', function (e) {
        var sidebar = document.getElementById('sidebar');
        if (sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !e.target.closest('.btn-mobile-menu')) {
                sidebar.classList.remove('show');
            }
        }
    });
})();

// ── Système de toasts ─────────────────────────────────────────
(function () {
    var ICONS = {
        success: 'bi-check-circle-fill',
        error:   'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info:    'bi-info-circle-fill',
    };

    window.showToast = function (message, type, duration) {
        type     = type     || 'success';
        duration = duration || 7000;

        var container = document.getElementById('toast-container');
        var el = document.createElement('div');
        el.className = 'toast-item toast-' + type;
        el.setAttribute('role', 'status');
        el.innerHTML =
            '<i class="bi ' + (ICONS[type] || ICONS.info) + '" aria-hidden="true"></i>' +
            '<span>' + message + '</span>' +
            '<button class="toast-close" onclick="this.parentElement.remove()" aria-label="Fermer">' +
            '&times;</button>';
        container.appendChild(el);

        setTimeout(function () {
            el.classList.add('toast-out');
            setTimeout(function () { el.remove(); }, 320);
        }, duration);
    };

    // Afficher automatiquement les messages de session au chargement
    @if(session('success'))
        document.addEventListener('DOMContentLoaded', function () {
            showToast(@json(session('success')), 'success');
        });
    @endif
    @if(session('error'))
        document.addEventListener('DOMContentLoaded', function () {
            showToast(@json(session('error')), 'error');
        });
    @endif
    @if(session('warning'))
        document.addEventListener('DOMContentLoaded', function () {
            showToast(@json(session('warning')), 'warning');
        });
    @endif
})();
</script>
@stack('scripts')
</body>
</html>
