<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; color: #333; }
    .page { padding: 28px 32px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #185FA5; padding-bottom: 12px; margin-bottom: 16px; }
    .company-name { font-size: 17px; font-weight: bold; color: #185FA5; }
    .title-box { background: #185FA5; color: white; padding: 8px 18px; border-radius: 4px; text-align: center; }
    .info-grid { display: flex; gap: 10px; margin-bottom: 14px; }
    .info-block { flex: 1; border: 1px solid #e0e0e0; border-radius: 4px; padding: 8px 12px; }
    .info-block h4 { font-size: 9px; color: #185FA5; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; border-bottom: 1px solid #eee; padding-bottom: 3px; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 3px; font-size: 10px; }
    .info-label { color: #888; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table thead tr { background: #f5f7fa; }
    table th { padding: 6px 10px; text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: .04em; color: #666; border-bottom: 1px solid #ddd; }
    table td { padding: 6px 10px; border-bottom: 1px solid #f0f0f0; font-size: 10.5px; }
    .total-row { background: #f5f7fa; font-weight: bold; }
    .net-box { background: #185FA5; color: white; border-radius: 6px; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
    .net-amount { font-size: 18px; font-weight: bold; }
    .section-title { font-size: 10px; font-weight: bold; color: #185FA5; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; padding-left: 4px; border-left: 3px solid #185FA5; }
    .amount-pos { color: #2d6a4f; }
    .amount-neg { color: #c0392b; }
    .footer { border-top: 1px solid #ddd; padding-top: 8px; margin-top: 14px; display: flex; justify-content: space-between; font-size: 9px; color: #aaa; }
    .sign-zone { border: 1px dashed #ccc; border-radius: 4px; padding: 12px; text-align: center; color: #bbb; font-size: 9px; height: 70px; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <div class="company-name">{{ setting('company_name', 'ENTREPRISE') }}</div>
            <div style="font-size:9px;color:#666">{{ config('gescolab.company_address', '') }}</div>
        </div>
        <div class="title-box">
            <div style="font-weight:bold;font-size:13px">BULLETIN DE PAIE</div>
            <div style="font-size:9px;opacity:.85">{{ \Carbon\Carbon::parse($payroll->period.'-01')->isoFormat('MMMM YYYY') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-block">
            <h4>Employé</h4>
            <div class="info-row"><span class="info-label">Nom & Prénom</span><strong>{{ $payroll->employee->full_name }}</strong></div>
            <div class="info-row"><span class="info-label">Matricule</span><span>{{ $payroll->employee->matricule }}</span></div>
            <div class="info-row"><span class="info-label">Poste</span><span>{{ $payroll->employee->position }}</span></div>
            <div class="info-row"><span class="info-label">Département</span><span>{{ $payroll->employee->department }}</span></div>
        </div>
        <div class="info-block">
            <h4>Contrat</h4>
            <div class="info-row"><span class="info-label">Type</span><span>{{ strtoupper($payroll->employee->activeContract?->type ?? 'CDI') }}</span></div>
            <div class="info-row"><span class="info-label">Embauche</span><span>{{ $payroll->employee->hire_date->format('d/m/Y') }}</span></div>
            <div class="info-row"><span class="info-label">Ancienneté</span><span>{{ $payroll->employee->seniority_label }}</span></div>
            <div class="info-row"><span class="info-label">N° CNPS</span><span>{{ $payroll->employee->cnps_number ?? '—' }}</span></div>
        </div>
        <div class="info-block">
            <h4>Période</h4>
            <div class="info-row"><span class="info-label">Mois</span><strong>{{ \Carbon\Carbon::parse($payroll->period.'-01')->isoFormat('MMMM YYYY') }}</strong></div>
            <div class="info-row"><span class="info-label">Jours travaillés</span><span>{{ $payroll->worked_days }}</span></div>
            <div class="info-row"><span class="info-label">Jours congés</span><span>{{ $payroll->leave_days }}</span></div>
        </div>
    </div>

    <div class="section-title">Rémunération</div>
    <table>
        <thead><tr><th>Libellé</th><th>Base</th><th>Taux</th><th style="text-align:right">Montant (FCFA)</th></tr></thead>
        <tbody>
            <tr><td>Salaire de base</td><td>—</td><td>100%</td><td style="text-align:right" class="amount-pos">{{ number_format($payroll->base_salary, 0, ',', ' ') }}</td></tr>
            @if($payroll->seniority_bonus > 0)
            <tr><td>Prime d'ancienneté</td><td>{{ number_format($payroll->base_salary, 0, ',', ' ') }}</td><td>{{ $payroll->seniority_rate }}%</td><td style="text-align:right" class="amount-pos">{{ number_format($payroll->seniority_bonus, 0, ',', ' ') }}</td></tr>
            @endif
            @if($payroll->transport_allowance > 0)
            <tr><td>Indemnité de transport</td><td>—</td><td>Forfait</td><td style="text-align:right" class="amount-pos">{{ number_format($payroll->transport_allowance, 0, ',', ' ') }}</td></tr>
            @endif
            @if($payroll->housing_allowance > 0)
            <tr><td>Indemnité de logement</td><td>—</td><td>Forfait</td><td style="text-align:right" class="amount-pos">{{ number_format($payroll->housing_allowance, 0, ',', ' ') }}</td></tr>
            @endif
            <tr class="total-row"><td colspan="3"><strong>TOTAL BRUT</strong></td><td style="text-align:right"><strong>{{ number_format($payroll->gross_salary, 0, ',', ' ') }} FCFA</strong></td></tr>
        </tbody>
    </table>

    <div class="section-title">Cotisations & Retenues</div>
    <table>
        <thead><tr><th>Libellé</th><th>Assiette</th><th>Taux</th><th style="text-align:right">Montant (FCFA)</th></tr></thead>
        <tbody>
            <tr><td>CNPS (part salarié)</td><td>{{ number_format($payroll->gross_salary, 0, ',', ' ') }}</td><td>{{ config('gescolab.cnps_employee_rate', 6.3) }}%</td><td style="text-align:right" class="amount-neg">- {{ number_format($payroll->cnps_employee, 0, ',', ' ') }}</td></tr>
            <tr><td>Impôt Général sur le Revenu (IGR)</td><td>{{ number_format($payroll->gross_salary - $payroll->cnps_employee, 0, ',', ' ') }}</td><td>Barème CI</td><td style="text-align:right" class="amount-neg">- {{ number_format($payroll->igr, 0, ',', ' ') }}</td></tr>
            <tr class="total-row"><td colspan="3"><strong>TOTAL RETENUES</strong></td><td style="text-align:right" class="amount-neg"><strong>- {{ number_format($payroll->cnps_employee + $payroll->igr, 0, ',', ' ') }} FCFA</strong></td></tr>
        </tbody>
    </table>

    <div class="net-box">
        <div style="font-size:12px;font-weight:bold">NET À PAYER</div>
        <div class="net-amount">{{ number_format($payroll->net_salary, 0, ',', ' ') }} FCFA</div>
    </div>

    <div style="display:flex;gap:14px;margin-bottom:14px">
        <div style="flex:1;border:1px solid #e0e0e0;border-radius:4px;padding:8px 12px;font-size:10px">
            <div style="font-weight:bold;margin-bottom:4px;color:#666">Charges employeur</div>
            <div style="display:flex;justify-content:space-between;margin-bottom:3px"><span>CNPS employeur ({{ config('gescolab.cnps_employer_rate', 12) }}%)</span><span>{{ number_format($payroll->cnps_employer, 0, ',', ' ') }} FCFA</span></div>
            <div style="display:flex;justify-content:space-between;font-weight:bold"><span>Coût total employeur</span><span>{{ number_format($payroll->gross_salary + $payroll->cnps_employer, 0, ',', ' ') }} FCFA</span></div>
        </div>
        <div style="flex:1">
            <div class="sign-zone">Signature & cachet de l'employeur</div>
        </div>
    </div>

    <div class="footer">
        <span>Bulletin généré le {{ now()->format('d/m/Y à H:i') }}</span>
        <span>Document confidentiel — Usage interne</span>
        <span>GES-COLAB — Système de Gestion RH</span>
    </div>
</div>
</body>
</html>
