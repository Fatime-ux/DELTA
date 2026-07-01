<?php
require_once 'config.php';

$pageTitle = 'Accueil';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Plateforme d'audit financier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar-custom { background: rgba(0,0,0,0.3) !important; backdrop-filter: blur(10px); }
        .hero { padding: 80px 0 60px; color: white; }
        .hero h1 { font-size: 3.5rem; font-weight: 700; margin-bottom: 20px; }
        .hero .subtitle { font-size: 1.2rem; opacity: 0.9; margin-bottom: 40px; }
        .card-feature {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 30px;
            color: white;
            transition: all 0.3s;
            height: 100%;
            text-align: center;
        }
        .card-feature:hover { transform: translateY(-10px); background: rgba(255,255,255,0.2); }
        .card-feature i { font-size: 2.5rem; margin-bottom: 15px; }
        .btn-light-custom {
            background: white; color: #667eea; padding: 12px 40px;
            border-radius: 30px; font-weight: 600; border: none;
            transition: all 0.3s;
        }
        .btn-light-custom:hover { transform: scale(1.05); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .btn-outline-custom {
            background: transparent; color: white; padding: 12px 40px;
            border-radius: 30px; font-weight: 600; border: 2px solid white;
            transition: all 0.3s;
        }
        .btn-outline-custom:hover { background: white; color: #667eea; }
        .footer { color: rgba(255,255,255,0.7); padding: 40px 0; border-top: 1px solid rgba(255,255,255,0.1); }
        .features-section { padding: 60px 0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="#">
                <i class="fas fa-chart-line me-2"></i>DELTA Audit
            </a>
            <div class="ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= url('dashboard.php') ?>" class="btn btn-light btn-sm">
                        <i class="fas fa-home me-1"></i> Tableau de bord
                    </a>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-sign-in-alt me-1"></i> Connexion
                    </a>
                    <a href="<?= url('register.php') ?>" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-user-plus me-1"></i> Inscription
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1>Gestion d'audit <br><span style="color: #ffd700;">financier DELTA</span></h1>
                    <p class="subtitle">
                        Plateforme conforme aux normes <strong>ISA</strong> et <strong>OHADA</strong> pour la gestion complète 
                        de vos missions d'audit : planning, feuilles de travail, observations, 
                        circularisation et rapports.
                    </p>
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?= url('login.php') ?>" class="btn btn-light-custom me-3">
                            <i class="fas fa-rocket me-2"></i>Démarrer
                        </a>
                        <a href="#features" class="btn btn-outline-custom">
                            <i class="fas fa-info-circle me-2"></i>En savoir plus
                        </a>
                    <?php else: ?>
                        <a href="<?= url('dashboard.php') ?>" class="btn btn-light-custom">
                            <i class="fas fa-tachometer-alt me-2"></i>Accéder au tableau de bord
                        </a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block">
                    <i class="fas fa-chart-pie" style="font-size: 12rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card-feature">
                        <i class="fas fa-briefcase"></i>
                        <h5>Gestion des missions</h5>
                        <p>Planning, équipe, seuils de matérialité, suivi des étapes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-feature">
                        <i class="fas fa-file-alt"></i>
                        <h5>Feuilles de travail</h5>
                        <p>Standardisées par cycle (ventes, achats, paie, etc.)</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-feature">
                        <i class="fas fa-search"></i>
                        <h5>Détection d'anomalies</h5>
                        <p>Tests Benford, doublons, dates atypiques automatiques</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-feature">
                        <i class="fas fa-envelope"></i>
                        <h5>Circularisation</h5>
                        <p>Envoi et suivi des confirmations externes</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-feature">
                        <i class="fas fa-file-pdf"></i>
                        <h5>Rapports d'audit</h5>
                        <p>Modèles préformatés et export PDF</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-feature">
                        <i class="fas fa-shield-alt"></i>
                        <h5>Sécurité & Conformité</h5>
                        <p>Journal d'audit, signatures, normes ISA/OHADA</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="footer">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> DELTA Audit - Plateforme de gestion d'audit financier</p>
            <small>Conforme aux normes ISA et OHADA</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>