<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande approuvée</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #333; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #16a34a; padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 20px; }
        .body { padding: 36px 40px; }
        .body p { line-height: 1.7; margin: 0 0 16px; }
        .badge { display: inline-block; background: #dcfce7; color: #15803d; border-radius: 20px; padding: 4px 14px; font-weight: bold; font-size: 14px; }
        .details { background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 4px; padding: 16px 20px; margin: 24px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 5px 0; font-size: 14px; }
        .details td:first-child { color: #555; width: 160px; }
        .details td:last-child { font-weight: bold; }
        .footer { background: #f4f6f9; text-align: center; padding: 20px 40px; font-size: 12px; color: #888; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>✓ Demande approuvée</h1>
        </div>

        <div class="body">
            <p>Bonjour <strong>{{ $leave->employee->full_name }}</strong>,</p>

            <p>
                Nous avons le plaisir de vous informer que votre demande de
                <span class="badge">{{ $leave->type_label }}</span>
                a été <strong>approuvée</strong> par le service RH.
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
                    <tr>
                        <td>Approuvé le</td>
                        <td>{{ $leave->approved_at?->translatedFormat('d F Y à H\hi') }}</td>
                    </tr>
                </table>
            </div>

            <p>Bon repos et bonne récupération.</p>
        </div>

        <div class="footer">
            {{ setting('company_name', config('app.name')) }} &mdash;
            {{ setting('company_address', '') }}<br>
            Cet e-mail a été généré automatiquement, merci de ne pas y répondre.
        </div>
    </div>
</body>
</html>
