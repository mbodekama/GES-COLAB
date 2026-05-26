@extends('layouts.app')
@section('page-title', 'Mon profil')

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">

    {{-- INFOS GÉNÉRALES --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person me-2"></i>Informations du compte</div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}">
            @csrf @method('PATCH')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Nom complet</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                           class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Email</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                           class="form-control @error('email') is-invalid @enderror" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Rôle</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->primary_role_label }}" readonly>
                </div>
                @if($employee)
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Poste</label>
                    <input type="text" class="form-control" value="{{ $employee->position }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Département</label>
                    <input type="text" class="form-control" value="{{ $employee->department }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Matricule</label>
                    <input type="text" class="form-control" value="{{ $employee->matricule }}" readonly>
                </div>
                @endif
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check-circle me-1"></i>Mettre à jour
                </button>
            </div>
            </form>
        </div>
    </div>

    {{-- CHANGEMENT MOT DE PASSE --}}
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-lock me-2"></i>Changer le mot de passe</div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.password') }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Mot de passe actuel</label>
                    <input type="password" name="current_password"
                           class="form-control @error('current_password','updatePassword') is-invalid @enderror">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Nouveau mot de passe</label>
                    <input type="password" name="password"
                           class="form-control @error('password','updatePassword') is-invalid @enderror">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-medium">Confirmer</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-key me-1"></i>Changer le mot de passe
                </button>
            </div>
            </form>
        </div>
    </div>

    {{-- MES CONGÉS --}}
    @if($employee)
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-calendar-check me-2"></i>Mes congés récents</span>
            <span class="badge bg-info">{{ $employee->leave_balance }} jours restants</span>
        </div>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Type</th><th>Du</th><th>Au</th><th>Jours</th><th>Statut</th></tr></thead>
            <tbody>
            @forelse($employee->leaves()->latest()->take(5)->get() as $leave)
            <tr>
                <td class="small">{{ $leave->type_label }}</td>
                <td class="small">{{ $leave->start_date->format('d M Y') }}</td>
                <td class="small">{{ $leave->end_date->format('d M Y') }}</td>
                <td>{{ $leave->duration_days }}j</td>
                <td>{!! $leave->status_badge !!}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted text-center py-3 small">Aucun congé</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @endif

</div>
</div>
@endsection
