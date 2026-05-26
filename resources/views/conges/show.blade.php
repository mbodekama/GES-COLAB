@extends('layouts.app')
@section('page-title', 'Détail — '.$leave->leave_number)

@section('header-actions')
    @if($leave->status === 'approved')
    <a href="{{ route('leaves.print', $leave) }}" class="btn btn-outline-dark btn-sm" target="_blank">
        <i class="bi bi-printer me-1"></i> Attestation PDF
    </a>
    @endif
    <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
    <div class="card">
        <div class="card-header">
            <span>Demande {{ $leave->leave_number }}</span>
            {!! $leave->status_badge !!}
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Employé</div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar-initials" style="background:#E6F1FB;color:#185FA5">
                            {{ $leave->employee->initials }}
                        </div>
                        <div>
                            <div class="fw-medium">{{ $leave->employee->full_name }}</div>
                            <div class="small text-muted">{{ $leave->employee->position }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Type</div>
                    <span class="badge bg-secondary badge-status">{{ $leave->type_label }}</span>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Date de début</div>
                    <div class="fw-medium">{{ $leave->start_date->format('d M Y') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Date de fin</div>
                    <div class="fw-medium">{{ $leave->end_date->format('d M Y') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Durée</div>
                    <div class="fw-bold fs-5">{{ $leave->duration_days }} jour(s)</div>
                </div>
                @if($leave->reason)
                <div class="col-12">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Motif</div>
                    <div class="p-3 bg-light rounded" style="font-size:13.5px">{{ $leave->reason }}</div>
                </div>
                @endif
                @if($leave->rejection_reason)
                <div class="col-12">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Motif de refus</div>
                    <div class="p-3 rounded" style="background:#FCEBEB;font-size:13.5px;color:#A32D2D">
                        {{ $leave->rejection_reason }}
                    </div>
                </div>
                @endif
                @if($leave->approvedBy)
                <div class="col-md-6">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Traité par</div>
                    <div>{{ $leave->approvedBy->name }}</div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Traité le</div>
                    <div>{{ $leave->approved_at?->format('d M Y à H:i') }}</div>
                </div>
                @endif
            </div>

            @if($leave->status === 'pending')
            @can('valider congés')
            <hr>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('leaves.approve', $leave) }}">
                    @csrf
                    <button class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Approuver
                    </button>
                </form>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle me-2"></i>Refuser
                </button>
            </div>
            @endcan
            @endif
        </div>
    </div>
</div>
</div>

{{-- MODAL REFUS --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('leaves.reject', $leave) }}">
        @csrf
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Motif de refus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Expliquez brièvement le refus</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Raison du refus..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger">Confirmer le refus</button>
            </div>
        </div>
        </form>
    </div>
</div>
@endsection
