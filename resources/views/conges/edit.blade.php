@extends('layouts.app')
@section('page-title', 'Modifier — '.$leave->leave_number)

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Congés & Permissions', 'url' => route('leaves.index')],
    ['label' => $leave->leave_number, 'url' => route('leaves.show', $leave)],
    ['label' => 'Modifier'],
]" />
@endsection

@section('header-actions')
    <a href="{{ route('leaves.show', $leave) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
<form method="POST" action="{{ route('leaves.update', $leave) }}">
@csrf
@method('PUT')

<div class="card mb-3">
    <div class="card-header d-flex align-items-center gap-2"
         style="background:#E6F1FB; border-left:4px solid #185FA5;">
        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
              style="width:22px;height:22px;background:#185FA5;color:#fff;font-size:11px;font-weight:700">1</span>
        <span style="color:#185FA5"><i class="bi bi-calendar-plus me-1"></i>Modifier la demande</span>
    </div>
    <div class="card-body">
        <div class="row g-3">

            {{-- Employé (lecture seule) --}}
            <div class="col-12">
                <div class="alert alert-info py-2 small mb-0">
                    <i class="bi bi-person me-1"></i>
                    Demande pour : <strong>{{ $leave->employee->full_name }}</strong>
                    — Solde disponible : <strong>{{ $leave->employee->leave_balance }} jours</strong>
                </div>
            </div>

            <div class="col-md-6">
                <x-select
                    name="type"
                    label="Type de demande"
                    :options="[
                        'annual'      => 'Congé annuel',
                        'sick'        => 'Congé maladie',
                        'permission'  => 'Permission',
                        'exceptional' => 'Congé exceptionnel',
                        'maternity'   => 'Congé maternité',
                        'paternity'   => 'Congé paternité',
                    ]"
                    :value="old('type', $leave->type)"
                    required
                />
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-medium">Date de début <span class="text-danger">*</span></label>
                <input type="date" name="start_date" id="start-date"
                       value="{{ old('start_date', $leave->start_date->format('Y-m-d')) }}"
                       class="form-control @error('start_date') is-invalid @enderror"
                       required>
                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-medium">Date de fin <span class="text-danger">*</span></label>
                <input type="date" name="end_date" id="end-date"
                       value="{{ old('end_date', $leave->end_date->format('Y-m-d')) }}"
                       class="form-control @error('end_date') is-invalid @enderror"
                       required>
                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <div class="alert alert-success py-2 small mb-0" id="duration-info">
                    <i class="bi bi-calendar-check me-1"></i>
                    Durée actuelle : <strong>{{ $leave->duration_days }} jour(s) calendaire(s)</strong>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label small fw-medium">Motif</label>
                <textarea name="reason" class="form-control" rows="3"
                          placeholder="Décrivez brièvement le motif...">{{ old('reason', $leave->reason) }}</textarea>
            </div>

        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle me-2"></i>Enregistrer les modifications
    </button>
    <a href="{{ route('leaves.show', $leave) }}" class="btn btn-outline-secondary">Annuler</a>
</div>

</form>
</div>
</div>
@endsection

@push('scripts')
<script>
const startInput = document.getElementById('start-date');
const endInput   = document.getElementById('end-date');
const info       = document.getElementById('duration-info');

function calcDays() {
    const s = startInput.value, e = endInput.value;
    if (s && e) {
        const diff = Math.ceil((new Date(e) - new Date(s)) / 86400000) + 1;
        if (diff > 0) {
            info.innerHTML = `<i class="bi bi-calendar-check me-1"></i>Durée estimée : <strong>${diff} jour(s) calendaire(s)</strong>`;
            info.className = 'alert alert-success py-2 small mb-0';
        } else {
            info.innerHTML = `<i class="bi bi-exclamation-triangle me-1"></i>La date de fin doit être après la date de début.`;
            info.className = 'alert alert-danger py-2 small mb-0';
        }
    }
    if (s) endInput.min = s;
}

startInput.addEventListener('change', calcDays);
endInput.addEventListener('change', calcDays);
</script>
@endpush
