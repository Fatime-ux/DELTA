<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$missionId = (int)($_GET['mission_id'] ?? 0);
$message = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Récupérer les missions pour le filtre
$missions = $pdo->query("SELECT e.id, ent.name as entity_name, e.fiscal_year 
                         FROM engagements e 
                         LEFT JOIN entities ent ON e.entity_id = ent.id 
                         ORDER BY e.created_at DESC")->fetchAll();

// Récupérer les feuilles de travail
$where = [];
$params = [];

if ($missionId > 0) {
    $where[] = "w.engagement_id = ?";
    $params[] = $missionId;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT w.*, u.name as assigned_name, ent.name as entity_name, e.fiscal_year 
        FROM workpapers w 
        LEFT JOIN users u ON w.assigned_to = u.id 
        LEFT JOIN engagements e ON w.engagement_id = e.id 
        LEFT JOIN entities ent ON e.entity_id = ent.id 
        $whereClause 
        ORDER BY w.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workpapers = $stmt->fetchAll();

// Cycles pour le formulaire
$cycles = $pdo->query("SELECT id, name, code FROM audit_cycles ORDER BY name")->fetchAll();

$users = $pdo->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name")->fetchAll();

// Affichage des cycles
$cycleDisplayMap = [
    'ventes' => 'Ventes',
    'achats' => 'Achats',
    'paie' => 'Paie',
    'tresorerie' => 'Trésorerie',
    'immobilisations' => 'Immobilisations',
    'stocks' => 'Stocks'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Feuilles de travail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .table th { background: #f8f9fa; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.en_cours { background: #fff3e0; color: #e65100; }
        .status-badge.a_reviser { background: #fce4ec; color: #c62828; }
        .status-badge.validee { background: #e8f5e9; color: #2e7d32; }
        
        .cycle-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .cycle-badge.ventes { background: #e3f2fd; color: #1565c0; }
        .cycle-badge.achats { background: #fce4ec; color: #c62828; }
        .cycle-badge.paie { background: #f3e5f5; color: #6a1b9a; }
        .cycle-badge.tresorerie { background: #e0f7fa; color: #00695c; }
        .cycle-badge.immobilisations { background: #e8f5e9; color: #2e7d32; }
        .cycle-badge.stocks { background: #fff3e0; color: #e65100; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-file-alt me-2 text-primary"></i>Feuilles de travail</h1>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#wpModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle feuille
                </button>
            </div>
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

        <!-- Filtre par mission -->
        <div class="card-custom">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <select name="mission_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les missions</option>
                        <?php foreach ($missions as $mission): ?>
                            <option value="<?= $mission['id'] ?>" <?= $missionId == $mission['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mission['entity_name'] ?? 'N/A') ?> - <?= $mission['fiscal_year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($missionId > 0): ?>
                    <div class="col-md-6 text-end">
                        <a href="workpapers.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>Réinitialiser
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Liste des feuilles -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mission</th>
                            <th>Cycle</th>
                            <th>Titre</th>
                            <th>Assigné à</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($workpapers) > 0): ?>
                            <?php foreach ($workpapers as $wp): ?>
                            <tr>
                                <td><?= $wp['id'] ?></td>
                                <td><?= htmlspecialchars($wp['entity_name'] ?? 'N/A') ?> (<?= $wp['fiscal_year'] ?>)</td>
                                <td>
                                    <?php
                                    $cycleValue = $wp['cycle'] ?? '';
                                    $cycleDisplay = $cycleDisplayMap[$cycleValue] ?? $cycleValue;
                                    if (empty($cycleDisplay) || $cycleDisplay == '') {
                                        $cycleDisplay = 'Non défini';
                                    }
                                    $cycleClass = $cycleValue;
                                    ?>
                                    <span class="cycle-badge <?= $cycleClass ?>">
                                        <i class="fas fa-sync-alt me-1"></i>
                                        <?= htmlspecialchars(ucfirst($cycleDisplay)) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($wp['title']) ?></td>
                                <td><?= htmlspecialchars($wp['assigned_name'] ?? 'Non assigné') ?></td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'en_cours' => ['label' => 'En cours', 'class' => 'en_cours'],
                                        'a_reviser' => ['label' => 'À réviser', 'class' => 'a_reviser'],
                                        'validee' => ['label' => 'Validée', 'class' => 'validee']
                                    ];
                                    $status = $wp['status'] ?? 'en_cours';
                                    $s = $statusLabels[$status] ?? $statusLabels['en_cours'];
                                    ?>
                                    <span class="status-badge <?= $s['class'] ?>"><?= $s['label'] ?></span>
                                </td>
                                <td>
                                    <a href="observations.php?workpaper_id=<?= $wp['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- ============================================================ -->
                                    <!-- BOUTON ÉDITION CORRIGÉ -->
                                    <!-- ============================================================ -->
                                    <a href="workpaper_edit.php?id=<?= $wp['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- ============================================================ -->
                                    <!-- BOUTON SUPPRESSION -->
                                    <!-- ============================================================ -->
                                    <a href="workpaper_delete.php?id=<?= $wp['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('⚠️ Supprimer cette feuille ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                    Aucune feuille de travail trouvée
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Création WP -->
    <div class="modal fade" id="wpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="workpaper_save.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2 text-primary"></i>Nouvelle feuille de travail</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Mission *</label>
                            <select name="engagement_id" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($missions as $mission): ?>
                                    <option value="<?= $mission['id'] ?>" <?= $missionId == $mission['id'] ? 'selected' : '' ?>>
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
                                    <option value="<?= $cycle['code'] ?>"><?= htmlspecialchars($cycle['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Titre *</label>
                            <input type="text" name="title" class="form-control" placeholder="Ex: Test des ventes N-1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assigner à</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Non assigné</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="en_cours">En cours</option>
                                <option value="a_reviser">À réviser</option>
                                <option value="validee">Validée</option>
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