<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GES-COLAB') — GES-COLAB RH</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>

        /* ── BASE ────────────────────────────────────────────── */
        html { font-size: 16px; }

        :root {
            --sidebar-w: 250px;
            --topbar-h: 62px;
            --blue: #185FA5;
            --blue-light: #E6F1FB;
            --sidebar-bg: #0d1b2e;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f2f5;
            margin: 0;
            font-size: 15px;
        }

        /* ── SIDEBAR ─────────────────────────────────────────── */
        #sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            z-index: 1040;
            transition: transform .25s ease;
        }

        .sidebar-brand {
            height: var(--topbar-h);
            display: flex; align-items: center; gap: 12px;
            padding: 0 18px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            flex-shrink: 0;
        }
        .brand-icon {
            width: 38px; height: 38px; background: var(--blue);
            border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-size: 20px; font-weight: 700;
            color: #fff; flex-shrink: 0;
        }
        .brand-name { color: #fff; font-weight: 600; font-size: 16px; line-height: 1.1; }
        .brand-sub  { color: rgba(255,255,255,.4); font-size: 11px; }

        .sidebar-nav { flex: 1; padding: 8px 0; overflow-y: auto; }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }

        .nav-section {
            padding: 14px 18px 4px;
            font-size: 11px; font-weight: 600;
            letter-spacing: .08em; text-transform: uppercase;
            color: rgba(255,255,255,.3);
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 18px; color: rgba(255,255,255,.65);
            font-size: 14.5px; text-decoration: none;
            transition: background .15s, color .15s;
            position: relative; cursor: pointer;
        }
        .nav-item:hover  { background: rgba(255,255,255,.06); color: #fff; }
        .nav-item.active { background: rgba(24,95,165,.35); color: #fff; }
        .nav-item.active::before {
            content: ''; position: absolute; left: 0; top: 4px; bottom: 4px;
            width: 3px; background: var(--blue); border-radius: 0 2px 2px 0;
        }
        .nav-item i { font-size: 17px; width: 20px; flex-shrink: 0; }
        .nav-badge {
            margin-left: auto; padding: 2px 8px;
            background: #dc3545; color: #fff;
            font-size: 11px; font-weight: 600; border-radius: 99px;
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,.06);
            padding: 14px 18px; flex-shrink: 0;
        }
        .user-pill { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--blue); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600; flex-shrink: 0;
        }
        .user-name { color: #fff; font-size: 14px; font-weight: 500; line-height: 1.2; }
        .user-role { color: rgba(255,255,255,.45); font-size: 12px; }

        /* ── TOPBAR ──────────────────────────────────────────── */
        #topbar {
            position: fixed; top: 0;
            left: var(--sidebar-w); right: 0;
            height: var(--topbar-h); z-index: 1030;
            background: #fff;
            border-bottom: 1px solid #e8ecf0;
            display: flex; align-items: center;
            padding: 0 22px; gap: 12px;
        }
        .page-title { font-size: 17px; font-weight: 600; color: #1a1a2e; flex: 1; }

        /* ── CONTENT ─────────────────────────────────────────── */
        #content-wrapper {
            margin-left: var(--sidebar-w);
            padding-top: var(--topbar-h);
            min-height: 100vh;
        }
        .content-inner { padding: 26px; }

        /* ── CARDS ───────────────────────────────────────────── */
        .card {
            border: 1px solid #e8ecf0;
            border-radius: 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e8ecf0;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 18px;
            font-weight: 600; font-size: 15px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-footer {
            background: #fafbfc;
            border-top: 1px solid #e8ecf0;
            border-radius: 0 0 12px 12px !important;
        }

        /* ── TABLES ──────────────────────────────────────────── */
        .table th {
            font-size: 12px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .05em;
            color: #6c757d; border-bottom-width: 1px;
            white-space: nowrap; padding: 11px 14px;
        }
        .table td {
            font-size: 14px; vertical-align: middle; padding: 11px 14px;
        }
        .table-hover tbody tr:hover td { background: #f8f9ff; }

        /* ── BADGES ──────────────────────────────────────────── */
        .badge-status {
            font-size: 12px; padding: 4px 11px;
            border-radius: 99px; font-weight: 500;
        }

        /* ── FILTER CARD ─────────────────────────────────────── */
        .filter-card {
            background: #fff; border: 1px solid #e8ecf0;
            border-radius: 12px; padding: 15px 18px; margin-bottom: 20px;
        }
        .filter-card label {
            font-size: 12px; font-weight: 600; color: #6c757d;
            margin-bottom: 3px;
            text-transform: uppercase; letter-spacing: .04em;
        }

        /* ── BUTTONS ─────────────────────────────────────────── */
        .btn { font-size: 14px; }
        .btn-sm { font-size: 13px; }
        .btn-primary { background: var(--blue); border-color: var(--blue); }
        .btn-primary:hover { background: #0C447C; border-color: #0C447C; }
        .btn-outline-primary { color: var(--blue); border-color: var(--blue); }
        .btn-outline-primary:hover { background: var(--blue); border-color: var(--blue); }

        /* ── AVATAR ──────────────────────────────────────────── */
        .avatar-initials {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 600; flex-shrink: 0;
        }

        /* ── SEARCH ──────────────────────────────────────────── */
        .search-wrapper { position: relative; }
        .search-wrapper .bi-search {
            position: absolute; left: 10px; top: 50%;
            transform: translateY(-50%); color: #adb5bd; font-size: 14px;
        }
        .search-wrapper input { padding-left: 32px; }

        /* ── ALERTS ──────────────────────────────────────────── */
        .alert { border-radius: 10px; border: none; font-size: 14.5px; }

        /* ── FORMULAIRES ─────────────────────────────────────── */
        .form-control, .form-select {
            font-size: 14px;
        }
        .form-label {
            font-size: 13.5px;
            font-weight: 500;
        }
        .form-text {
            font-size: 12px;
        }
        .form-check-label {
            font-size: 14px;
        }

        /* ── MODALES ─────────────────────────────────────────── */
        .modal-title { font-size: 16px; }
        .modal-body  { font-size: 14px; }

        /* ── DROPDOWNS ───────────────────────────────────────── */
        .dropdown-item { font-size: 14px; }

        /* ── PAGINATION ──────────────────────────────────────── */
        .pagination { font-size: 14px; }

        /* ── SMALL ───────────────────────────────────────────── */
        small, .small { font-size: 12.5px !important; }

        /* ── RESPONSIVE ──────────────────────────────────────── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #topbar { left: 0; }
            #content-wrapper { margin-left: 0; }
        }

        /* ── SORT HEADERS ───────────────────────────────────── */
        .sort-th {
            color: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            user-select: none;
            gap: 2px;
        }
        .sort-th:hover { color: #1d4ed8; }
        .sort-th--active { color: #1d4ed8; font-weight: 600; }

        /* ── PRINT ───────────────────────────────────────────── */
        @media print {
            #sidebar, #topbar { display: none !important; }
            #content-wrapper { margin: 0; padding: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

{{-- ── SIDEBAR ────────────────────────────────────────────── --}}
<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">G</div>
        <div>
            <div class="brand-name">GES-COLAB</div>
            <div class="brand-sub">Gestion RH v1.0</div>
        </div>
    </div>

    <div class="sidebar-nav">

        <div class="nav-section">Principal</div>

        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Tableau de bord
        </a>

        <a href="{{ route('messages.index') }}"
           class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
            <i class="bi bi-chat-dots-fill"></i> Messagerie
            @php $unread = auth()->user()->unreadMessagesCount(); @endphp
            @if($unread > 0)
                <span class="nav-badge">{{ $unread }}</span>
            @endif
        </a>

        <div class="nav-section">Ressources Humaines</div>

        @can('voir employés')
            <a href="{{ route('employees.index') }}"
               class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Employés
            </a>
        @endcan

        @can('voir contrats')
            <a href="{{ route('contracts.index') }}"
               class="nav-item {{ request()->routeIs('contracts.*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-text-fill"></i> Contrats
            </a>
        @endcan

        @can('voir congés')
            <a href="{{ route('leaves.index') }}"
               class="nav-item {{ request()->routeIs('leaves.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i> Congés & Permissions

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
                    <span class="nav-badge">{{ $badgeCount }}</span>
                @endif
            </a>
        @endcan

        @role('superadmin|admin|rh')
        <a href="{{ route('postes.index') }}"
           class="nav-item {{ request()->routeIs('postes.*') ? 'active' : '' }}">
            <i class="bi bi-diagram-3-fill"></i> Postes & Hiérarchie
        </a>
        @endrole

        <div class="nav-section">Paie</div>

        @can('voir fiches de paie')
            <a href="{{ route('payroll.index') }}"
               class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}">
                <i class="bi bi-receipt-cutoff"></i> Gestion de la paie
            </a>
        @endcan

        @can('voir grilles salariales')
            <a href="{{ route('salary-grids.index') }}"
               class="nav-item {{ request()->routeIs('salary-grids.*') ? 'active' : '' }}">
                <i class="bi bi-table"></i> Grilles salariales
            </a>
        @endcan

        @role('superadmin|admin')
        <div class="nav-section">Administration</div>

        <a href="{{ route('roles.index') }}"
           class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
            <i class="bi bi-shield-lock-fill"></i> Rôles & Permissions
        </a>

        <a href="{{ route('config.index') }}"
           class="nav-item {{ request()->routeIs('config.*') ? 'active' : '' }}">
            <i class="bi bi-gear-fill"></i> Configuration
        </a>
        @endrole

    </div>

    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="user-avatar">{{ auth()->user()->initials }}</div>
            <div style="flex:1;min-width:0">
                <div class="user-name text-truncate">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ auth()->user()->primary_role_label }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        style="background:none;border:none;
                               color:rgba(255,255,255,.4);
                               cursor:pointer;padding:4px"
                        title="Déconnexion">
                    <i class="bi bi-box-arrow-right" style="font-size:18px"></i>
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ── TOPBAR ──────────────────────────────────────────────── --}}
<header id="topbar">
    <button class="btn btn-link d-md-none p-0 me-2 text-dark"
            onclick="document.getElementById('sidebar').classList.toggle('show')">
        <i class="bi bi-list fs-4"></i>
    </button>

    <span class="page-title">@yield('page-title', 'Tableau de bord')</span>

    <div class="d-flex align-items-center gap-2">

        {{-- Recherche globale --}}
        <div class="search-wrapper d-none d-lg-block">
            <i class="bi bi-search"></i>
            <input type="text" id="global-search"
                   class="form-control form-control-sm"
                   placeholder="Rechercher un employé..."
                   style="width:210px;font-size:13.5px">
        </div>

        {{-- Notifications --}}
        <div class="dropdown">
            <button class="btn btn-light btn-sm position-relative"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell" style="font-size:16px"></i>
                @if(isset($unread) && $unread > 0)
                    <span class="position-absolute top-0 start-100 translate-middle
                                 badge rounded-pill bg-danger" style="font-size:10px">
                        {{ $unread }}
                    </span>
                @endif
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                style="width:290px;padding:8px">
                <li><h6 class="dropdown-header" style="font-size:12px">Notifications</h6></li>
                @php $pendingLeaves = \App\Models\Leave::pending()->count(); @endphp
                @if($pendingLeaves)
                    <li>
                        <a class="dropdown-item py-2" style="font-size:13.5px"
                           href="{{ route('leaves.index') }}?status=pending">
                            <i class="bi bi-calendar-x text-warning me-2"></i>
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
                    data-bs-toggle="dropdown">
                <div class="avatar-initials"
                     style="width:28px;height:28px;font-size:11px;
                            background:#E6F1FB;color:#185FA5">
                    {{ auth()->user()->initials }}
                </div>
                <span class="d-none d-md-inline" style="font-size:13.5px">
                    {{ auth()->user()->name }}
                </span>
                <i class="bi bi-chevron-down" style="font-size:11px"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li>
                    <a class="dropdown-item" style="font-size:14px"
                       href="{{ route('profile.edit') }}">
                        <i class="bi bi-person me-2"></i>Mon profil
                    </a>
                </li>
                @can('voir fiches de paie')
                    <li>
                        <a class="dropdown-item" style="font-size:14px"
                           href="{{ route('payroll.index') }}">
                            <i class="bi bi-receipt me-2"></i>Mes fiches de paie
                        </a>
                    </li>
                @endcan
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="dropdown-item text-danger"
                                style="font-size:14px">
                            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        @yield('header-actions')
    </div>
</header>

{{-- ── CONTENT ─────────────────────────────────────────────── --}}
<div id="content-wrapper">
    <div class="content-inner">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Erreurs :</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
    document.getElementById('global-search')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            window.location.href = '{{ route('employees.index') }}?search='
                + encodeURIComponent(this.value.trim());
        }
    });
</script>
@stack('scripts')
</body>
</html>
