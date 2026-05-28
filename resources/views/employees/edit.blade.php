@extends('layouts.app')
@section('page-title', 'Modifier — '.$employee->full_name)

@section('header-actions')
    <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('employees.update', $employee) }}">
@csrf
@method('PUT')
<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Informations personnelles</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Date de naissance</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Lieu de naissance</label>
                        <input type="text" name="birth_place" value="{{ old('birth_place', $employee->birth_place) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Nationalité</label>
                        <input type="text" name="nationality" value="{{ old('nationality', $employee->nationality) }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Situation familiale</label>
                        <select name="marital_status" class="form-select">
                            @foreach(['single'=>'Célibataire','married'=>'Marié(e)','divorced'=>'Divorcé(e)','widowed'=>'Veuf/Veuve'] as $val => $label)
                            <option value="{{ $val }}" {{ old('marital_status', $employee->marital_status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Enfants</label>
                        <input type="number" name="children_count" value="{{ old('children_count', $employee->children_count) }}" class="form-control" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">N° CNPS</label>
                        <input type="text" name="cnps_number" value="{{ old('cnps_number', $employee->cnps_number) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Adresse</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $employee->address) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-briefcase me-2"></i>Informations professionnelles</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Poste <span class="text-danger">*</span></label>
                        <input type="text" name="position" value="{{ old('position', $employee->position) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Département <span class="text-danger">*</span></label>
                        <input type="text" name="department" value="{{ old('department', $employee->department) }}" class="form-control" list="dept-list" required>
                        <datalist id="dept-list">
                            <option>Direction</option><option>Ressources Humaines</option>
                            <option>Finance & Comptabilité</option><option>Informatique</option>
                            <option>Commercial</option><option>Logistique</option>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Supérieur hiérarchique (N+1)</label>
                        <select name="supervisor_id" class="form-select">
                            <option value="">— Aucun —</option>
                            @foreach($supervisors as $sup)
                                <option value="{{ $sup->id }}"
                                    {{ old('supervisor_id', $employee->supervisor_id) == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->full_name }} — {{ $sup->position }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Date d'embauche <span class="text-danger">*</span></label>
                        <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Solde congés (jours)</label>
                        <input type="number" name="leave_balance" value="{{ old('leave_balance', $employee->leave_balance) }}" class="form-control" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Statut</label>
                        <select name="status" class="form-select">
                            @foreach(['active'=>'Actif','on_leave'=>'En congé','suspended'=>'Suspendu','terminated'=>'Parti'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $employee->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Rôle applicatif</div>
            <div class="card-body">
                <label class="form-label small fw-medium">Rôle</label>
                <select name="role" class="form-select">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}"
                            {{ $employee->user?->hasRole($role->name) ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Enregistrer les modifications
                </button>
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection
