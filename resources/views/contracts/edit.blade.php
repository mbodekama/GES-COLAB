@extends('layouts.app')
@section('page-title', 'Modifier — '.$contract->contract_number)

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Contrats', 'url' => route('contracts.index')],
    ['label' => $contract->contract_number, 'url' => route('contracts.show', $contract)],
    ['label' => 'Modifier'],
]" />
@endsection

@section('header-actions')
    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <form method="POST" action="{{ route('contracts.update', $contract) }}">
            @csrf
            @method('PUT')

            {{-- Employé (lecture seule) --}}
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2"
                     style="background:#E6F1FB; border-left:4px solid #185FA5;">
                    <span style="color:#185FA5"><i class="bi bi-person me-1"></i>Employé</span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-initials"
                             style="width:44px;height:44px;font-size:16px;background:#E6F1FB;color:#185FA5;flex-shrink:0">
                            {{ $contract->employee->initials }}
                        </div>
                        <div>
                            <div class="fw-semibold">{{ $contract->employee->full_name }}</div>
                            <div class="small text-muted">
                                {{ $contract->employee->matricule }} — {{ $contract->employee->department }}
                            </div>
                        </div>
                        <span class="badge bg-light text-muted border ms-auto" style="font-size:12px">
                            {{ $contract->contract_number }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Détails du contrat --}}
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2"
                     style="background:#E6F1FB; border-left:4px solid #185FA5;">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                          style="width:22px;height:22px;background:#185FA5;color:#fff;font-size:11px;font-weight:700">1</span>
                    <span style="color:#185FA5"><i class="bi bi-file-earmark-text me-1"></i>Détails du contrat</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-select
                                name="type"
                                label="Type de contrat"
                                :options="['cdi' => 'CDI', 'cdd' => 'CDD', 'internship' => 'Stage', 'consulting' => 'Consulting']"
                                :value="old('type', $contract->type)"
                                id="contract-type"
                                onchange="toggleEndDate()"
                                required
                            />
                        </div>
                        <div class="col-md-4">
                            <x-select
                                name="status"
                                label="Statut"
                                :options="['active' => 'En cours', 'expired' => 'Expiré', 'terminated' => 'Résilié', 'renewed' => 'Renouvelé']"
                                :value="old('status', $contract->status)"
                                id="contract-status"
                                onchange="toggleStatusDates()"
                                required
                            />
                        </div>

                        {{-- Date résiliation (visible si statut = terminated) --}}
                        <div class="col-md-4" id="wrap-date-resiliation" style="display:none">
                            <label class="form-label small fw-medium">
                                <i class="bi bi-calendar-x me-1 text-danger"></i>Date de résiliation
                            </label>
                            <input type="date" name="date_resiliation"
                                   value="{{ old('date_resiliation', $contract->date_resiliation?->format('Y-m-d')) }}"
                                   class="form-control @error('date_resiliation') is-invalid @enderror">
                            @error('date_resiliation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Date renouvellement (visible si statut = renewed) --}}
                        <div class="col-md-4" id="wrap-date-renouvellement" style="display:none">
                            <label class="form-label small fw-medium">
                                <i class="bi bi-arrow-clockwise me-1 text-warning"></i>Date de renouvellement
                            </label>
                            <input type="date" name="date_renouvellement"
                                   value="{{ old('date_renouvellement', $contract->date_renouvellement?->format('Y-m-d')) }}"
                                   class="form-control @error('date_renouvellement') is-invalid @enderror">
                            @error('date_renouvellement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium">Date de début <span class="text-danger">*</span></label>
                            <input type="date" name="start_date"
                                   value="{{ old('start_date', $contract->start_date->format('Y-m-d')) }}"
                                   class="form-control @error('start_date') is-invalid @enderror" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="end-date-wrap">
                            <label class="form-label small fw-medium">
                                Date de fin <small class="text-muted">(CDD / Stage)</small>
                            </label>
                            <input type="date" name="end_date"
                                   value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}"
                                   class="form-control @error('end_date') is-invalid @enderror">
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium">Poste <span class="text-danger">*</span></label>
                            <input type="text" name="position"
                                   value="{{ old('position', $contract->position) }}"
                                   class="form-control @error('position') is-invalid @enderror" required>
                            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-medium">Département <span class="text-danger">*</span></label>
                            <input type="text" name="department"
                                   value="{{ old('department', $contract->department) }}"
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

            {{-- Rémunération --}}
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2"
                     style="background:#E6F1FB; border-left:4px solid #185FA5;">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                          style="width:22px;height:22px;background:#185FA5;color:#fff;font-size:11px;font-weight:700">2</span>
                    <span style="color:#185FA5"><i class="bi bi-cash me-1"></i>Rémunération</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-medium">Salaire de base (FCFA) <span class="text-danger">*</span></label>
                            <input type="number" name="base_salary"
                                   value="{{ old('base_salary', $contract->base_salary) }}"
                                   class="form-control @error('base_salary') is-invalid @enderror"
                                   min="0" required>
                            @error('base_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-7">
                            <x-select
                                name="salary_grid_id"
                                label="Grille salariale"
                                :options="$salaryGrids->mapWithKeys(fn($g) => [
                                    $g->id => $g->name.' ('.number_format($g->min_salary, 0, ',', ' ').' – '.number_format($g->max_salary, 0, ',', ' ').' FCFA)'
                                ])->all()"
                                :value="old('salary_grid_id', $contract->salary_grid_id)"
                                id="grid-select"
                                onchange="fillFromGrid(this)"
                                placeholder="— Aucune —"
                            />
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-medium">Notes internes</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      placeholder="Clauses particulières, observations...">{{ old('notes', $contract->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Enregistrer les modifications
                </button>
                <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary">Annuler</a>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const salaryGridMap = {!! $salaryGrids->mapWithKeys(fn($g) => [$g->id => (float) $g->base_salary]) !!};

    function fillFromGrid(sel) {
        const salary = salaryGridMap[sel.value];
        if (salary) document.querySelector('[name=base_salary]').value = salary;
    }

    function toggleEndDate() {
        const type  = document.getElementById('contract-type').value;
        const wrap  = document.getElementById('end-date-wrap');
        const input = wrap.querySelector('input');
        const show  = ['cdd', 'internship', 'consulting'].includes(type);
        wrap.style.display = show ? 'block' : 'none';
        input.required = show;
    }

    function toggleStatusDates() {
        const status = document.getElementById('contract-status').value;
        document.getElementById('wrap-date-resiliation').style.display =
            status === 'terminated' ? 'block' : 'none';
        document.getElementById('wrap-date-renouvellement').style.display =
            status === 'renewed' ? 'block' : 'none';
    }

    toggleEndDate();
    toggleStatusDates();
</script>
@endpush
