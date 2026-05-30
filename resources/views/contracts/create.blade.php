@extends('layouts.app')
@section('page-title', 'Nouveau contrat')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Contrats', 'url' => route('contracts.index')],
    ['label' => 'Nouveau contrat'],
]" />
@endsection

@section('header-actions')
    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-9">
            <form method="POST" action="{{ route('contracts.store') }}">
                @csrf

                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person me-2"></i>Employé</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-medium">Employé concerné <span class="text-danger">*</span></label>
                                <select name="employee_id"
                                        class="form-select @error('employee_id') is-invalid @enderror"
                                        required id="employee-select" onchange="fillFromEmployee(this)">
                                    <option value="">— Sélectionner un employé —</option>
                                    @foreach($employees as $emp)
                                        <option value="{{ $emp->id }}"
                                                data-position="{{ $emp->position }}"
                                                data-department="{{ $emp->department }}"
                                            {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                            {{ $emp->full_name }} ({{ $emp->matricule }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i>Détails du contrat</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Type de contrat <span class="text-danger">*</span></label>
                                <select name="type" class="form-select @error('type') is-invalid @enderror"
                                        required id="contract-type" onchange="toggleEndDate()">
                                    <option value="cdi"        {{ old('type') === 'cdi'        ? 'selected' : '' }}>CDI</option>
                                    <option value="cdd"        {{ old('type') === 'cdd'        ? 'selected' : '' }}>CDD</option>
                                    <option value="internship" {{ old('type') === 'internship' ? 'selected' : '' }}>Stage</option>
                                    <option value="consulting" {{ old('type') === 'consulting' ? 'selected' : '' }}>Consulting</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Date de début <span class="text-danger">*</span></label>
                                <input type="date" name="start_date"
                                       value="{{ old('start_date', date('Y-m-d')) }}"
                                       class="form-control @error('start_date') is-invalid @enderror" required>
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4" id="end-date-wrap">
                                <label class="form-label small fw-medium">
                                    Date de fin <small class="text-muted">(CDD / Stage)</small>
                                </label>
                                <input type="date" name="end_date"
                                       value="{{ old('end_date') }}"
                                       class="form-control @error('end_date') is-invalid @enderror">
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Fin période d'essai</label>
                                <input type="date" name="trial_end_date"
                                       value="{{ old('trial_end_date') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Poste <span class="text-danger">*</span></label>
                                <input type="text" name="position" id="position-field"
                                       value="{{ old('position') }}"
                                       class="form-control @error('position') is-invalid @enderror" required>
                                @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Département <span class="text-danger">*</span></label>
                                <input type="text" name="department" id="department-field"
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
                                @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-cash me-2"></i>Rémunération</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-medium">Salaire de base (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="base_salary"
                                       value="{{ old('base_salary') }}"
                                       class="form-control @error('base_salary') is-invalid @enderror"
                                       min="0" required>
                                @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-medium">Grille salariale</label>
                                <select name="salary_grid_id" class="form-select" id="grid-select" onchange="fillFromGrid(this)">
                                    <option value="">— Aucune —</option>
                                    @foreach($salaryGrids as $grid)
                                        <option value="{{ $grid->id }}"
                                                data-salary="{{ $grid->base_salary }}"
                                            {{ old('salary_grid_id') == $grid->id ? 'selected' : '' }}>
                                            {{ $grid->name }}
                                            ({{ number_format($grid->min_salary, 0, ',', ' ') }}
                                            – {{ number_format($grid->max_salary, 0, ',', ' ') }} FCFA)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Sélectionnez une grille pour pré-remplir le salaire.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Notes internes</label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Clauses particulières, observations...">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Créer le contrat
                    </button>
                    <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">Annuler</a>
                </div>

            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Pré-remplir poste & département depuis l'employé sélectionné
        function fillFromEmployee(sel) {
            const opt = sel.options[sel.selectedIndex];
            if (opt.value) {
                document.getElementById('position-field').value  = opt.dataset.position  || '';
                document.getElementById('department-field').value = opt.dataset.department || '';
            }
        }

        // Pré-remplir le salaire depuis la grille
        function fillFromGrid(sel) {
            const opt = sel.options[sel.selectedIndex];
            if (opt.value && opt.dataset.salary) {
                document.querySelector('[name=base_salary]').value = opt.dataset.salary;
            }
        }

        // Afficher/masquer la date de fin selon le type
        function toggleEndDate() {
            const type  = document.getElementById('contract-type').value;
            const wrap  = document.getElementById('end-date-wrap');
            const input = wrap.querySelector('input');
            const show  = ['cdd', 'internship', 'consulting'].includes(type);
            wrap.style.display = show ? 'block' : 'none';
            input.required = show;
        }

        // Init au chargement
        toggleEndDate();
    </script>
@endpush
