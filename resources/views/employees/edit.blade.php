@extends('layouts.app')
@section('page-title', 'Modifier — '.$employee->full_name)

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Employés', 'url' => route('employees.index')],
    ['label' => $employee->full_name, 'url' => route('employees.show', $employee)],
    ['label' => 'Modifier'],
]" />
@endsection

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
                                       value="{{ old('first_name', $employee->first_name) }}"
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
                                       value="{{ old('last_name', $employee->last_name) }}"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       required>
                                @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="email"
                                       value="{{ old('email', $employee->email) }}"
                                       class="form-control @error('email') is-invalid @enderror"
                                       required>
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Téléphone</label>
                                <input type="text" name="phone"
                                       value="{{ old('phone', $employee->phone) }}"
                                       class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Date de naissance</label>
                                <input type="date" name="birth_date"
                                       value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}"
                                       class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Lieu de naissance</label>
                                <input type="text" name="birth_place"
                                       value="{{ old('birth_place', $employee->birth_place) }}"
                                       class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Nationalité</label>
                                <input type="text" name="nationality"
                                       value="{{ old('nationality', $employee->nationality) }}"
                                       class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-medium">Situation familiale</label>
                                <select name="marital_status" class="form-select">
                                    @foreach([
                                        'single'   => 'Célibataire',
                                        'married'  => 'Marié(e)',
                                        'divorced' => 'Divorcé(e)',
                                        'widowed'  => 'Veuf/Veuve',
                                    ] as $val => $lbl)
                                        <option value="{{ $val }}"
                                            {{ old('marital_status', $employee->marital_status) === $val ? 'selected' : '' }}>
                                            {{ $lbl }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-medium">Nb. enfants</label>
                                <input type="number" name="children_count"
                                       value="{{ old('children_count', $employee->children_count) }}"
                                       class="form-control" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">N° CNPS</label>
                                <input type="text" name="cnps_number"
                                       value="{{ old('cnps_number', $employee->cnps_number) }}"
                                       class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Adresse</label>
                                <textarea name="address" class="form-control"
                                          rows="2">{{ old('address', $employee->address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── INFOS PROFESSIONNELLES ──────────────────────── --}}
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-briefcase me-2"></i>Informations professionnelles
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- POSTE --}}
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
                                            {{ old('poste_id', $employee->poste_id) == $poste->id ? 'selected' : '' }}>
                                            {{ $poste->title }}
                                            (Niv. {{ $poste->level }}
                                            — {{ $poste->department ?? 'Tous dépts' }})
                                            {{ $poste->can_be_n1 ? '⭐' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">⭐ = poste pouvant être N+1</div>
                                @error('poste_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-medium">
                                    Département <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="department" id="dept-field"
                                       value="{{ old('department', $employee->department) }}"
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

                            <div class="col-md-4">
                                <label class="form-label small fw-medium">
                                    Date d'embauche <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="hire_date"
                                       value="{{ old('hire_date', $employee->hire_date->format('Y-m-d')) }}"
                                       class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-medium">
                                    Solde congés (jours)
                                </label>
                                <input type="number" name="leave_balance"
                                       value="{{ old('leave_balance', $employee->leave_balance) }}"
                                       class="form-control" min="0">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Statut</label>
                                <select name="status" class="form-select">
                                    @foreach([
                                        'active'     => 'Actif',
                                        'on_leave'   => 'En congé',
                                        'suspended'  => 'Suspendu',
                                        'terminated' => 'Parti',
                                    ] as $val => $lbl)
                                        <option value="{{ $val }}"
                                            {{ old('status', $employee->status) === $val ? 'selected' : '' }}>
                                            {{ $lbl }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- N+1 : chargé dynamiquement + valeur actuelle pré-sélectionnée --}}
                            <div class="col-12">
                                <label class="form-label small fw-medium">
                                    Supérieur hiérarchique (N+1)
                                    <span class="text-muted fw-normal" id="n1-hint"></span>
                                </label>

                                {{-- Affichage du N+1 actuel --}}
                                @if($employee->supervisor)
                                    <div class="alert alert-info py-2 small mb-2">
                                        <i class="bi bi-person-check me-1"></i>
                                        N+1 actuel :
                                        <strong>{{ $employee->supervisor->full_name }}</strong>
                                        — {{ $employee->supervisor->poste?->title ?? $employee->supervisor->position }}
                                        (Niv. {{ $employee->supervisor->poste?->level ?? '?' }})
                                    </div>
                                @endif

                                <select name="supervisor_id" id="supervisor-select"
                                        class="form-select">
                                    <option value="">Chargement en cours...</option>
                                </select>
                                <div class="form-text" id="n1-info">
                                    Le N+1 est filtré selon le niveau du poste sélectionné.
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- ── SIDEBAR DROITE ──────────────────────────────────── --}}
            <div class="col-md-4">

                {{-- RÔLE --}}
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="bi bi-shield-lock me-2"></i>Rôle applicatif
                    </div>
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
                        <div class="form-text">
                            Rôle actuel :
                            <strong>{{ $employee->user?->primary_role_label ?? '—' }}</strong>
                        </div>
                    </div>
                </div>

                {{-- INFOS POSTE ACTUEL --}}
                @if($employee->poste)
                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="bi bi-diagram-3 me-2"></i>Poste actuel
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-dark">{{ $employee->poste->code }}</span>
                                <span class="fw-medium">{{ $employee->poste->title }}</span>
                            </div>
                            {!! $employee->poste->level_badge !!}
                            @if($employee->poste->can_be_n1)
                                <div class="mt-2 small text-primary">
                                    <i class="bi bi-person-check-fill me-1"></i>
                                    Ce poste peut être N+1
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- SUBALTERNES --}}
                @if($employee->subordinates->count())
                    <div class="card mb-3">
                        <div class="card-header">
                            <i class="bi bi-people me-2"></i>
                            Subalternes ({{ $employee->subordinates->count() }})
                        </div>
                        <div class="card-body p-2">
                            @foreach($employee->subordinates as $sub)
                                <a href="{{ route('employees.show', $sub) }}"
                                   class="d-flex align-items-center gap-2 p-2 rounded text-decoration-none
                          text-dark mb-1"
                                   style="background:#f8f9fa">
                                    <div class="avatar-initials"
                                         style="width:26px;height:26px;font-size:10px;
                                background:#E6F1FB;color:#185FA5">
                                        {{ $sub->initials }}
                                    </div>
                                    <div>
                                        <div class="small fw-medium">{{ $sub->full_name }}</div>
                                        <div style="font-size:10px;color:#6c757d">
                                            {{ $sub->poste?->title ?? $sub->position }}
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ACTIONS --}}
                <div class="card">
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>
                            Enregistrer les modifications
                        </button>
                        <a href="{{ route('employees.show', $employee) }}"
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
        // ── ID et supervisor actuel de l'employé ──────────────────────
        const currentEmployeeId  = {{ $employee->id }};
        const currentSupervisorId = {{ $employee->supervisor_id ?? 'null' }};

        // ── Changement de poste : recharger les N+1 ──────────────────
        function onPosteChange(select) {
            const opt     = select.options[select.selectedIndex];
            const posteId = select.value;
            const level   = opt.dataset.level;
            const dept    = opt.dataset.dept;
            const supSel  = document.getElementById('supervisor-select');
            const n1Info  = document.getElementById('n1-info');
            const n1Hint  = document.getElementById('n1-hint');

            // Pré-remplir le département si lié au poste
            if (dept && dept !== 'null' && dept !== 'undefined') {
                document.getElementById('dept-field').value = dept;
            }

            if (!posteId) {
                supSel.innerHTML = '<option value="">— Sélectionnez d\'abord un poste —</option>';
                return;
            }

            n1Hint.textContent = `(postes de niveau > ${level})`;
            supSel.innerHTML   = '<option value="">Chargement...</option>';
            supSel.disabled    = true;

            fetch(`/api/postes/${posteId}/n1`)
                .then(r => r.json())
                .then(data => {
                    supSel.disabled = false;

                    // Exclure l'employé lui-même de la liste
                    data = data.filter(emp => emp.id !== currentEmployeeId);

                    if (data.length === 0) {
                        supSel.innerHTML =
                            '<option value="">Aucun N+1 disponible pour ce niveau</option>';
                        n1Info.innerHTML =
                            '<span class="text-warning">'
                            + '<i class="bi bi-exclamation-triangle me-1"></i>'
                            + 'Aucun employé avec un poste de niveau supérieur marqué N+1.'
                            + '</span>';
                        return;
                    }

                    let options = '<option value="">— Aucun (optionnel) —</option>';
                    data.forEach(emp => {
                        const selected = emp.id === currentSupervisorId ? 'selected' : '';
                        options += `<option value="${emp.id}" ${selected}>
                    ${emp.name} — ${emp.poste}
                    (Niv. ${emp.level})
                    ${emp.department ? '· ' + emp.department : ''}
                </option>`;
                    });
                    supSel.innerHTML = options;

                    n1Info.innerHTML =
                        `<span class="text-success">`
                        + `<i class="bi bi-check-circle me-1"></i>`
                        + `${data.length} N+1 disponible(s) pour ce niveau.`
                        + `</span>`;
                })
                .catch(() => {
                    supSel.disabled  = false;
                    supSel.innerHTML =
                        '<option value="">Erreur de chargement</option>';
                });
        }

        // ── Charger les N+1 au chargement de la page ──────────────────
        document.addEventListener('DOMContentLoaded', function () {
            const sel = document.getElementById('poste-select');
            if (sel && sel.value) {
                onPosteChange(sel);
            } else {
                // Pas de poste sélectionné : vider le select
                document.getElementById('supervisor-select').innerHTML =
                    '<option value="">— Sélectionnez d\'abord un poste —</option>';
            }
        });
    </script>
@endpush
