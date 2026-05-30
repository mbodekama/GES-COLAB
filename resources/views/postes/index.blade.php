@extends('layouts.app')
@section('page-title', 'Postes & Hiérarchie')

@section('header-actions')
    @role('superadmin|admin|rh')
    <button class="btn btn-primary btn-sm"
            data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-1"></i> Nouveau poste
    </button>
    @endrole
@endsection

@section('content')

{{-- PYRAMIDE HIÉRARCHIQUE --}}
<div class="card mb-4">
    <div class="card-header">
        <span><i class="bi bi-diagram-3 me-2"></i>Pyramide hiérarchique</span>
        <small class="text-muted fw-normal">
            <i class="bi bi-person-check-fill text-primary me-1"></i>
            Postes en bleu = peuvent être N+1
        </small>
    </div>
    <div class="card-body py-3">
        @php
            $grouped = $postes->getCollection()
                              ->groupBy('level')
                              ->sortKeysDesc();
        @endphp

        @foreach($grouped as $level => $posts)
        <div class="d-flex align-items-center gap-3 mb-2">
            {{-- Étiquette niveau --}}
            <div class="text-end flex-shrink-0" style="width:90px">
                <span class="badge bg-light text-dark border" style="font-size:11px">
                    Niv. {{ $level }}
                </span>
            </div>

            {{-- Séparateur --}}
            <div style="width:1px;background:#dee2e6;align-self:stretch"></div>

            {{-- Postes de ce niveau --}}
            <div class="d-flex flex-wrap gap-2">
                @foreach($posts as $post)
                <div class="d-flex align-items-center gap-1 px-3 py-1 rounded-pill border"
                     style="font-size:12px;
                            background:{{ $post->can_be_n1 ? '#E6F1FB' : '#f8f9fa' }};
                            border-color:{{ $post->can_be_n1 ? '#185FA5' : '#dee2e6' }} !important;
                            opacity:{{ $post->is_active ? 1 : 0.45 }}">
                    @if($post->can_be_n1)
                        <i class="bi bi-person-check-fill"
                           style="color:#185FA5;font-size:11px"></i>
                    @endif
                    <span class="{{ $post->can_be_n1 ? 'fw-semibold' : '' }}"
                          style="{{ $post->can_be_n1 ? 'color:#185FA5' : '' }}">
                        {{ $post->title }}
                    </span>
                    <span class="badge bg-secondary ms-1" style="font-size:9px">
                        {{ $post->employees_count }}
                    </span>
                    @if(!$post->is_active)
                        <span class="badge bg-danger ms-1" style="font-size:9px">Inactif</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- TABLE COMPLÈTE --}}
<div class="card">
    <div class="card-header">
        <span>
            Tous les postes
            <span class="text-muted fw-normal">({{ $postes->total() }})</span>
        </span>
    </div>

    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Code</th>
                <x-sort-th column="title" label="Titre du poste" />
                <x-sort-th column="department" label="Département" />
                <x-sort-th column="level" label="Niveau" />
                <th class="text-center">Peut être N+1</th>
                <th class="text-center">Employés</th>
                <th>Statut</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($postes as $poste)
        <tr>
            <td>
                <span class="badge bg-dark" style="font-size:11px;letter-spacing:.03em">
                    {{ $poste->code }}
                </span>
            </td>
            <td>
                <div class="fw-medium">{{ $poste->title }}</div>
                @if($poste->description)
                <div class="text-muted small text-truncate" style="max-width:200px">
                    {{ $poste->description }}
                </div>
                @endif
            </td>
            <td class="small">{{ $poste->department ?? '—' }}</td>
            <td>{!! $poste->level_badge !!}</td>
            <td class="text-center">
                @if($poste->can_be_n1)
                    <i class="bi bi-check-circle-fill text-success"
                       style="font-size:18px" title="Oui"></i>
                @else
                    <i class="bi bi-dash-circle text-muted"
                       style="font-size:18px;opacity:.3" title="Non"></i>
                @endif
            </td>
            <td class="text-center">
                <span class="badge {{ $poste->employees_count > 0 ? 'bg-primary' : 'bg-light text-dark border' }}">
                    {{ $poste->employees_count }}
                </span>
            </td>
            <td>
                @if($poste->is_active)
                    <span class="badge bg-success badge-status">Actif</span>
                @else
                    <span class="badge bg-secondary badge-status">Inactif</span>
                @endif
            </td>
            <td class="text-center">
                <div class="btn-group btn-group-md d-flex justify-content-start gap-2">
                    @role('superadmin|admin|rh')
                    <div>
                        <button class="btn btn-outline-primary"
                                title="Modifier"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="{{ $poste->id }}"
                                data-title="{{ $poste->title }}"
                                data-code="{{ $poste->code }}"
                                data-department="{{ $poste->department }}"
                                data-level="{{ $poste->level }}"
                                data-can_be_n1="{{ $poste->can_be_n1 ? 1 : 0 }}"
                                data-description="{{ $poste->description }}"
                                data-is_active="{{ $poste->is_active ? 1 : 0 }}"
                                data-url="{{ route('postes.update', $poste) }}"
                                onclick="fillEdit(this)">
                            <i class="bi bi-pencil"></i> &nbsp; Modifier
                        </button>
                    </div>
                    @if($poste->employees_count === 0)
                    <div>
                        <form method="POST"
                              action="{{ route('postes.destroy', $poste) }}"
                              class="d-inline"
                              onsubmit="return confirm('Supprimer le poste « {{ $poste->title }} » ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger" title="Supprimer">
                                <i class="bi bi-trash"></i> &nbsp; Supprimer
                            </button>
                        </form>
                    </div>
                    @endif
                    @endrole
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-diagram-3 fs-1 d-block mb-2 opacity-25"></i>
                Aucun poste défini.<br>
                <small>Créez des postes pour hiérarchiser vos employés.</small>
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center py-2">
        <small class="text-muted">
            {{ $postes->firstItem() ?? 0 }}–{{ $postes->lastItem() ?? 0 }}
            sur {{ $postes->total() }}
        </small>
        {{ $postes->links() }}
    </div>
</div>

{{-- ── MODAL CRÉATION ──────────────────────────────────────── --}}
<div class="modal fade" id="createModal" tabindex="-1"
     aria-labelledby="createModalTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('postes.store') }}">
        @csrf
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalTitle">
                    <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Nouveau poste
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-medium">
                            Intitulé du poste <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" class="form-control"
                               placeholder="ex: Chef de Service Commercial" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">
                            Code <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="code" class="form-control"
                               placeholder="ex: CHEF_SVC"
                               style="text-transform:uppercase" required>
                        <div class="form-text">Unique, sans espaces ni accents</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Département</label>
                        <input type="text" name="department" class="form-control"
                               list="dept-list-pos"
                               placeholder="ex: Commercial">
                        <datalist id="dept-list-pos">
                            <option>Direction</option>
                            <option>Ressources Humaines</option>
                            <option>Finance & Comptabilité</option>
                            <option>Informatique</option>
                            <option>Commercial</option>
                            <option>Logistique</option>
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <x-select
                            name="level"
                            label="Niveau hiérarchique"
                            :options="collect(range(10,1))->mapWithKeys(fn($i) => [$i => 'Niveau '.$i.' — '.match(true) { $i>=9=>'Direction', $i>=7=>'Management supérieur', $i>=5=>'Management intermédiaire', $i>=3=>'Supervision', default=>'Exécution' }])->all()"
                            required
                        />
                    </div>
                    <div class="col-md-3 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="can_be_n1" id="can_be_n1_c" value="1">
                            <label class="form-check-label small fw-medium"
                                   for="can_be_n1_c">
                                <i class="bi bi-person-check me-1 text-primary"></i>
                                Peut être N+1
                            </label>
                            <div class="form-text">
                                Peut valider les permissions
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="Rôle et responsabilités du poste..."></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="is_active" id="is_active_c" value="1" checked>
                            <label class="form-check-label" for="is_active_c">
                                Poste actif
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Créer le poste
                </button>
            </div>
        </div>
        </form>
    </div>
</div>

{{-- ── MODAL ÉDITION ───────────────────────────────────────── --}}
<div class="modal fade" id="editModal" tabindex="-1"
     aria-labelledby="editModalTitle" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="edit-form" action="#">
        @csrf @method('PUT')
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalTitle">
                    <i class="bi bi-pencil me-2" aria-hidden="true"></i>Modifier le poste
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-medium">
                            Intitulé du poste <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" id="edit-title"
                               class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">
                            Code <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="code" id="edit-code"
                               class="form-control" required
                               style="text-transform:uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-medium">Département</label>
                        <input type="text" name="department" id="edit-department"
                               class="form-control" list="dept-list-pos">
                    </div>
                    <div class="col-md-3">
                        <x-select
                            name="level"
                            label="Niveau hiérarchique"
                            :options="collect(range(10,1))->mapWithKeys(fn($i) => [$i => 'Niveau '.$i.' — '.match(true) { $i>=9=>'Direction', $i>=7=>'Management supérieur', $i>=5=>'Management intermédiaire', $i>=3=>'Supervision', default=>'Exécution' }])->all()"
                            id="edit-level"
                            required
                        />
                    </div>
                    <div class="col-md-3 d-flex align-items-end pb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="can_be_n1" id="edit-can_be_n1" value="1">
                            <label class="form-check-label small fw-medium"
                                   for="edit-can_be_n1">
                                <i class="bi bi-person-check me-1 text-primary"></i>
                                Peut être N+1
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-medium">Description</label>
                        <textarea name="description" id="edit-description"
                                  class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="is_active" id="edit-is_active" value="1">
                            <label class="form-check-label" for="edit-is_active">
                                Poste actif
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Annuler</button>
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
function fillEdit(btn) {
    document.getElementById('edit-form').action        = btn.dataset.url;
    document.getElementById('edit-title').value        = btn.dataset.title;
    document.getElementById('edit-code').value         = btn.dataset.code;
    document.getElementById('edit-department').value   = btn.dataset.department || '';
    document.getElementById('edit-level').value        = btn.dataset.level;
    document.getElementById('edit-description').value  = btn.dataset.description || '';
    document.getElementById('edit-can_be_n1').checked  = btn.dataset.can_be_n1 === '1';
    document.getElementById('edit-is_active').checked  = btn.dataset.is_active  === '1';
}
</script>
@endpush
