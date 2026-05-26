@extends('layouts.app')
@section('page-title', 'Nouvelle demande de congé')

@section('header-actions')
    <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
<form method="POST" action="{{ route('leaves.store') }}" enctype="multipart/form-data">
@csrf

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-calendar-plus me-2"></i>Informations de la demande</div>
    <div class="card-body">
        <div class="row g-3">

            @hasrole('rh|admin|superadmin')
            <div class="col-12">
                <label class="form-label small fw-medium">Employé concerné <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" required>
                    <option value="">— Sélectionner un employé —</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                            {{ old('employee_id', auth()->user()->employee?->id) == $emp->id ? 'selected' : '' }}>
                            {{ $emp->full_name }} — {{ $emp->position }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @else
                <input type="hidden" name="employee_id" value="{{ auth()->user()->employee?->id }}">
                <div class="col-12">
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Demande pour : <strong>{{ auth()->user()->name }}</strong>
                        — Solde disponible : <strong>{{ auth()->user()->employee?->leave_balance ?? 0 }} jours</strong>
                    </div>
                </div>
            @endhasrole

            <div class="col-md-6">
                <label class="form-label small fw-medium">Type de demande <span class="text-danger">*</span></label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    <option value="annual"      {{ old('type') === 'annual'      ? 'selected' : '' }}>Congé annuel</option>
                    <option value="sick"        {{ old('type') === 'sick'        ? 'selected' : '' }}>Congé maladie</option>
                    <option value="permission"  {{ old('type') === 'permission'  ? 'selected' : '' }}>Permission</option>
                    <option value="exceptional" {{ old('type') === 'exceptional' ? 'selected' : '' }}>Congé exceptionnel</option>
                    <option value="maternity"   {{ old('type') === 'maternity'   ? 'selected' : '' }}>Congé maternité</option>
                    <option value="paternity"   {{ old('type') === 'paternity'   ? 'selected' : '' }}>Congé paternité</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-medium">Date de début <span class="text-danger">*</span></label>
                <input type="date" name="start_date" id="start-date"
                       value="{{ old('start_date') }}"
                       class="form-control @error('start_date') is-invalid @enderror"
                       min="{{ date('Y-m-d') }}" required>
                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-medium">Date de fin <span class="text-danger">*</span></label>
                <input type="date" name="end_date" id="end-date"
                       value="{{ old('end_date') }}"
                       class="form-control @error('end_date') is-invalid @enderror"
                       min="{{ date('Y-m-d') }}" required>
                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <div class="alert alert-info py-2 small mb-0" id="duration-info">
                    <i class="bi bi-calendar3 me-1"></i>
                    Sélectionnez les dates pour calculer la durée.
                </div>
            </div>

            <div class="col-12">
                <label class="form-label small fw-medium">Motif</label>
                <textarea name="reason" class="form-control" rows="3"
                          placeholder="Décrivez brièvement le motif...">{{ old('reason') }}</textarea>
            </div>

            <div class="col-12">
                <label class="form-label small fw-medium">Pièce justificative</label>
                <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text">PDF, JPG ou PNG — max 5 Mo.</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-send me-2"></i>Soumettre la demande
    </button>
    <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary">Annuler</a>
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
    // Sync min date de fin
    if (s) endInput.min = s;
}

startInput.addEventListener('change', calcDays);
endInput.addEventListener('change', calcDays);
</script>
@endpush
