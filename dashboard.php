<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();

// ============================================================
// STATISTIQUES
// ============================================================
$missions_total = $pdo->query("SELECT COUNT(*) FROM engagements")->fetchColumn();
$missions_en_cours = $pdo->query("SELECT COUNT(*) FROM engagements WHERE status IN ('plan','fieldwork','review')")->fetchColumn();
$wp_total = $pdo->query("SELECT COUNT(*) FROM workpapers")->fetchColumn();
$entities_total = $pdo->query("SELECT COUNT(*) FROM entities")->fetchColumn();
$observations_total = $pdo->query("SELECT COUNT(*) FROM observations")->fetchColumn();

$userRole = $_SESSION['user_role'] ?? 'auditeur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --dark: #1a1a2e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .sidebar {
            background: var(--dark);
            min-height: 100vh;
            padding: 20px 0;
            color: white;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar .logo { text-align: center; padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .logo i { font-size: 32px; color: var(--primary); }
        .sidebar .logo h4 { margin-top: 10px; font-weight: 700; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 12px 25px;
            border-radius: 10px;
            margin: 5px 10px;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(102, 126, 234, 0.2);
            color: white;
        }
        .sidebar .nav-link i { margin-right: 12px; width: 20px; }
        .sidebar .nav-link.admin-link {
            background: rgba(102, 126, 234, 0.2);
            color: white;
            border-left: 3px solid #667eea;
            font-weight: 600;
        }
        .sidebar .nav-link.logout-link {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 15px;
            padding-top: 15px;
            color: #ff6b6b;
        }
        .sidebar .nav-link.logout-link:hover {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }
        
        .main-content { margin-left: 250px; padding: 30px; }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
            text-align: center;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .stat-card .number { font-size: 36px; font-weight: 700; }
        .stat-card .label { color: #666; font-size: 14px; margin-top: 5px; }
        .stat-card .icon { font-size: 32px; margin-bottom: 10px; }
        
        .stat-card a { text-decoration: none; color: inherit; display: block; }
        
        .card-custom {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .card-custom h5 { font-weight: 600; color: var(--dark); margin-bottom: 20px; }
        
        .topbar {
            background: white;
            padding: 15px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 10px 25px; border-radius: 10px; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        
        .admin-badge {
            background: #667eea;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-logout:hover { background: #c82333; color: white; }
        
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- ============================================================ -->
    <!-- SIDEBAR AVEC DÉCONNEXION -->
    <!-- ============================================================ -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-chart-line"></i>
            <h4>DELTA</h4>
            <small>Audit Management</small>
        </div>
        <nav class="nav flex-column mt-4">
            <a class="nav-link active" href="dashboard.php"><i class="fas fa-home"></i> Tableau de bord</a>
            <a class="nav-link" href="missions.php"><i class="fas fa-briefcase"></i> Missions</a>
            <a class="nav-link" href="entities.php"><i class="fas fa-building"></i> Entités</a>
            <a class="nav-link" href="workpapers.php"><i class="fas fa-file-alt"></i> Feuilles de travail</a>
            <a class="nav-link" href="observations.php"><i class="fas fa-exclamation-triangle"></i> Observations</a>
            <a class="nav-link" href="circularization.php"><i class="fas fa-envelope"></i> Circularisation</a>
            <a class="nav-link" href="reports.php"><i class="fas fa-file-pdf"></i> Rapports</a>
            
            <!-- ============================================================ -->
            <!-- ADMINISTRATION - Toujours visible -->
            <!-- ============================================================ -->
            <a class="nav-link admin-link" href="admin_users.php">
                <i class="fas fa-users-cog"></i> Administration
                <span style="background: #ffc107; color: #1a1a2e; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 5px;">ADMIN</span>
            </a>
            
            <!-- ============================================================ -->
            <!-- DÉCONNEXION -->
            <!-- ============================================================ -->
            <a class="nav-link logout-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN CONTENT -->
    <!-- ============================================================ -->
    <div class="main-content">
        <!-- Topbar avec bouton déconnexion -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Bonjour, <?= escape($_SESSION['user_name'] ?? 'Utilisateur') ?></h5>
                <small class="text-muted">
                    Rôle: <?= escape($_SESSION['user_role'] ?? 'auditeur') ?>
                    <?php if ($userRole === 'admin'): ?>
                        <span class="admin-badge"><i class="fas fa-crown me-1"></i>Admin</span>
                    <?php endif; ?>
                </small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary bg-opacity-10 text-primary p-2">
                    <i class="fas fa-calendar me-1"></i> <?= date('d/m/Y') ?>
                </span>
                <a href="logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                </a>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- STATISTIQUES -->
        <!-- ============================================================ -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon text-primary"><i class="fas fa-briefcase"></i></div>
                    <div class="number" style="color: #667eea;"><?= $missions_total ?></div>
                    <div class="label">Missions totales</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon text-warning"><i class="fas fa-spinner"></i></div>
                    <div class="number" style="color: #ffc107;"><?= $missions_en_cours ?></div>
                    <div class="label">En cours</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="icon text-success"><i class="fas fa-file-alt"></i></div>
                    <div class="number" style="color: #28a745;"><?= $wp_total ?></div>
                    <div class="label">Feuilles de travail</div>
                </div>
            </div>
            <div class="col-md-3">
                <a href="entities.php" style="text-decoration: none; color: inherit;">
                    <div class="stat-card" style="border: 2px solid #667eea; background: #f8f9ff;">
                        <div class="icon text-info"><i class="fas fa-building"></i></div>
                        <div class="number" style="color: #17a2b8;"><?= $entities_total ?></div>
                        <div class="label">Entités auditées <i class="fas fa-arrow-right ms-1" style="color: #667eea;"></i></div>
                    </div>
                </a>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- RÉSUMÉ -->
        <!-- ============================================================ -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card-custom">
                    <h5><i class="fas fa-file-alt me-2 text-primary"></i>Résumé des missions</h5>
                    <p><strong>Total :</strong> <?= $missions_total ?> missions</p>
                    <p><strong>En cours :</strong> <?= $missions_en_cours ?> missions</p>
                    <p><strong>Feuilles de travail :</strong> <?= $wp_total ?> feuilles</p>
                    <a href="missions.php" class="btn btn-primary-custom mt-2">
                        <i class="fas fa-arrow-right me-1"></i>Voir les missions
                    </a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-custom">
                    <h5><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Observations</h5>
                    <p><strong>Total :</strong> <?= $observations_total ?> observations</p>
                    <p><strong>Entités :</strong> <?= $entities_total ?> entités auditées</p>
                    <a href="observations.php" class="btn btn-primary-custom mt-2">
                        <i class="fas fa-arrow-right me-1"></i>Voir les observations
                    </a>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ACTIONS RAPIDES -->
        <!-- ============================================================ -->
        <div class="card-custom mt-4">
            <h5><i class="fas fa-bolt me-2 text-warning"></i>Actions rapides</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="missions.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-plus me-1"></i>Nouvelle mission
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="entities.php" class="btn btn-outline-success w-100">
                        <i class="fas fa-building me-1"></i>Nouvelle entité
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="workpapers.php" class="btn btn-outline-warning w-100">
                        <i class="fas fa-file-alt me-1"></i>Feuille de travail
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="reports.php" class="btn btn-outline-danger w-100">
                        <i class="fas fa-file-pdf me-1"></i>Générer rapport
                    </a>
                </div>
            </div>
        </div>
        
        <!-- ============================================================ -->
        <!-- ACCÈS ADMINISTRATION -->
        <!-- ============================================================ -->
        <div class="card-custom mt-3" style="background: #f8f9ff; border: 2px solid #667eea;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="fas fa-users-cog me-2 text-primary"></i>Administration</h5>
                    <small class="text-muted">Gestion des utilisateurs, rôles et sauvegardes</small>
                </div>
                <a href="admin_users.php" class="btn btn-primary-custom">
                    <i class="fas fa-arrow-right me-1"></i>Accéder
                </a>
            </div>
        </div>
        
        <!-- ============================================================ -->
        <!-- BOUTON DÉCONNEXION EN BAS DE PAGE -->
        <!-- ============================================================ -->
        <div class="text-center mt-4">
            <a href="logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt me-2"></i>Se déconnecter
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>