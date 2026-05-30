<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre compte {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #333; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1d4ed8; padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; letter-spacing: .5px; }
        .body { padding: 36px 40px; }
        .body p { line-height: 1.7; margin: 0 0 16px; }
        .credentials { background: #f0f4ff; border-left: 4px solid #1d4ed8; border-radius: 4px; padding: 16px 20px; margin: 24px 0; }
        .credentials p { margin: 6px 0; font-size: 15px; }
        .credentials strong { display: inline-block; width: 110px; color: #555; }
        .credentials code { font-size: 15px; background: #e0e7ff; padding: 2px 6px; border-radius: 3px; }
        .btn-wrapper { text-align: center; margin: 28px 0 8px; }
        .btn { display: inline-block; background: #1d4ed8; color: #fff !important; text-decoration: none; padding: 12px 32px; border-radius: 6px; font-size: 15px; font-weight: bold; }
        .warning { font-size: 13px; color: #e53e3e; margin-top: 24px; }
        .footer { background: #f4f6f9; text-align: center; padding: 20px 40px; font-size: 12px; color: #888; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }} — Création de compte</h1>
        </div>

        <div class="body">
            <p>Bonjour <strong>{{ $user->name }}</strong>,</p>

            <p>
                Un compte a été créé pour vous sur la plateforme <strong>{{ config('app.name') }}</strong>,
                le système de gestion des ressources humaines de votre entreprise.
            </p>

            <div class="credentials">
                <p><strong>Adresse e-mail :</strong> <code>{{ $user->email }}</code></p>
                <p><strong>Mot de passe :</strong> <code>{{ $plainPassword }}</code></p>
            </div>

            <div class="btn-wrapper">
                <a href="{{ config('app.url') }}/login" class="btn">Accéder à mon espace</a>
            </div>

            <p class="warning">
                Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe
                dès votre première connexion.
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
