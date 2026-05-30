@extends('layouts.app')
@section('page-title', 'Rôles & Permissions')

@section('header-actions')
    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Nouveau rôle
    </a>
@endsection

@section('content')
<div class="row g-3">

    {{-- LISTE DES RÔLES --}}
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <span>Rôles disponibles</span>
                <span class="badge bg-primary">{{ $roles->count() }}</span>
            </div>
            <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <x-sort-th column="name" label="Rôle" />
                        <th>Utilisateurs</th>
                        <th>Permissions</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                @php
                    $colors = [
                        'superadmin'   => 'danger',
                        'admin'        => 'primary',
                        'rh'           => 'purple',
                        'comptable'    => 'warning',
                        'informaticien'=> 'success',
                        'user'         => 'secondary',
                    ];
                    $color = $colors[$role->name] ?? 'secondary';
                @endphp
                <tr>
                    <td>
                        <span class="badge bg-{{ $color }} badge-status">{{ ucfirst($role->name) }}</span>
                    </td>
                    <td>{{ $role->users->count() }}</td>
                    <td>{{ $role->permissions->count() }}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-md d-flex justify-content-start gap-2">
                            <div>
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil"></i> &nbsp; Modifier
                                </a>
                            </div>
                            @if(!in_array($role->name, ['superadmin', 'admin', 'user']))
                            <div>
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline"
                                      onsubmit="return confirm('Supprimer le rôle « {{ $role->name }} » ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger"
                                            aria-label="Supprimer le rôle {{ $role->name }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i> &nbsp; Supprimer
                                    </button>
                                </form>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- MATRICE PERMISSIONS --}}
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header">Matrice des permissions par rôle</div>
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
            <table class="table table-sm mb-0" style="font-size:12px">
                <thead style="position:sticky;top:0;background:#fff;z-index:1">
                    <tr>
                        <th style="min-width:180px">Permission</th>
                        @foreach($roles as $role)
                            <th class="text-center" style="white-space:nowrap">{{ ucfirst($role->name) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach($permissions->groupBy(fn($p) => explode(' ', $p->name)[1] ?? 'général') as $module => $perms)
                    <tr class="table-light">
                        <td colspan="{{ $roles->count() + 1 }}"
                            class="fw-semibold text-muted py-1 ps-2"
                            style="font-size:10px;text-transform:uppercase;letter-spacing:.06em">
                            {{ ucfirst($module) }}
                        </td>
                    </tr>
                    @foreach($perms as $permission)
                    <tr>
                        <td class="ps-3">{{ $permission->name }}</td>
                        @foreach($roles as $role)
                        <td class="text-center">
                            @if($role->hasPermissionTo($permission))
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-x-circle" style="color:#dee2e6"></i>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
            </div>
        </div>

        {{-- ASSIGNATION RAPIDE --}}
        <div class="card">
            <div class="card-header">Assigner un rôle</div>
            <div class="card-body">
                <form id="assign-form" method="POST" action="#" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label small fw-medium">Utilisateur</label>
                        <select class="form-select form-select-sm" id="user-select" onchange="updateAssignUrl(this)">
                            <option value="">— Sélectionner —</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" data-url="{{ route('users.roles.assign', $u) }}">
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <x-select
                            name="role"
                            label="Nouveau rôle"
                            :options="$roles->pluck('name')->mapWithKeys(fn($n) => [$n => ucfirst($n)])->all()"
                            class="form-select-sm"
                        />
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Assigner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateAssignUrl(sel) {
    const url = sel.options[sel.selectedIndex]?.dataset?.url;
    if (url) document.getElementById('assign-form').action = url;
}
</script>
@endpush
