@extends('layouts.app')
@section('page-title', 'Nouveau rôle')

@section('header-actions')
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
<form method="POST" action="{{ route('roles.store') }}">
@csrf
<div class="card mb-3">
    <div class="card-header">Informations du rôle</div>
    <div class="card-body">
        <div class="mb-4">
            <label class="form-label small fw-medium">Nom du rôle <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="ex: superviseur" required>
            <div class="form-text">En minuscules, sans espaces (ex: superviseur, chef_projet)</div>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <label class="form-label small fw-medium mb-2">Permissions associées</label>
        @foreach($permissions as $module => $perms)
        <div class="mb-3">
            <div class="fw-semibold text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.06em">
                {{ ucfirst($module) }}
            </div>
            <div class="row g-2">
                @foreach($perms as $perm)
                <div class="col-md-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="permissions[]" value="{{ $perm->id }}"
                               id="perm-{{ $perm->id }}"
                               {{ in_array($perm->id, old('permissions', [])) ? 'checked' : '' }}>
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
        <i class="bi bi-check-circle me-1"></i>Créer le rôle
    </button>
    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
</form>
</div>
</div>
@endsection
