@extends('layouts.app')
@section('page-title', 'Grilles salariales')

@section('header-actions')
    @can('gérer grilles salariales')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle me-1"></i> Nouvelle grille
        </button>
    @endcan
@endsection

@section('content')

    <div class="card">
        <div class="card-header">
            <span>Grilles salariales <span class="text-muted fw-normal">({{ $grids->total() }})</span></span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Niveau</th>
                    <th>Salaire min (FCFA)</th>
                    <th>Salaire max (FCFA)</th>
                    <th>Salaire de base</th>
                    <th>Transport</th>
                    <th>Logement</th>
                    <th>Statut</th>
                    <th class="text-center">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($grids as $grid)
                    <tr>
                        <td class="fw-medium">{{ $grid->name }}</td>
                        <td>
                            <span class="badge bg-secondary badge-status">{{ $grid->level_label }}</span>
                        </td>
                        <td>{{ number_format($grid->min_salary, 0, ',', ' ') }}</td>
                        <td>{{ number_format($grid->max_salary, 0, ',', ' ') }}</td>
                        <td class="fw-medium text-success">{{ number_format($grid->base_salary, 0, ',', ' ') }}</td>
                        <td>{{ number_format($grid->transport_allowance, 0, ',', ' ') }}</td>
                        <td>{{ number_format($grid->housing_allowance, 0, ',', ' ') }}</td>
                        <td>
                            @if($grid->is_active)
                                <span class="badge bg-success badge-status">Active</span>
                            @else
                                <span class="badge bg-secondary badge-status">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                @can('gérer grilles salariales')
                                    <button class="btn btn-outline-primary"
                                            title="Modifier"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            data-id="{{ $grid->id }}"
                                            data-name="{{ $grid->name }}"
                                            data-level="{{ $grid->level }}"
                                            data-min="{{ $grid->min_salary }}"
                                            data-max="{{ $grid->max_salary }}"
                                            data-base="{{ $grid->base_salary }}"
                                            data-transport="{{ $grid->transport_allowance }}"
                                            data-housing="{{ $grid->housing_allowance }}"
                                            data-meal="{{ $grid->meal_allowance }}"
                                            data-active="{{ $grid->is_active ? 1 : 0 }}"
                                            data-description="{{ $grid->description }}"
                                            data-url="{{ route('salary-grids.update', $grid) }}"
                                            onclick="fillEditModal(this)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('salary-grids.destroy', $grid) }}" class="d-inline"
                                          onsubmit="return confirm('Supprimer cette grille ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-table fs-1 d-block mb-2 opacity-25"></i>
                            Aucune grille salariale définie
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center py-2">
            <small class="text-muted">
                {{ $grids->firstItem() ?? 0 }}–{{ $grids->lastItem() ?? 0 }} sur {{ $grids->total() }}
            </small>
            {{ $grids->links() }}
        </div>
    </div>

    {{-- ── MODAL CRÉATION ──────────────────────────────────────── --}}
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="{{ route('salary-grids.store') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Nouvelle grille salariale</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-medium">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control"
                                       placeholder="ex: G3 — Agents de maîtrise" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Niveau <span class="text-danger">*</span></label>
                                <select name="level" class="form-select" required>
                                    <option value="1">Niveau 1 — Employés</option>
                                    <option value="2">Niveau 2 — Employés qualifiés</option>
                                    <option value="3">Niveau 3 — Agents de maîtrise</option>
                                    <option value="4">Niveau 4 — Cadres</option>
                                    <option value="5">Niveau 5 — Cadres supérieurs</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Salaire minimum (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="min_salary" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Salaire maximum (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="max_salary" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Salaire de base (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="base_salary" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Indemnité transport (FCFA)</label>
                                <input type="number" name="transport_allowance" class="form-control" min="0" value="30000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Indemnité logement (FCFA)</label>
                                <input type="number" name="housing_allowance" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Indemnité repas (FCFA)</label>
                                <input type="number" name="meal_allowance" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Statut</label>
                                <select name="is_active" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Description</label>
                                <textarea name="description" class="form-control" rows="2"
                                          placeholder="Description optionnelle..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Créer la grille
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── MODAL MODIFICATION ──────────────────────────────────── --}}
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" id="edit-form" action="#">
                @csrf @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Modifier la grille</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-medium">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit-name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Niveau <span class="text-danger">*</span></label>
                                <select name="level" id="edit-level" class="form-select" required>
                                    <option value="1">Niveau 1 — Employés</option>
                                    <option value="2">Niveau 2 — Employés qualifiés</option>
                                    <option value="3">Niveau 3 — Agents de maîtrise</option>
                                    <option value="4">Niveau 4 — Cadres</option>
                                    <option value="5">Niveau 5 — Cadres supérieurs</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Salaire minimum (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="min_salary" id="edit-min" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-medium">Salaire maximum (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="max_salary" id="edit-max" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Salaire de base (FCFA) <span class="text-danger">*</span></label>
                                <input type="number" name="base_salary" id="edit-base" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Indemnité transport (FCFA)</label>
                                <input type="number" name="transport_allowance" id="edit-transport" class="form-control" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Indemnité logement (FCFA)</label>
                                <input type="number" name="housing_allowance" id="edit-housing" class="form-control" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Indemnité repas (FCFA)</label>
                                <input type="number" name="meal_allowance" id="edit-meal" class="form-control" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Statut</label>
                                <select name="is_active" id="edit-active" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Description</label>
                                <textarea name="description" id="edit-description"
                                          class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Enregistrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function fillEditModal(btn) {
            document.getElementById('edit-form').action      = btn.dataset.url;
            document.getElementById('edit-name').value       = btn.dataset.name;
            document.getElementById('edit-level').value      = btn.dataset.level;
            document.getElementById('edit-min').value        = btn.dataset.min;
            document.getElementById('edit-max').value        = btn.dataset.max;
            document.getElementById('edit-base').value       = btn.dataset.base;
            document.getElementById('edit-transport').value  = btn.dataset.transport;
            document.getElementById('edit-housing').value    = btn.dataset.housing;
            document.getElementById('edit-meal').value       = btn.dataset.meal;
            document.getElementById('edit-active').value     = btn.dataset.active;
            document.getElementById('edit-description').value = btn.dataset.description;
        }
    </script>
@endpush
