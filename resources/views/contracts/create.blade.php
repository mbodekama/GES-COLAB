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
                    <div class="card-header d-flex align-items-center gap-2"
                         style="background:#E6F1FB; border-left:4px solid #185FA5;">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                              style="width:22px;height:22px;background:#185FA5;color:#fff;font-size:11px;font-weight:700">1</span>
                        <span style="color:#185FA5"><i class="bi bi-person me-1"></i>Employé</span>
                    </div>
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
                    <div class="card-header d-flex align-items-center gap-2"
                         style="background:#E6F1FB; border-left:4px solid #185FA5;">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                              style="width:22px;height:22px;background:#185FA5;color:#fff;font-size:11px;font-weight:700">2</span>
                        <span style="color:#185FA5"><i class="bi bi-file-earmark-text me-1"></i>Détails du contrat</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <x-select
                                    name="type"
                                    label="Type de contrat"
                                    :options="['cdi' => 'CDI', 'cdd' => 'CDD', 'internship' => 'Stage', 'consulting' => 'Consulting']"
                                    :value="old('type', 'cdi')"
                                    id="contract-type"
                                    onchange="toggleEndDate()"
                                    required
                                />
                            </div>
                            <div class="col-md-4">
                                <x-date
                                    name="start_date"
                                    label="Date de début"
                                    :value="date('Y-m-d')"
                                    required
                                />
                            </div>
                            <div class="col-md-4" id="end-date-wrap">
                                <x-date
                                    name="end_date"
                                    label="Date de fin"
                                />
                            </div>
                            <div class="col-md-4">
                                <x-date
                                    name="trial_end_date"
                                    label="Fin période d'essai"
                                />
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
                    <div class="card-header d-flex align-items-center gap-2"
                         style="background:#E6F1FB; border-left:4px solid #185FA5;">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                              style="width:22px;height:22px;background:#185FA5;color:#fff;font-size:11px;font-weight:700">3</span>
                        <span style="color:#185FA5"><i class="bi bi-cash me-1"></i>Rémunération</span>
                    </div>
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
