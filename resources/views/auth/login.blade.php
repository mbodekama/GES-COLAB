<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — GES-COLAB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d1b2e 0%, #185FA5 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-wrap { width: 100%; max-width: 420px; padding: 16px; }
        .brand-icon {
            width: 60px; height: 60px;
            background: #185FA5;
            border: 3px solid rgba(255,255,255,.2);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 700; color: #fff;
            margin: 0 auto 14px;
        }
        .card { border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .card-body { padding: 2.5rem; }
        .form-control, .input-group-text {
            border-color: #dee2e6;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #185FA5;
            box-shadow: 0 0 0 .2rem rgba(24,95,165,.15);
        }
        .btn-login {
            background: #185FA5; border: none; color: #fff;
            padding: 11px; font-size: 15px; font-weight: 500;
            border-radius: 10px; transition: background .2s;
        }
        .btn-login:hover { background: #0C447C; color: #fff; }
        .input-group-text { background: #f8f9fa; color: #6c757d; }
        .demo-box {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 10px;
            padding: 12px 16px;
            color: rgba(255,255,255,.8);
            font-size: 12px;
            margin-top: 16px;
        }
        .demo-box strong { color: #fff; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="text-center mb-3">
        <div class="brand-icon">G</div>
        <h4 class="text-white fw-semibold mb-0">GES-COLAB</h4>
        <small class="text-white-50">Système de Gestion RH</small>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="fw-semibold mb-1">Connexion</h5>
            <p class="text-muted small mb-4">Accédez à votre espace de travail</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-success py-2 small">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-medium small">Adresse e-mail</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="votre@email.ci" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium small">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pwd"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="••••••••" required>
                        <button type="button" class="input-group-text" onclick="togglePwd()" style="cursor:pointer">
                            <i class="bi bi-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Se souvenir de moi</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>
            </form>
        </div>
    </div>

    <div class="demo-box">
        <div class="mb-1"><strong>Comptes de démonstration</strong></div>
        <div>🔴 Superadmin : <strong>admin@gescolab.ci</strong></div>
        <div>🟣 RH : <strong>rh@gescolab.ci</strong></div>
        <div>🟡 Comptable : <strong>comptable@gescolab.ci</strong></div>
        <div>⚫ Employé : <strong>employe@gescolab.ci</strong></div>
        <div class="mt-1 text-white-50">Mot de passe : <strong class="text-white">password</strong></div>
    </div>

    <p class="text-center text-white-50 mt-3" style="font-size:.75rem">
        &copy; {{ date('Y') }} GES-COLAB — Tous droits réservés
    </p>
</div>

<script>
function togglePwd() {
    const pwd  = document.getElementById('pwd');
    const icon = document.getElementById('eye-icon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
