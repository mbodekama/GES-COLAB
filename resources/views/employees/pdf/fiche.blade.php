<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; }
    .page { padding: 28px 32px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #185FA5; padding-bottom: 14px; margin-bottom: 20px; }
    .company-name { font-size: 18px; font-weight: bold; color: #185FA5; }
    .doc-title { background: #185FA5; color: white; padding: 8px 16px; border-radius: 4px; text-align: center; font-weight: bold; font-size: 13px; }
    .section { margin-bottom: 18px; }
    .section-title { background: #f5f7fa; border-left: 4px solid #185FA5; padding: 5px 10px; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; color: #185FA5; }
    .grid-2 { display: flex; flex-wrap: wrap; gap: 8px 0; }
    .field { width: 50%; padding: 3px 8px; }
    .field-label { color: #888; font-size: 10px; }
    .field-value { font-weight: 500; font-size: 11px; }
    .footer { border-top: 1px solid #ddd; padding-top: 10px; margin-top: 20px; display: flex; justify-content: space-between; font-size: 10px; color: #999; }
    .sign-zone { border: 1px dashed #ccc; border-radius: 4px; padding: 20px; text-align: center; color: #bbb; font-size: 10px; margin-top: 8px; }
    table { width: 100%; border-collapse: collapse; }
    table th { background: #f5f7fa; padding: 6px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #666; border-bottom: 1px solid #e0e0e0; }
    table td { padding: 6px 10px; border-bottom: 1px solid #f5f5f5; font-size: 11px; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <div class="company-name">{{ setting('company_name', 'ENTREPRISE') }}</div>
            <div style="color:#666;font-size:10px">{{ config('gescolab.company_address', '') }}</div>
        </div>
        <div class="doc-title">FICHE EMPLOYÉ</div>
    </div>

    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding:12px;border:1px solid #e0e0e0;border-radius:6px">
        <div style="width:52px;height:52px;background:#E6F1FB;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:bold;color:#185FA5;flex-shrink:0">
            {{ $employee->initials }}
        </div>
        <div>
            <div style="font-size:16px;font-weight:bold">{{ $employee->full_name }}</div>
            <div style="color:#666">{{ $employee->position }} — {{ $employee->department }}</div>
            <div style="color:#185FA5;font-size:10px;font-weight:500">{{ $employee->matricule }}</div>
        </div>
        <div style="margin-left:auto;text-align:right">
            <div style="font-size:10px;color:#666">Statut</div>
            <div style="font-weight:600;color:{{ $employee->status === 'active' ? '#3B6D11' : '#dc3545' }}">
                {{ ucfirst($employee->status) }}
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Informations personnelles</div>
        <div class="grid-2">
            <div class="field"><div class="field-label">Date de naissance</div><div class="field-value">{{ $employee->birth_date?->format('d/m/Y') ?? '—' }}</div></div>
            <div class="field"><div class="field-label">Lieu de naissance</div><div class="field-value">{{ $employee->birth_place ?? '—' }}</div></div>
            <div class="field"><div class="field-label">Nationalité</div><div class="field-value">{{ $employee->nationality ?? '—' }}</div></div>
            <div class="field"><div class="field-label">Situation familiale</div><div class="field-value">{{ $employee->marital_status_label }}</div></div>
            <div class="field"><div class="field-label">Nombre d'enfants</div><div class="field-value">{{ $employee->children_count }}</div></div>
            <div class="field"><div class="field-label">N° CNPS</div><div class="field-value">{{ $employee->cnps_number ?? '—' }}</div></div>
            <div class="field"><div class="field-label">Téléphone</div><div class="field-value">{{ $employee->phone ?? '—' }}</div></div>
            <div class="field"><div class="field-label">Email</div><div class="field-value">{{ $employee->email }}</div></div>
            <div class="field" style="width:100%"><div class="field-label">Adresse</div><div class="field-value">{{ $employee->address ?? '—' }}</div></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Informations professionnelles</div>
        <div class="grid-2">
            <div class="field"><div class="field-label">Date d'embauche</div><div class="field-value">{{ $employee->hire_date->format('d/m/Y') }}</div></div>
            <div class="field"><div class="field-label">Ancienneté</div><div class="field-value">{{ $employee->seniority_label }}</div></div>
            <div class="field"><div class="field-label">Solde congés</div><div class="field-value">{{ $employee->leave_balance }} jours</div></div>
        </div>
    </div>

    @if($employee->activeContract)
    <div class="section">
        <div class="section-title">Contrat en cours</div>
        <div class="grid-2">
            <div class="field"><div class="field-label">N° Contrat</div><div class="field-value">{{ $employee->activeContract->contract_number }}</div></div>
            <div class="field"><div class="field-label">Type</div><div class="field-value">{{ strtoupper($employee->activeContract->type) }}</div></div>
            <div class="field"><div class="field-label">Date de début</div><div class="field-value">{{ $employee->activeContract->start_date->format('d/m/Y') }}</div></div>
            <div class="field"><div class="field-label">Date de fin</div><div class="field-value">{{ $employee->activeContract->end_date?->format('d/m/Y') ?? 'Indéterminé' }}</div></div>
            <div class="field"><div class="field-label">Salaire de base</div><div class="field-value">{{ number_format($employee->activeContract->base_salary, 0, ',', ' ') }} FCFA</div></div>
        </div>
    </div>
    @endif

    @if($employee->leaves->count())
    <div class="section">
        <div class="section-title">Historique des congés</div>
        <table>
            <thead><tr><th>N°</th><th>Type</th><th>Du</th><th>Au</th><th>Jours</th><th>Statut</th></tr></thead>
            <tbody>
            @foreach($employee->leaves()->latest()->take(8)->get() as $leave)
            <tr>
                <td>{{ $leave->leave_number }}</td>
                <td>{{ $leave->type_label }}</td>
                <td>{{ $leave->start_date->format('d/m/Y') }}</td>
                <td>{{ $leave->end_date->format('d/m/Y') }}</td>
                <td>{{ $leave->duration_days }}j</td>
                <td>{{ ucfirst($leave->status) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div style="display:flex;gap:16px;margin-top:16px">
        <div style="flex:1">
            <div class="sign-zone">Signature de l'employé<br><br><br></div>
        </div>
        <div style="flex:1">
            <div class="sign-zone">Visa RH / Direction<br><br><br></div>
        </div>
    </div>

    <div class="footer">
        <span>Document généré le {{ now()->format('d/m/Y à H:i') }}</span>
        <span>Document confidentiel — Usage interne</span>
        <span>GES-COLAB — Gestion RH</span>
    </div>
</div>
</body>
</html>
