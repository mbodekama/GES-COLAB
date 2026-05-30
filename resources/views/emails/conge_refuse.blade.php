<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande refusée</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #333; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #dc2626; padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; }
        .body { padding: 36px 40px; }
        .body p { line-height: 1.7; margin: 0 0 16px; }
        .badge { display: inline-block; background: #fee2e2; color: #b91c1c; border-radius: 20px; padding: 4px 14px; font-weight: bold; font-size: 14px; }
        .details { background: #fff5f5; border-left: 4px solid #dc2626; border-radius: 4px; padding: 16px 20px; margin: 24px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 5px 0; font-size: 14px; }
        .details td:first-child { color: #555; width: 160px; }
        .details td:last-child { font-weight: bold; }
        .motif { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 4px; padding: 14px 18px; margin: 20px 0; font-style: italic; color: #7f1d1d; }
        .footer { background: #f4f6f9; text-align: center; padding: 20px 40px; font-size: 12px; color: #888; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>✗ Demande refusée</h1>
        </div>

        <div class="body">
            <p>Bonjour <strong>{{ $leave->employee->full_name }}</strong>,</p>

            <p>
                Nous vous informons que votre demande de
                <span class="badge">{{ $leave->type_label }}</span>
                a été <strong>refusée</strong>.
            </p>

            <div class="details">
                <table>
                    <tr>
                        <td>N° de demande</td>
                        <td>{{ $leave->leave_number }}</td>
                    </tr>
                    <tr>
                        <td>Type</td>
                        <td>{{ $leave->type_label }}</td>
                    </tr>
                    <tr>
                        <td>Date de début</td>
                        <td>{{ \Carbon\Carbon::parse($leave->start_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Date de fin</td>
                        <td>{{ \Carbon\Carbon::parse($leave->end_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Durée</td>
                        <td>{{ $leave->duration_days }} jour(s)</td>
                    </tr>
                </table>
            </div>

            <p><strong>Motif du refus :</strong></p>
            <div class="motif">{{ $motif }}</div>

            <p>
                Pour toute question, veuillez contacter le service RH de votre entreprise.
            </p>
        </div>

        <div class="footer">
            {{ setting('company_name', config('app.name')) }} &mdash;
            {{ setting('company_address', '') }}<br>
            Cet e-mail a été généré automatiquement, merci de ne pas y répondre.
        </div>
    </div>
</body>
</html>
