<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$message = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ============================================================
// TRAITEMENT DES ACTIONS
// ============================================================

// Création d'une entité
if (isset($_POST['create_entity'])) {
    $name = trim($_POST['name']);
    $sector = trim($_POST['sector'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $ohada = isset($_POST['ohada_applicable']) ? 1 : 0;
    
    if (empty($name)) {
        $_SESSION['error'] = 'Le nom de l\'entité est obligatoire';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO entities (name, sector, country, ohada_applicable) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $sector, $country, $ohada])) {
                $_SESSION['success'] = 'Entité "' . htmlspecialchars($name) . '" créée avec succès !';
                logAction($_SESSION['user_id'], 'create_entity', 'entity', $pdo->lastInsertId());
            } else {
                $_SESSION['error'] = 'Erreur lors de la création';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        }
    }
    header('Location: entities.php');
    exit;
}

// Mise à jour d'une entité
if (isset($_POST['update_entity'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $sector = trim($_POST['sector'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $ohada = isset($_POST['ohada_applicable']) ? 1 : 0;
    
    if (empty($name)) {
        $_SESSION['error'] = 'Le nom de l\'entité est obligatoire';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE entities SET name = ?, sector = ?, country = ?, ohada_applicable = ? WHERE id = ?");
            if ($stmt->execute([$name, $sector, $country, $ohada, $id])) {
                $_SESSION['success'] = 'Entité mise à jour avec succès !';
                logAction($_SESSION['user_id'], 'update_entity', 'entity', $id);
            } else {
                $_SESSION['error'] = 'Erreur lors de la mise à jour';
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        }
    }
    header('Location: entities.php');
    exit;
}

// Suppression d'une entité
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Vérifier si l'entité est utilisée dans des missions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM engagements WHERE entity_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = 'Cette entité est utilisée dans des missions. Vous ne pouvez pas la supprimer.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM entities WHERE id = ?");
            if ($stmt->execute([$id])) {
                $_SESSION['success'] = 'Entité supprimée avec succès !';
                logAction($_SESSION['user_id'], 'delete_entity', 'entity', $id);
            } else {
                $_SESSION['error'] = 'Erreur lors de la suppression';
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    }
    header('Location: entities.php');
    exit;
}

// ============================================================
// RÉCUPÉRATION DES DONNÉES
// ============================================================

// Récupérer toutes les entités
$entities = $pdo->query("SELECT * FROM entities ORDER BY name")->fetchAll();

// Statistiques
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM entities")->fetchColumn(),
    'ohada' => $pdo->query("SELECT COUNT(*) FROM entities WHERE ohada_applicable = 1")->fetchColumn(),
    'missions' => $pdo->query("SELECT COUNT(DISTINCT entity_id) FROM engagements")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Entités auditées</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .table th { background: #f8f9fa; }
        .stat-card { background: white; border-radius: 10px; padding: 15px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stat-card .number { font-size: 24px; font-weight: 700; }
        .stat-card .label { color: #666; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-building me-2 text-primary"></i>Entités auditées</h1>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#entityModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle entité
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

        <!-- Statistiques -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number text-primary"><?= $stats['total'] ?></div>
                    <div class="label">Total entités</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number text-success"><?= $stats['ohada'] ?></div>
                    <div class="label">Sous OHADA</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number text-warning"><?= $stats['missions'] ?></div>
                    <div class="label">Entités avec missions</div>
                </div>
            </div>
        </div>

        <!-- Liste des entités -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Secteur</th>
                            <th>Pays</th>
                            <th>OHADA</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($entities) > 0): ?>
                            <?php foreach ($entities as $entity): ?>
                            <tr>
                                <td><?= $entity['id'] ?></td>
                                <td><strong><?= htmlspecialchars($entity['name']) ?></strong></td>
                                <td><?= htmlspecialchars($entity['sector'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($entity['country'] ?? '-') ?></td>
                                <td>
                                    <?php if ($entity['ohada_applicable']): ?>
                                        <span class="badge bg-success">✅ Oui</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">❌ Non</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editEntity(<?= $entity['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete=<?= $entity['id'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('⚠️ Supprimer cette entité ?\n\n<?= htmlspecialchars($entity['name']) ?>\nCette action est irréversible !')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-building fa-2x text-muted mb-2 d-block"></i>
                                    Aucune entité trouvée
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL CRÉATION ENTITÉ -->
    <!-- ============================================================ -->
    <div class="modal fade" id="entityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2 text-primary"></i>Nouvelle entité auditée</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'entité *</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: Société ABC SARL" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secteur d'activité</label>
                            <input type="text" name="sector" class="form-control" placeholder="Ex: Distribution, BTP, Service">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pays</label>
                            <input type="text" name="country" class="form-control" placeholder="Ex: Sénégal, Côte d'Ivoire">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="ohada_applicable" class="form-check-input" id="ohada" checked>
                            <label class="form-check-label" for="ohada">
                                <strong>OHADA applicable</strong>
                                <small class="text-muted d-block">L'entreprise est soumise au droit OHADA</small>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="create_entity" class="btn btn-primary-custom">
                            <i class="fas fa-save me-1"></i>Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL ÉDITION ENTITÉ -->
    <!-- ============================================================ -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2 text-warning"></i>Modifier l'entité</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'entité *</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secteur d'activité</label>
                            <input type="text" name="sector" id="edit_sector" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pays</label>
                            <input type="text" name="country" id="edit_country" class="form-control">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="ohada_applicable" class="form-check-input" id="edit_ohada">
                            <label class="form-check-label" for="edit_ohada">
                                <strong>OHADA applicable</strong>
                                <small class="text-muted d-block">L'entreprise est soumise au droit OHADA</small>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_entity" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEntity(id) {
            // Récupérer les données via AJAX
            fetch('entity_get.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_sector').value = data.sector || '';
                    document.getElementById('edit_country').value = data.country || '';
                    document.getElementById('edit_ohada').checked = data.ohada_applicable == 1;
                    
                    const modal = new bootstrap.Modal(document.getElementById('editModal'));
                    modal.show();
                })
                .catch(error => {
                    alert('Erreur lors du chargement des données');
                });
        }
    </script>
</body>
</html>