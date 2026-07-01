<?php
require_once 'config.php';
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Inscription</title>
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
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .register-card .logo { text-align: center; margin-bottom: 20px; }
        .register-card .logo i { font-size: 40px; color: #667eea; background: #f0f2ff; padding: 15px; border-radius: 15px; }
        .register-card h2 { text-align: center; font-weight: 700; color: #333; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 2px solid #e8ecf1; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 12px; border-radius: 10px; font-weight: 600;
            border: none; width: 100%;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo"><i class="fas fa-user-plus"></i></div>
        <h2>Créer un compte</h2>
        <p class="text-center text-muted">Inscrivez-vous pour accéder à la plateforme</p>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="<?= url('register_process.php') ?>" method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold">Nom complet</label>
                <input type="text" name="name" class="form-control" placeholder="Jean Dupont" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="jean@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mot de passe (min 8 caractères)</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" minlength="8" required>
            </div>
            <button type="submit" class="btn-primary-custom">
                <i class="fas fa-user-plus me-2"></i>S'inscrire
            </button>
        </form>
        <div class="text-center mt-3">
            <small><a href="<?= url('login.php') ?>" class="text-decoration-none">Déjà un compte ? Se connecter</a></small>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>