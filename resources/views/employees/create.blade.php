@extends('layouts.app')
@section('page-title', 'Nouvel employé')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Employés', 'url' => route('employees.index')],
    ['label' => 'Nouvel employé'],
]" />
@endsection

@section('header-actions')
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('employees.store') }}">
@csrf
<div class="row g-3">

    {{-- ── INFOS PERSONNELLES ─────────────────────────────── --}}
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>Informations personnelles
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">
                            Prénom <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="first_name"
                               value="{{ old('first_name') }}"
                               class="form-control @error('first_name') is-invalid @enderror"
                               required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">
                            Nom <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="last_name"
                               value="{{ old('last_name') }}"
                               class="form-control @error('last_name') is-invalid @enderror"
                               required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">
                            Email professionnel <span class="text-danger">*</span>
                        </label>
                        <input type="email" name="email"
                               value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Téléphone</label>
                        <input type="text" name="phone"
                               value="{{ old('phone') }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Date de naissance</label>
                        <input type="date" name="birth_date"
                               value="{{ old('birth_date') }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Lieu de naissance</label>
                        <input type="text" name="birth_place"
                               value="{{ old('birth_place') }}"
                               class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Nationalité</label>
                        <input type="text" name="nationality"
                               value="{{ old('nationality', 'Ivoirienne') }}"
                               class="form-control">
                    </div>
                    <div class="col-md-3">
                        <x-select
                            name="marital_status"
                            label="Situation familiale"
                            :options="['single' => 'Célibataire', 'married' => 'Marié(e)', 'divorced' => 'Divorcé(e)', 'widowed' => 'Veuf/Veuve']"
                            :value="old('marital_status')"
                        />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-medium">Nb. enfants</label>
                        <input type="number" name="children_count"
                               value="{{ old('children_count', 0) }}"
                               class="form-control" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">N° CNPS</label>
                        <input type="text" name="cnps_number"
                               value="{{ old('cnps_number') }}"
                               class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Adresse</label>
                        <textarea name="address" class="form-control"
                                  rows="2">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── INFOS PROFESSIONNELLES ──────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-briefcase me-2"></i>Informations professionnelles
            </div>
            <div class="card-body">
                <div class="row g-3">

                    {{-- POSTE : sélection depuis la table postes --}}
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">
                            Poste <span class="text-danger">*</span>
                        </label>
                        <select name="poste_id" id="poste-select"
                                class="form-select @error('poste_id') is-invalid @enderror"
                                required onchange="onPosteChange(this)">
                            <option value="">— Sélectionner un poste —</option>
                            @foreach($postes as $poste)
                            <option value="{{ $poste->id }}"
                                    data-level="{{ $poste->level }}"
                                    data-can_n1="{{ $poste->can_be_n1 ? 1 : 0 }}"
                                    data-dept="{{ $poste->department }}"
                                    {{ old('poste_id') == $poste->id ? 'selected' : '' }}>
                                {{ $poste->title }}
                                (Niv. {{ $poste->level }}
                                — {{ $poste->department ?? 'Tous dépts' }})
                                {{ $poste->can_be_n1 ? '⭐' : '' }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            ⭐ = poste pouvant être N+1
                        </div>
                        @error('poste_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-medium">
                            Département <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="department" id="dept-field"
                               value="{{ old('department') }}"
                               class="form-control @error('department') is-invalid @enderror"
                               list="dept-list" required>
                        <datalist id="dept-list">
                            <option>Direction</option>
                            <option>Ressources Humaines</option>
                            <option>Finance & Comptabilité</option>
                            <option>Informatique</option>
                            <option>Commercial</option>
                            <option>Logistique</option>
                        </datalist>
                        @error('department')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-medium">
                            Date d'embauche <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="hire_date"
                               value="{{ old('hire_date', date('Y-m-d')) }}"
                               class="form-control @error('hire_date') is-invalid @enderror"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-medium">
                            Solde congés initial (jours)
                        </label>
                        <input type="number" name="leave_balance"
                               value="{{ old('leave_balance', 30) }}"
                               class="form-control" min="0">
                    </div>

                    {{-- N+1 : chargé dynamiquement selon le poste --}}
                    <div class="col-12">
                        <label class="form-label small fw-medium">
                            Supérieur hiérarchique (N+1)
                            <span class="text-muted fw-normal" id="n1-hint"></span>
                        </label>
                        <select name="supervisor_id" id="supervisor-select"
                                class="form-select">
                            <option value="">
                                — Sélectionnez d'abord un poste —
                            </option>
                        </select>
                        <div class="form-text" id="n1-info">
                            Le N+1 sera filtré selon le niveau du poste choisi.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── CONTRAT INITIAL ─────────────────────────────── --}}
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-text me-2"></i>Contrat initial
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <x-select
                            name="contract_type"
                            label="Type de contrat"
                            :options="['cdi' => 'CDI', 'cdd' => 'CDD', 'internship' => 'Stage', 'consulting' => 'Consulting']"
                            :value="old('contract_type', 'cdi')"
                            id="contract-type"
                            onchange="toggleEndDate()"
                            required
                        />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">
                            Salaire de base (FCFA) <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="base_salary"
                               value="{{ old('base_salary') }}"
                               class="form-control @error('base_salary') is-invalid @enderror"
                               min="0" required>
                        @error('base_salary')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <x-select
                            name="salary_grid_id"
                            label="Grille salariale"
                            :options="$salaryGrids"
                            option-value="id"
                            option-label="name"
                            :value="old('salary_grid_id')"
                            placeholder="— Aucune —"
                        />
                    </div>
                    <div class="col-md-6" id="end-date-field" style="display:none">
                        <label class="form-label small fw-medium">
                            Date de fin
                            <small class="text-muted">(CDD / Stage)</small>
                        </label>
                        <input type="date" name="contract_end_date"
                               value="{{ old('contract_end_date') }}"
                               class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── SIDEBAR DROITE ──────────────────────────────────── --}}
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-shield-lock me-2"></i>Accès applicatif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <x-select
                        name="role"
                        label="Rôle"
                        :options="$roles->pluck('name')->mapWithKeys(fn($n) => [$n => ucfirst($n)])->all()"
                        :value="old('role')"
                        required
                    />
                    <div class="form-text">
                        Détermine les accès dans l'application.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">
                        Mot de passe <span class="text-danger">*</span>
                    </label>
                    <input type="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required minlength="8">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="form-label small fw-medium">
                        Confirmer le mot de passe <span class="text-danger">*</span>
                    </label>
                    <input type="password" name="password_confirmation"
                           class="form-control" required>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Enregistrer l'employé
                </button>
                <a href="{{ route('employees.index') }}"
                   class="btn btn-outline-secondary">
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
// ── Changement de poste : charger N+1 + remplir département ──
function onPosteChange(select) {
    const opt       = select.options[select.selectedIndex];
    const posteId   = select.value;
    const level     = opt.dataset.level;
    const dept      = opt.dataset.dept;
    const canBeN1   = opt.dataset.can_n1 === '1';
    const supSel    = document.getElementById('supervisor-select');
    const n1Info    = document.getElementById('n1-info');
    const n1Hint    = document.getElementById('n1-hint');

    // Pré-remplir le département si lié au poste
    if (dept && dept !== 'null') {
        document.getElementById('dept-field').value = dept;
    }

    if (!posteId) {
        supSel.innerHTML = '<option value="">— Sélectionnez d\'abord un poste —</option>';
        return;
    }

    n1Hint.textContent  = `(postes de niveau > ${level})`;
    supSel.innerHTML    = '<option value="">Chargement...</option>';
    supSel.disabled     = true;

    fetch(`/api/postes/${posteId}/n1`)
        .then(r => r.json())
        .then(data => {
            supSel.disabled = false;

            if (data.length === 0) {
                supSel.innerHTML = '<option value="">Aucun N+1 disponible</option>';
                n1Info.innerHTML =
                    '<span class="text-warning">'
                    + '<i class="bi bi-exclamation-triangle me-1"></i>'
                    + 'Aucun employé avec un poste de niveau supérieur '
                    + 'marqué comme N+1.</span>';
                return;
            }

            let options = '<option value="">— Aucun (optionnel) —</option>';
            data.forEach(emp => {
                options += `<option value="${emp.id}">
                    ${emp.name} — ${emp.poste} (Niv. ${emp.level})
                    ${emp.department ? '· ' + emp.department : ''}
                </option>`;
            });
            supSel.innerHTML = options;
            n1Info.innerHTML =
                `<span class="text-success">`
                + `<i class="bi bi-check-circle me-1"></i>`
                + `${data.length} N+1 disponible(s) pour ce niveau.</span>`;
        })
        .catch(() => {
            supSel.disabled  = false;
            supSel.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}

// ── Afficher/masquer la date de fin selon le type contrat ─────
function toggleEndDate() {
    const type  = document.getElementById('contract-type').value;
    const field = document.getElementById('end-date-field');
    const input = field.querySelector('input');
    const show  = ['cdd', 'internship', 'consulting'].includes(type);
    field.style.display = show ? 'block' : 'none';
    input.required      = show;
}

// ── Init au chargement ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    toggleEndDate();
    // Si un poste est déjà sélectionné (old input), charger les N+1
    const sel = document.getElementById('poste-select');
    if (sel && sel.value) onPosteChange(sel);
});
</script>
@endpush
