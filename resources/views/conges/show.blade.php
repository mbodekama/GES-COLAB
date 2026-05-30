@extends('layouts.app')
@section('page-title', 'Demande — '.$leave->leave_number)

@section('header-actions')
    @if($leave->status === 'approved')
        <a href="{{ route('leaves.print.design', $leave) }}" class="btn btn-primary btn-sm" target="_blank">
            <i class="bi bi-file-earmark-richtext me-1"></i> Attestation PDF
        </a>
    @endif
    @if($leave->status === 'pending' && auth()->user()->can('modifier congés'))
        <a href="{{ route('leaves.edit', $leave) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i> Modifier
        </a>
    @endif
    <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
    <div class="row g-3">

        {{-- COL GAUCHE — Infos + Actions --}}
        <div class="col-md-4">

            {{-- CARTE STATUT --}}
            <div class="card text-center p-4 mb-3">
                <div class="avatar-initials mx-auto mb-3"
                     style="width:64px;height:64px;font-size:22px;background:#E6F1FB;color:#185FA5">
                    {{ $leave->employee->initials }}
                </div>
                <h5 class="fw-semibold mb-0">{{ $leave->employee->full_name }}</h5>
                <p class="text-muted mb-2">{{ $leave->employee->position }}</p>
                {!! $leave->workflow_badge !!}
                <hr>
                <div class="text-start">
                    @foreach([
                        ['bi-hash',         'N° Demande', $leave->leave_number],
                        ['bi-tag',          'Type',       $leave->type_label],
                        ['bi-calendar3',    'Du',         $leave->start_date->format('d M Y')],
                        ['bi-calendar3',    'Au',         $leave->end_date->format('d M Y')],
                        ['bi-clock',        'Durée',      $leave->duration_days.' jour(s)'],
                        ['bi-calendar-plus','Soumis le',  $leave->created_at->format('d M Y à H:i')],
                    ] as [$icon, $label, $value])
                        <div class="d-flex justify-content-between align-items-center mb-2"
                             style="font-size:13px">
                    <span class="text-muted">
                        <i class="bi {{ $icon }} me-1"></i>{{ $label }}
                    </span>
                            <strong>{{ $value }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ACTIONS N+1 --}}
            {{-- ACTIONS N+1 --}}
            @if($leave->workflow_step === 'pending_n1')
                @php $canValidateN1 = auth()->user()->hasRole(['superadmin','admin'])
                       || auth()->user()->employee?->poste?->can_be_n1 === true; @endphp

                @if($canValidateN1 && $leave->employee->user_id !== auth()->id())
                    <div class="card mb-3">
                        <div class="card-header" style="background:#E6F1FB">
                            <i class="bi bi-person-check me-2" style="color:#185FA5"></i>
                            <span style="color:#185FA5;font-weight:600">
                Action requise — N+1
            </span>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Cette demande de permission attend votre validation
                                avant transmission au RH.
                            </p>

                            <form method="POST"
                                  action="{{ route('leaves.approve.n1', $leave) }}"
                                  class="mb-2">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small fw-medium">
                                        Commentaire
                                        <small class="text-muted">(optionnel)</small>
                                    </label>
                                    <textarea name="comment" class="form-control form-control-sm"
                                              rows="2"
                                              placeholder="Votre commentaire..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Valider & Transmettre au RH
                                </button>
                            </form>

                            <button class="btn btn-outline-danger w-100 btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectN1Modal">
                                <i class="bi bi-x-circle me-2"></i>Refuser
                            </button>
                        </div>
                    </div>
                @endif
            @endif

            {{-- ACTIONS RH --}}
            @if($leave->workflow_step === 'pending_rh')
                @can('valider congés')
                    <div class="card mb-3">
                        <div class="card-header" style="background:#EAF3DE">
                            <i class="bi bi-shield-check me-2" style="color:#3B6D11"></i>
                            <span style="color:#3B6D11;font-weight:600">Action requise — RH</span>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                @if($leave->type === 'permission')
                                    Permission validée par le N+1. Votre validation est requise.
                                @else
                                    Cette demande de congé attend votre validation.
                                @endif
                            </p>

                            {{-- Approuver RH --}}
                            <form method="POST" action="{{ route('leaves.approve', $leave) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-check-circle me-2"></i>Approuver définitivement
                                </button>
                            </form>

                            {{-- Refuser RH --}}
                            <button class="btn btn-outline-danger w-100 btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#rejectRhModal">
                                <i class="bi bi-x-circle me-2"></i>Refuser
                            </button>
                        </div>
                    </div>
                @endcan
            @endif

            {{-- PIÈCE JOINTE --}}
            @if($leave->attachment)
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-paperclip me-2"></i>Pièce jointe
                    </div>
                    <div class="card-body">
                        <a href="{{ Storage::url($leave->attachment) }}"
                           target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-file-earmark me-1"></i>Voir le document
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- COL DROITE — Détails + Workflow --}}
        <div class="col-md-8">

            {{-- DÉTAILS DE LA DEMANDE --}}
            <div class="card mb-3">
                <div class="card-header">
                    <span><i class="bi bi-file-earmark-text me-2"></i>Détails de la demande</span>
                    {!! $leave->workflow_badge !!}
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-muted"
                                 style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                Type
                            </div>
                            <span class="badge bg-secondary badge-status">
                            {{ $leave->type_label }}
                        </span>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted"
                                 style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                Date de début
                            </div>
                            <div class="fw-medium">{{ $leave->start_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted"
                                 style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                Date de fin
                            </div>
                            <div class="fw-medium">{{ $leave->end_date->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted"
                                 style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                Durée
                            </div>
                            <div class="fw-bold fs-5">{{ $leave->duration_days }} jour(s)</div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted"
                                 style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                Solde avant demande
                            </div>
                            <div class="fw-medium">
                                {{ $leave->employee->leave_balance }} jour(s) restants
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted"
                                 style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                Soumis le
                            </div>
                            <div class="fw-medium">
                                {{ $leave->created_at->format('d M Y à H:i') }}
                            </div>
                        </div>

                        @if($leave->reason)
                            <div class="col-12">
                                <div class="text-muted"
                                     style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                    Motif
                                </div>
                                <div class="p-3 rounded mt-1" style="background:#f8f9fa;font-size:13.5px">
                                    {{ $leave->reason }}
                                </div>
                            </div>
                        @endif

                        @if($leave->rejection_reason)
                            <div class="col-12">
                                <div class="text-muted"
                                     style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">
                                    Motif de refus
                                </div>
                                <div class="p-3 rounded mt-1"
                                     style="background:#FCEBEB;color:#A32D2D;font-size:13.5px">
                                    <i class="bi bi-x-circle me-2"></i>{{ $leave->rejection_reason }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TIMELINE WORKFLOW --}}
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-diagram-3 me-2"></i>Suivi du workflow
                </div>
                <div class="card-body py-4">
                    <div class="d-flex flex-column gap-0">

                        {{-- ÉTAPE 1 — Soumission --}}
                        <div class="d-flex gap-3">
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width:36px;height:36px;background:#EAF3DE;flex-shrink:0">
                                    <i class="bi bi-send-fill" style="color:#3B6D11;font-size:15px"></i>
                                </div>
                                <div style="width:2px;flex:1;background:#e0e0e0;min-height:24px;margin:4px 0"></div>
                            </div>
                            <div class="pb-3">
                                <div class="fw-semibold" style="font-size:13.5px">Demande soumise</div>
                                <div class="text-muted small">
                                    {{ $leave->created_at->format('d M Y à H:i') }}
                                    — par <strong>{{ $leave->employee->full_name }}</strong>
                                </div>
                                <span class="badge bg-success mt-1" style="font-size:10px">Complété</span>
                            </div>
                        </div>

                        {{-- ÉTAPE 2 — Validation N+1 (permissions uniquement) --}}
                        @if($leave->type === 'permission')
                            @php
                                $n1Done    = !is_null($leave->n1_validated_at);
                                $n1Active  = $leave->workflow_step === 'pending_n1';
                                $n1Refused = $leave->status === 'rejected' && !$n1Done;

                                $n1BgIcon  = $n1Done    ? '#EAF3DE'
                                           : ($n1Active  ? '#FAEEDA'
                                           : ($n1Refused ? '#FCEBEB' : '#f5f5f5'));
                                $n1Color   = $n1Done    ? '#3B6D11'
                                           : ($n1Active  ? '#BA7517'
                                           : ($n1Refused ? '#A32D2D' : '#adb5bd'));
                            @endphp
                            <div class="d-flex gap-3">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                         style="width:36px;height:36px;background:{{ $n1BgIcon }};flex-shrink:0">
                                        <i class="bi bi-person-check-fill"
                                           style="color:{{ $n1Color }};font-size:15px"></i>
                                    </div>
                                    <div style="width:2px;flex:1;background:#e0e0e0;min-height:24px;margin:4px 0"></div>
                                </div>
                                <div class="pb-3">
                                    <div class="fw-semibold" style="font-size:13.5px">
                                        Validation N+1
                                        <small class="text-muted fw-normal">
                                            (Superviseur / Chef de service / DGO)
                                        </small>
                                    </div>
                                    @if($n1Done)
                                        <div class="text-muted small">
                                            {{ $leave->n1_validated_at->format('d M Y à H:i') }}
                                            — par <strong>{{ $leave->n1Validator?->name ?? '—' }}</strong>
                                        </div>
                                        @if($leave->n1_comment)
                                            <div class="small mt-1 fst-italic text-muted">
                                                « {{ $leave->n1_comment }} »
                                            </div>
                                        @endif
                                        <span class="badge bg-success mt-1" style="font-size:10px">
                                    Validé — transmis au RH
                                </span>
                                    @elseif($n1Refused)
                                        <div class="text-muted small">Refusé à cette étape</div>
                                        <span class="badge bg-danger mt-1" style="font-size:10px">Refusé</span>
                                    @elseif($n1Active)
                                        <div class="text-muted small">
                                            En attente de validation par le N+1
                                        </div>
                                        <span class="badge bg-warning text-dark mt-1" style="font-size:10px">
                                    En attente
                                </span>
                                    @else
                                        <div class="text-muted small">Non atteinte</div>
                                        <span class="badge bg-secondary mt-1" style="font-size:10px">—</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- ÉTAPE 3 — Validation RH --}}
                        @php
                            $rhDone    = $leave->status === 'approved';
                            $rhActive  = $leave->workflow_step === 'pending_rh';
                            $rhRefused = $leave->status === 'rejected'
                                         && ($leave->type !== 'permission' || $leave->n1_validated_at);

                            $rhBgIcon  = $rhDone    ? '#EAF3DE'
                                       : ($rhActive  ? '#FAEEDA'
                                       : ($rhRefused ? '#FCEBEB' : '#f5f5f5'));
                            $rhColor   = $rhDone    ? '#3B6D11'
                                       : ($rhActive  ? '#BA7517'
                                       : ($rhRefused ? '#A32D2D' : '#adb5bd'));
                        @endphp
                        <div class="d-flex gap-3">
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width:36px;height:36px;background:{{ $rhBgIcon }};flex-shrink:0">
                                    <i class="bi bi-shield-fill-check"
                                       style="color:{{ $rhColor }};font-size:15px"></i>
                                </div>
                                @if($leave->status === 'approved')
                                    <div style="width:2px;flex:1;background:#e0e0e0;min-height:24px;margin:4px 0"></div>
                                @endif
                            </div>
                            <div class="pb-3">
                                <div class="fw-semibold" style="font-size:13.5px">
                                    Validation RH
                                    <small class="text-muted fw-normal">(Validateur final)</small>
                                </div>
                                @if($rhDone)
                                    <div class="text-muted small">
                                        {{ $leave->approved_at?->format('d M Y à H:i') }}
                                        — par <strong>{{ $leave->approvedBy?->name ?? '—' }}</strong>
                                    </div>
                                    <span class="badge bg-success mt-1" style="font-size:10px">
                                    Approuvé définitivement
                                </span>
                                @elseif($rhRefused)
                                    <div class="text-muted small">
                                        Refusé — {{ $leave->rejection_reason ?? '' }}
                                    </div>
                                    <span class="badge bg-danger mt-1" style="font-size:10px">Refusé</span>
                                @elseif($rhActive)
                                    <div class="text-muted small">
                                        En attente de validation par le RH
                                    </div>
                                    <span class="badge bg-warning text-dark mt-1" style="font-size:10px">
                                    En attente
                                </span>
                                @else
                                    <div class="text-muted small">Non atteinte</div>
                                    <span class="badge bg-secondary mt-1" style="font-size:10px">—</span>
                                @endif
                            </div>
                        </div>

                        {{-- ÉTAPE 4 — Clôture (si approuvé) --}}
                        @if($leave->status === 'approved')
                            <div class="d-flex gap-3">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                         style="width:36px;height:36px;background:#EEEDFE;flex-shrink:0">
                                        <i class="bi bi-check2-all" style="color:#534AB7;font-size:15px"></i>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="font-size:13.5px">Demande clôturée</div>
                                    <div class="text-muted small">
                                        Solde mis à jour — {{ $leave->duration_days }} jour(s) déduit(s)
                                    </div>
                                    <a href="{{ route('leaves.print.design', $leave) }}"
                                       target="_blank"
                                       class="btn btn-primary btn-sm mt-2">
                                        <i class="bi bi-file-earmark-richtext me-1"></i>Imprimer l'attestation
                                    </a>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── MODAL REFUS N+1 ────────────────────────────────────── --}}
    <div class="modal fade" id="rejectN1Modal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('leaves.reject.n1', $leave) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-x-circle me-2 text-danger"></i>Refuser la demande (N+1)
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small py-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Cette action refusera définitivement la demande de
                            <strong>{{ $leave->employee->full_name }}</strong>.
                        </div>
                        <label class="form-label small fw-medium">
                            Motif du refus <span class="text-danger">*</span>
                        </label>
                        <textarea name="comment" class="form-control" rows="3"
                                  placeholder="Expliquez le motif du refus..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>Confirmer le refus
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── MODAL REFUS RH ──────────────────────────────────────── --}}
    <div class="modal fade" id="rejectRhModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('leaves.reject', $leave) }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-x-circle me-2 text-danger"></i>Refuser la demande (RH)
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning small py-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Vous êtes le validateur final. Ce refus est définitif.
                        </div>
                        <label class="form-label small fw-medium">
                            Motif du refus <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="Motif du refus..." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>Confirmer le refus
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
