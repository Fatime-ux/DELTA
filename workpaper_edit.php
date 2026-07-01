<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$message = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Récupérer l'ID de la feuille à modifier
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = 'ID de feuille de travail manquant';
    header('Location: workpapers.php');
    exit;
}

// Récupérer les données de la feuille
$stmt = $pdo->prepare("SELECT * FROM workpapers WHERE id = ?");
$stmt->execute([$id]);
$workpaper = $stmt->fetch();

if (!$workpaper) {
    $_SESSION['error'] = 'Feuille de travail non trouvée';
    header('Location: workpapers.php');
    exit;
}

// Récupérer les missions pour le formulaire
$missions = $pdo->query("SELECT e.id, ent.name as entity_name, e.fiscal_year 
                         FROM engagements e 
                         LEFT JOIN entities ent ON e.entity_id = ent.id 
                         ORDER BY e.created_at DESC")->fetchAll();

// Récupérer les cycles
$cycles = $pdo->query("SELECT id, name, code FROM audit_cycles ORDER BY name")->fetchAll();

// Récupérer les utilisateurs
$users = $pdo->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name")->fetchAll();

// Si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_workpaper'])) {
    $engagementId = (int)$_POST['engagement_id'];
    $cycle = $_POST['cycle'];
    $title = trim($_POST['title']);
    $assignedTo = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;
    $status = $_POST['status'];
    
    if (empty($engagementId) || empty($cycle) || empty($title)) {
        $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis';
    } else {
        try {
            // ============================================================
            // CONVERSION DU CYCLE EN MINUSCULES POUR L'ENUM
            // ============================================================
            
            // Table de correspondance : Code/Nom -> Valeur ENUM (minuscules)
            $cycleMapping = [
                'VEN' => 'ventes',
                'ventes' => 'ventes',
                'ACH' => 'achats',
                'achats' => 'achats',
                'PAI' => 'paie',
                'paie' => 'paie',
                'TRE' => 'tresorerie',
                'tresorerie' => 'tresorerie',
                'IMM' => 'immobilisations',
                'immobilisations' => 'immobilisations',
                'STO' => 'stocks',
                'stocks' => 'stocks'
            ];
            
            // Convertir en minuscules
            $cycleLower = strtolower(trim($cycle));
            
            // Si c'est un nom ou code, le convertir
            $cycleToStore = $cycleMapping[$cycle] ?? $cycleMapping[$cycleLower] ?? 'autres';
            
            // Vérifier que la valeur est valide pour l'ENUM
            $validEnums = ['ventes', 'achats', 'paie', 'tresorerie', 'immobilisations', 'stocks'];
            if (!in_array($cycleToStore, $validEnums)) {
                $cycleToStore = 'autres';
            }
            
            $stmt = $pdo->prepare("UPDATE workpapers 
                                  SET engagement_id = ?, cycle = ?, title = ?, assigned_to = ?, status = ? 
                                  WHERE id = ?");
            if ($stmt->execute([$engagementId, $cycleToStore, $title, $assignedTo, $status, $id])) {
                $_SESSION['success'] = 'Feuille de travail mise à jour avec succès !';
                logAction($_SESSION['user_id'], 'update_workpaper', 'workpaper', $id);
                header('Location: workpapers.php');
                exit;
            } else {
                $_SESSION['error'] = 'Erreur lors de la mise à jour';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        }
    }
    header('Location: workpaper_edit.php?id=' . $id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Modifier feuille de travail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .form-label { font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-edit me-2 text-warning"></i>Modifier la feuille de travail</h1>
            <a href="workpapers.php" class="btn btn-outline-secondary">
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

        <div class="card-custom">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Mission *</label>
                    <select name="engagement_id" class="form-select" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($missions as $mission): ?>
                            <option value="<?= $mission['id'] ?>" <?= $mission['id'] == $workpaper['engagement_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mission['entity_name'] ?? 'N/A') ?> - <?= $mission['fiscal_year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cycle *</label>
                    <select name="cycle" class="form-select" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($cycles as $cycle): ?>
                            <option value="<?= $cycle['code'] ?>" <?= strtolower($cycle['code']) == strtolower($workpaper['cycle']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cycle['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Valeur actuelle : <strong><?= htmlspecialchars($workpaper['cycle'] ?? 'Non défini') ?></strong>
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Titre *</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($workpaper['title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assigner à</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">Non assigné</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $user['id'] == $workpaper['assigned_to'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="en_cours" <?= $workpaper['status'] == 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="a_reviser" <?= $workpaper['status'] == 'a_reviser' ? 'selected' : '' ?>>À réviser</option>
                        <option value="validee" <?= $workpaper['status'] == 'validee' ? 'selected' : '' ?>>Validée</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="update_workpaper" class="btn btn-primary-custom">
                        <i class="fas fa-save me-1"></i>Mettre à jour
                    </button>
                    <a href="workpapers.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>