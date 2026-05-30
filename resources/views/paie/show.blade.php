@extends('layouts.app')
@section('page-title', 'Bulletin de paie')

@section('breadcrumb')
<x-breadcrumb :items="[
    ['label' => 'Gestion de la paie', 'url' => route('payroll.index')],
    ['label' => $payroll->employee->full_name.' — '.\Carbon\Carbon::parse($payroll->period.'-01')->isoFormat('MMMM YYYY')],
]" />
@endsection

@section('header-actions')
    <a href="{{ route('payroll.print.design', $payroll) }}" class="btn btn-primary btn-sm" target="_blank">
        <i class="bi bi-file-earmark-richtext me-1"></i> Bulletin PDF
    </a>
    <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('content')
<div class="row justify-content-center g-3">
<div class="col-md-7">
    <div class="card">
        <div class="card-header" style="background:#185FA5;color:white;border-radius:12px 12px 0 0 !important">
            <div>
                <div class="fw-semibold">Bulletin de paie — {{ \Carbon\Carbon::parse($payroll->period.'-01')->isoFormat('MMMM YYYY') }}</div>
                <div style="font-size:12px;opacity:.8">{{ $payroll->employee->full_name }} — {{ $payroll->employee->matricule }}</div>
            </div>
            <div style="font-size:12px;opacity:.8">{{ $payroll->employee->position }}</div>
        </div>
        <div class="card-body p-4">

            {{-- EMPLOYÉ INFO --}}
            <div class="row g-2 mb-4 pb-3 border-bottom">
                <div class="col-md-4">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase">Département</div>
                    <div class="fw-medium">{{ $payroll->employee->department }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase">Type contrat</div>
                    <div class="fw-medium">{{ strtoupper($payroll->employee->activeContract?->type ?? 'CDI') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase">Ancienneté</div>
                    <div class="fw-medium">{{ $payroll->employee->seniority_label }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase">Jours travaillés</div>
                    <div class="fw-medium">{{ $payroll->worked_days }}j</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase">Jours congés</div>
                    <div class="fw-medium">{{ $payroll->leave_days }}j</div>
                </div>
            </div>

            {{-- RÉMUNÉRATION --}}
            <div class="fw-semibold mb-2" style="color:#185FA5;font-size:12px;text-transform:uppercase;letter-spacing:.04em">
                Rémunération
            </div>
            <table class="table table-sm mb-4">
                <thead><tr><th>Libellé</th><th class="text-end">Montant (FCFA)</th></tr></thead>
                <tbody>
                    <tr><td>Salaire de base</td><td class="text-end">{{ number_format($payroll->base_salary, 0, ',', ' ') }}</td></tr>
                    @if($payroll->seniority_bonus > 0)
                    <tr><td>Prime d'ancienneté ({{ $payroll->seniority_rate }}%)</td><td class="text-end">{{ number_format($payroll->seniority_bonus, 0, ',', ' ') }}</td></tr>
                    @endif
                    @if($payroll->transport_allowance > 0)
                    <tr><td>Indemnité de transport</td><td class="text-end">{{ number_format($payroll->transport_allowance, 0, ',', ' ') }}</td></tr>
                    @endif
                    @if($payroll->housing_allowance > 0)
                    <tr><td>Indemnité de logement</td><td class="text-end">{{ number_format($payroll->housing_allowance, 0, ',', ' ') }}</td></tr>
                    @endif
                    <tr class="table-light fw-semibold">
                        <td>Total brut</td>
                        <td class="text-end">{{ number_format($payroll->gross_salary, 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- RETENUES --}}
            <div class="fw-semibold mb-2" style="color:#dc3545;font-size:12px;text-transform:uppercase;letter-spacing:.04em">
                Cotisations & Retenues
            </div>
            <table class="table table-sm mb-4">
                <thead><tr><th>Libellé</th><th class="text-end">Montant (FCFA)</th></tr></thead>
                <tbody>
                    <tr>
                        <td>CNPS salarié ({{ config('gescolab.cnps_employee_rate', 6.3) }}%)</td>
                        <td class="text-end text-danger">- {{ number_format($payroll->cnps_employee, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td>IGR (barème CI)</td>
                        <td class="text-end text-danger">- {{ number_format($payroll->igr, 0, ',', ' ') }}</td>
                    </tr>
                    <tr class="table-light fw-semibold">
                        <td>Total retenues</td>
                        <td class="text-end text-danger">- {{ number_format($payroll->cnps_employee + $payroll->igr, 0, ',', ' ') }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- NET --}}
            <div class="p-3 rounded-3 d-flex justify-content-between align-items-center"
                 style="background:#185FA5;color:#fff">
                <span class="fw-semibold fs-6">NET À PAYER</span>
                <span class="fw-bold fs-4">{{ number_format($payroll->net_salary, 0, ',', ' ') }} FCFA</span>
            </div>

            {{-- PART EMPLOYEUR --}}
            <div class="mt-3 p-3 rounded-3" style="background:#f8f9fa;font-size:12px">
                <div class="fw-semibold mb-1 text-muted">Charges employeur</div>
                <div class="d-flex justify-content-between">
                    <span>CNPS employeur ({{ config('gescolab.cnps_employer_rate', 12) }}%)</span>
                    <span>{{ number_format($payroll->cnps_employer, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="d-flex justify-content-between fw-semibold mt-1">
                    <span>Coût total employeur</span>
                    <span>{{ number_format($payroll->gross_salary + $payroll->cnps_employer, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>

        </div>
    </div>

    {{-- HISTORIQUE DES MODIFICATIONS --}}
    @include('partials.activity-log', ['activityLogs' => $activityLogs])

</div>
</div>
@endsection
