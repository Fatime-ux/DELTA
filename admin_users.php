<?php
require_once 'config.php';
requireLogin();

// Vérifier que l'utilisateur est admin
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'Accès refusé. Vous devez être administrateur.';
    header('Location: dashboard.php');
    exit;
}

$pdo = getDB();
$message = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ============================================================
// TRAITEMENT DES ACTIONS
// ============================================================

// Mise à jour du rôle
if (isset($_POST['update_role'])) {
    $userId = (int)$_POST['user_id'];
    $role = $_POST['role'];
    
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Vous ne pouvez pas modifier votre propre rôle !';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$role, $userId])) {
                $_SESSION['success'] = 'Rôle mis à jour avec succès !';
            } else {
                $_SESSION['error'] = 'Erreur lors de la mise à jour du rôle';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        }
    }
    header('Location: admin_users.php');
    exit;
}

// Activation/Désactivation
if (isset($_GET['toggle_status'])) {
    $userId = (int)$_GET['toggle_status'];
    
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Vous ne pouvez pas désactiver votre propre compte !';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active', 'disabled', 'active') WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $_SESSION['success'] = 'Statut modifié avec succès !';
            } else {
                $_SESSION['error'] = 'Erreur lors de la modification du statut';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        }
    }
    header('Location: admin_users.php');
    exit;
}

// Suppression d'un utilisateur
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = 'Vous ne pouvez pas supprimer votre propre compte !';
    } else {
        try {
            // Vérifier si l'utilisateur a des missions
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM engagements WHERE manager_id = ? OR partner_id = ?");
            $stmt->execute([$userId, $userId]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = 'Cet utilisateur est associé à des missions. Changez d\'abord ses missions.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$userId])) {
                    $_SESSION['success'] = 'Utilisateur supprimé avec succès !';
                } else {
                    $_SESSION['error'] = 'Erreur lors de la suppression';
                }
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        }
    }
    header('Location: admin_users.php');
    exit;
}

// Création d'un utilisateur
if (isset($_POST['create_user'])) {
    $name = trim($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = 'Tous les champs sont obligatoires';
    } elseif (strlen($password) < 8) {
        $_SESSION['error'] = 'Le mot de passe doit contenir au moins 8 caractères';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Cet email est déjà utilisé';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'active')");
                if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                    $_SESSION['success'] = 'Utilisateur créé avec succès !';
                } else {
                    $_SESSION['error'] = 'Erreur lors de la création';
                }
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        }
    }
    header('Location: admin_users.php');
    exit;
}

// ============================================================
// RÉCUPÉRATION DES DONNÉES
// ============================================================

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    'disabled' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'disabled'")->fetchColumn(),
    'admin' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
    'manager' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'manager'")->fetchColumn(),
    'auditeur' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'auditeur'")->fetchColumn(),
];

$roles = ['admin', 'associe', 'manager', 'auditeur'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .stat-card { background: white; border-radius: 10px; padding: 15px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stat-card .number { font-size: 24px; font-weight: 700; }
        .stat-card .label { color: #666; font-size: 13px; }
        .table th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users-cog me-2 text-primary"></i>Administration</h1>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="number text-primary"><?= $stats['total'] ?></div>
                    <div class="label">Total</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="number text-success"><?= $stats['active'] ?></div>
                    <div class="label">Actifs</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="number text-danger"><?= $stats['disabled'] ?></div>
                    <div class="label">Désactivés</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="number text-warning"><?= $stats['manager'] ?></div>
                    <div class="label">Managers</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="number text-info"><?= $stats['auditeur'] ?></div>
                    <div class="label">Auditeurs</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="number text-primary"><?= $stats['admin'] ?></div>
                    <div class="label">Admins</div>
                </div>
            </div>
        </div>

        <!-- Bouton Créer -->
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-users me-2"></i>Liste des utilisateurs</h5>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="fas fa-user-plus me-1"></i>Nouvel utilisateur
                </button>
            </div>
        </div>

        <!-- Tableau -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($user['name']) ?>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary ms-1">Vous</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="role" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()" <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?= $role ?>" <?= $user['role'] == $role ? 'selected' : '' ?>>
                                                    <?= ucfirst($role) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                </td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?toggle_status=<?= $user['id'] ?>" class="btn btn-sm <?= $user['status'] == 'active' ? 'btn-success' : 'btn-secondary' ?>" onclick="return confirm('Confirmer cette action ?')">
                                            <?= $user['status'] == 'active' ? 'Actif' : 'Désactivé' ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer définitivement cet utilisateur ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-users fa-2x text-muted mb-2 d-block"></i>
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sauvegarde -->
        <div class="card-custom">
            <h5><i class="fas fa-database me-2"></i>Sauvegarde</h5>
            <p class="text-muted small">Téléchargez une sauvegarde complète de la base de données</p>
            <a href="backup.php" class="btn btn-primary-custom">
                <i class="fas fa-download me-1"></i>Télécharger la sauvegarde
            </a>
        </div>
    </div>

    <!-- Modal Création -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-user-plus me-2 text-primary"></i>Nouvel utilisateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom complet *</label>
                            <input type="text" name="name" class="form-control" placeholder="Jean Dupont" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" placeholder="jean@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mot de passe * (min 8 caractères)</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" minlength="8" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rôle *</label>
                            <select name="role" class="form-select" required>
                                <option value="auditeur">Auditeur</option>
                                <option value="manager">Manager</option>
                                <option value="associe">Associé</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="create_user" class="btn btn-primary-custom">
                            <i class="fas fa-save me-1"></i>Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>