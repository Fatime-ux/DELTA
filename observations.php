<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$wpId = (int)($_GET['workpaper_id'] ?? 0);

// Récupérer les observations
$where = [];
$params = [];

if ($wpId > 0) {
    $where[] = "o.workpaper_id = ?";
    $params[] = $wpId;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT o.*, u.name as proposer_name, w.title as wp_title 
        FROM observations o 
        LEFT JOIN users u ON o.proposer_id = u.id 
        LEFT JOIN workpapers w ON o.workpaper_id = w.id 
        $whereClause 
        ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$observations = $stmt->fetchAll();

// Feuilles de travail pour le filtre
$workpapers = $pdo->query("SELECT id, title FROM workpapers ORDER BY title")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Observations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Observations</h1>
            <div>
                <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#obsModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle observation
                </button>
            </div>
        </div>

        <!-- Filtre -->
        <div class="card-custom">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <select name="workpaper_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les feuilles</option>
                        <?php foreach ($workpapers as $wp): ?>
                            <option value="<?= $wp['id'] ?>" <?= $wpId == $wp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wp['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($wpId > 0): ?>
                    <div class="col-md-6 text-end">
                        <a href="<?= url('observations.php') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Réinitialiser
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Liste des observations -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Feuille de travail</th>
                            <th>Description</th>
                            <th>Sévérité</th>
                            <th>Statut</th>
                            <th>Proposé par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($observations) > 0): ?>
                            <?php foreach ($observations as $obs): ?>
                            <tr>
                                <td><?= $obs['id'] ?></td>
                                <td><?= htmlspecialchars($obs['wp_title'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars(substr($obs['description'], 0, 100)) ?>...</td>
                                <td><?= getSeverityBadge($obs['severity']) ?></td>
                                <td><?= getStatusBadge($obs['status']) ?></td>
                                <td><?= htmlspecialchars($obs['proposer_name'] ?? 'N/A') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="alert('Fonctionnalité en cours')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                    Aucune observation trouvée
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Création Observation -->
    <div class="modal fade" id="obsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="<?= url('observation_save.php') ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2 text-danger"></i>Nouvelle observation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Feuille de travail *</label>
                            <select name="workpaper_id" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($workpapers as $wp): ?>
                                    <option value="<?= $wp['id'] ?>" <?= $wpId == $wp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($wp['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Décrivez l'observation..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sévérité *</label>
                            <select name="severity" class="form-select" required>
                                <option value="mineur">Mineur</option>
                                <option value="significatif">Significatif</option>
                                <option value="majeur">Majeur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom">
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