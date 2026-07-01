<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
    exit;
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-card .logo { text-align: center; margin-bottom: 30px; }
        .login-card .logo i { font-size: 48px; color: #667eea; background: #f0f2ff; padding: 20px; border-radius: 15px; }
        .login-card h2 { text-align: center; font-weight: 700; color: #333; }
        .login-card .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 2px solid #e8ecf1; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 12px; border-radius: 10px; font-weight: 600;
            border: none; width: 100%; transition: transform 0.3s;
        }
        .btn-primary-custom:hover { transform: translateY(-2px); color: white; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo"><i class="fas fa-chart-line"></i></div>
        <h2>DELTA Audit</h2>
        <p class="subtitle">Connectez-vous à votre espace</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= escape($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="<?= url('login_process.php') ?>" method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mot de passe</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-primary-custom">
                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
            </button>
        </form>

        <div class="text-center mt-3">
            <small><a href="<?= url('register.php') ?>" class="text-decoration-none">Créer un compte</a></small>
        </div>
        <div class="text-center mt-2">
            <small class="text-muted">Demo: admin@example.com / password</small>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>