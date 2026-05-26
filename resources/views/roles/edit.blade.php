@extends('layouts.app')
@section('page-title', 'Modifier — '.ucfirst($role->name))

@section('header-actions')
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
<form method="POST" action="{{ route('roles.update', $role) }}">
@csrf @method('PUT')
<div class="card mb-3">
    <div class="card-header">Modifier le rôle</div>
    <div class="card-body">
        <div class="mb-4">
            <label class="form-label small fw-medium">Nom du rôle <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $role->name) }}"
                   class="form-control @error('name') is-invalid @enderror"
                   {{ in_array($role->name, ['superadmin','admin','user']) ? 'readonly' : '' }} required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <label class="form-label small fw-medium mb-2">Permissions associées</label>
        @php $rolePermIds = $role->permissions->pluck('id')->toArray(); @endphp
        @foreach($permissions as $module => $perms)
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fw-semibold text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.06em">
                    {{ ucfirst($module) }}
                </span>
                <button type="button" class="btn btn-link p-0 text-muted" style="font-size:11px"
                        onclick="toggleModule('{{ $module }}')">Tout sélectionner</button>
            </div>
            <div class="row g-2" id="module-{{ $module }}">
                @foreach($perms as $perm)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input perm-{{ $module }}" type="checkbox"
                               name="permissions[]" value="{{ $perm->id }}"
                               id="perm-{{ $perm->id }}"
                               {{ in_array($perm->id, old('permissions', $rolePermIds)) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="perm-{{ $perm->id }}">
                            {{ $perm->name }}
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i>Enregistrer
    </button>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
</form>
</div>
</div>
@endsection

@push('scripts')
<script>
function toggleModule(module) {
    const checkboxes = document.querySelectorAll('.perm-' + module);
    const allChecked = [...checkboxes].every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endpush
