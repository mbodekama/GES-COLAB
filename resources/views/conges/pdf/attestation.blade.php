<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; }
    .page { padding: 40px; }
    .header { text-align: center; border-bottom: 2px solid #185FA5; padding-bottom: 16px; margin-bottom: 24px; }
    .company-name { font-size: 18px; font-weight: bold; color: #185FA5; }
    .doc-title { font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; margin-top: 10px; }
    .doc-subtitle { color: #666; font-size: 11px; margin-top: 4px; }
    .ref-box { text-align: right; font-size: 10px; color: #888; margin-bottom: 20px; }
    .body-text { font-size: 12px; line-height: 2; }
    .body-text strong { color: #185FA5; }
    .info-box { border: 1px solid #e0e0e0; border-radius: 6px; padding: 16px 20px; margin: 20px 0; background: #f9fafb; }
    .info-row { display: flex; margin-bottom: 6px; }
    .info-label { width: 180px; color: #666; flex-shrink: 0; }
    .info-value { font-weight: 500; }
    .sign-section { display: flex; justify-content: space-between; margin-top: 40px; }
    .sign-box { text-align: center; }
    .sign-line { border-top: 1px solid #333; width: 160px; margin: 40px auto 6px; }
    .footer { border-top: 1px solid #ddd; padding-top: 10px; margin-top: 24px; display: flex; justify-content: space-between; font-size: 9px; color: #aaa; }
    .stamp { border: 2px solid #185FA5; border-radius: 50%; width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; margin: 0 auto; color: #185FA5; font-size: 9px; text-align: center; font-weight: bold; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="company-name">{{ setting('company_name', 'ENTREPRISE') }}</div>
        <div style="font-size:10px;color:#666">{{ config('gescolab.company_address', '') }}</div>
        <div class="doc-title">Attestation de Congé</div>
        <div class="doc-subtitle">Réf. : {{ $leave->leave_number }}</div>
    </div>

    <div class="ref-box">
        Abidjan, le {{ now()->isoFormat('D MMMM YYYY') }}
    </div>

    <div class="body-text">
        <p>Nous soussignés, <strong>{{ setting('company_name', 'l\'entreprise') }}</strong>, certifions que :</p>
        <br>
        <p>
            Monsieur / Madame <strong>{{ $leave->employee->full_name }}</strong>,
            occupant le poste de <strong>{{ $leave->employee->position }}</strong>
            au sein du département <strong>{{ $leave->employee->department }}</strong>,
            matricule <strong>{{ $leave->employee->matricule }}</strong>,
            bénéficie d'un <strong>{{ $leave->type_label }}</strong>
            du <strong>{{ $leave->start_date->isoFormat('D MMMM YYYY') }}</strong>
            au <strong>{{ $leave->end_date->isoFormat('D MMMM YYYY') }}</strong>
            inclus, soit une durée de <strong>{{ $leave->duration_days }} jour(s)</strong>.
        </p>
    </div>

    <div class="info-box">
        <div class="info-row"><span class="info-label">Employé</span><span class="info-value">{{ $leave->employee->full_name }}</span></div>
        <div class="info-row"><span class="info-label">Matricule</span><span class="info-value">{{ $leave->employee->matricule }}</span></div>
        <div class="info-row"><span class="info-label">Poste</span><span class="info-value">{{ $leave->employee->position }}</span></div>
        <div class="info-row"><span class="info-label">Type de congé</span><span class="info-value">{{ $leave->type_label }}</span></div>
        <div class="info-row"><span class="info-label">Date de début</span><span class="info-value">{{ $leave->start_date->format('d/m/Y') }}</span></div>
        <div class="info-row"><span class="info-label">Date de fin</span><span class="info-value">{{ $leave->end_date->format('d/m/Y') }}</span></div>
        <div class="info-row"><span class="info-label">Durée</span><span class="info-value">{{ $leave->duration_days }} jour(s)</span></div>
        @if($leave->approvedBy)
        <div class="info-row"><span class="info-label">Approuvé par</span><span class="info-value">{{ $leave->approvedBy->name }}</span></div>
        <div class="info-row"><span class="info-label">Date d'approbation</span><span class="info-value">{{ $leave->approved_at?->format('d/m/Y') }}</span></div>
        @endif
    </div>

    <div class="body-text">
        <p>La présente attestation est délivrée pour servir et valoir ce que de droit.</p>
    </div>

    <div class="sign-section">
        <div class="sign-box">
            <div>L'employé(e)</div>
            <div class="sign-line"></div>
            <div style="font-size:10px">{{ $leave->employee->full_name }}</div>
        </div>
        <div class="sign-box">
            <div class="stamp">CACHET<br>RH</div>
        </div>
        <div class="sign-box">
            <div>Le Responsable RH</div>
            <div class="sign-line"></div>
            <div style="font-size:10px">{{ $leave->approvedBy?->name ?? '—' }}</div>
        </div>
    </div>

    <div class="footer">
        <span>Document généré le {{ now()->format('d/m/Y à H:i') }}</span>
        <span>GES-COLAB — Système de Gestion RH</span>
    </div>
</div>
</body>
</html>
