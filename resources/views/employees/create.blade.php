@extends('layouts.app')
@section('page-title', 'Nouvel employé')

@section('header-actions')
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('employees.store') }}">
@csrf
<div class="row g-3">

    {{-- INFOS PERSONNELLES --}}
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Informations personnelles</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}"
                               class="form-control @error('first_name') is-invalid @enderror" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}"
                               class="form-control @error('last_name') is-invalid @enderror" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Email professionnel <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Date de naissance</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Lieu de naissance</label>
                        <input type="text" name="birth_place" value="{{ old('birth_place') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Nationalité</label>
                        <input type="text" name="nationality" value="{{ old('nationality', 'Ivoirienne') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Situation familiale</label>
                        <select name="marital_status" class="form-select">
                            <option value="single"   {{ old('marital_status') === 'single'   ? 'selected' : '' }}>Célibataire</option>
                            <option value="married"  {{ old('marital_status') === 'married'  ? 'selected' : '' }}>Marié(e)</option>
                            <option value="divorced" {{ old('marital_status') === 'divorced' ? 'selected' : '' }}>Divorcé(e)</option>
                            <option value="widowed"  {{ old('marital_status') === 'widowed'  ? 'selected' : '' }}>Veuf/Veuve</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Nb. enfants</label>
                        <input type="number" name="children_count" value="{{ old('children_count', 0) }}" class="form-control" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">N° CNPS</label>
                        <input type="text" name="cnps_number" value="{{ old('cnps_number') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Adresse</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- INFOS PROFESSIONNELLES --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-briefcase me-2"></i>Informations professionnelles</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Poste <span class="text-danger">*</span></label>
                        <input type="text" name="position" value="{{ old('position') }}"
                               class="form-control @error('position') is-invalid @enderror" required>
                        @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Département <span class="text-danger">*</span></label>
                        <input type="text" name="department" value="{{ old('department') }}"
                               class="form-control @error('department') is-invalid @enderror"
                               list="dept-list" required>
                        <datalist id="dept-list">
                            <option>Direction</option>
                            <option>Ressources Humaines</option>
                            <option>Finance & Comptabilité</option>
                            <option>Informatique</option>
                            <option>Commercial</option>
                            <option>Logistique</option>
                            <option>Marketing</option>
                        </datalist>
                        @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Date d'embauche <span class="text-danger">*</span></label>
                        <input type="date" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}"
                               class="form-control @error('hire_date') is-invalid @enderror" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Solde congés initial (jours)</label>
                        <input type="number" name="leave_balance" value="{{ old('leave_balance', 30) }}"
                               class="form-control" min="0">
                    </div>
                </div>
            </div>
        </div>

        {{-- CONTRAT --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Contrat initial</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Type de contrat <span class="text-danger">*</span></label>
                        <select name="contract_type" class="form-select" required id="contract-type">
                            <option value="cdi">CDI</option>
                            <option value="cdd">CDD</option>
                            <option value="internship">Stage</option>
                            <option value="consulting">Consulting</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Salaire de base (FCFA) <span class="text-danger">*</span></label>
                        <input type="number" name="base_salary" value="{{ old('base_salary') }}"
                               class="form-control @error('base_salary') is-invalid @enderror" min="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Grille salariale</label>
                        <select name="salary_grid_id" class="form-select">
                            <option value="">— Aucune —</option>
                            @foreach($salaryGrids as $grid)
                                <option value="{{ $grid->id }}" {{ old('salary_grid_id') == $grid->id ? 'selected' : '' }}>
                                    {{ $grid->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6" id="end-date-field">
                        <label class="form-label small fw-medium">Date de fin <small class="text-muted">(CDD / Stage)</small></label>
                        <input type="date" name="contract_end_date" value="{{ old('contract_end_date') }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SIDEBAR --}}
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-shield-lock me-2"></i>Accès applicatif</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Rôle <span class="text-danger">*</span></label>
                    <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Détermine les accès dans l'application.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Mot de passe <span class="text-danger">*</span></label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required minlength="8">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label small fw-medium">Confirmer le mot de passe <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Enregistrer l'employé
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                    Annuler
                </a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const contractType = document.getElementById('contract-type');
const endDateField = document.getElementById('end-date-field');

function toggleEndDate() {
    const show = ['cdd', 'internship', 'consulting'].includes(contractType.value);
    endDateField.style.display = show ? 'block' : 'none';
}
contractType.addEventListener('change', toggleEndDate);
toggleEndDate();
</script>
@endpush
