<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$message = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "ent.name LIKE ?";
    $params[] = "%$search%";
}
if ($statusFilter) {
    $where[] = "e.status = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Total pour pagination
$countSql = "SELECT COUNT(*) FROM engagements e 
             LEFT JOIN entities ent ON e.entity_id = ent.id 
             $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Récupération des missions avec les 3 seuils
$sql = "SELECT e.*, 
        ent.name as entity_name, 
        u.name as manager_name,
        u2.name as partner_name
        FROM engagements e 
        LEFT JOIN entities ent ON e.entity_id = ent.id 
        LEFT JOIN users u ON e.manager_id = u.id 
        LEFT JOIN users u2 ON e.partner_id = u2.id 
        $whereClause 
        ORDER BY e.created_at DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$missions = $stmt->fetchAll();

// Liste des entités pour le formulaire
$entities = $pdo->query("SELECT id, name FROM entities ORDER BY name")->fetchAll();
$users = $pdo->query("SELECT id, name, role FROM users WHERE status = 'active' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Missions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .table th { background: #f8f9fa; }
        
        /* Badges de matérialité */
        .badge-materiality {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin: 1px 2px;
        }
        .badge-materiality.global { background: #667eea; color: white; }
        .badge-materiality.performance { background: #28a745; color: white; }
        .badge-materiality.triviality { background: #6c757d; color: white; }
        .badge-materiality i { margin-right: 3px; }
        
        .materiality-info {
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-briefcase me-2 text-primary"></i>Gestion des missions</h1>
            <div>
                <a href="<?= url('dashboard.php') ?>" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#missionModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle mission
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

        <!-- Filtres -->
        <div class="card-custom">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="plan" <?= $statusFilter == 'plan' ? 'selected' : '' ?>>Plan</option>
                        <option value="fieldwork" <?= $statusFilter == 'fieldwork' ? 'selected' : '' ?>>Travail terrain</option>
                        <option value="review" <?= $statusFilter == 'review' ? 'selected' : '' ?>>Revue</option>
                        <option value="closed" <?= $statusFilter == 'closed' ? 'selected' : '' ?>>Clôturée</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i>Filtrer
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?= url('missions.php') ?>" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo me-1"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Liste des missions -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Entité</th>
                            <th>Exercice</th>
                            <th>Manager</th>
                            <th>Statut</th>
                            <th>Seuils de matérialité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($missions) > 0): ?>
                            <?php foreach ($missions as $mission): ?>
                            <tr>
                                <td><?= $mission['id'] ?></td>
                                <td><strong><?= htmlspecialchars($mission['entity_name'] ?? 'N/A') ?></strong></td>
                                <td><?= $mission['fiscal_year'] ?></td>
                                <td><?= htmlspecialchars($mission['manager_name'] ?? 'Non assigné') ?></td>
                                <td><?= getStatusBadge($mission['status']) ?></td>
                                <td>
                                    <div>
                                        <span class="badge-materiality global">
                                            <i class="fas fa-globe"></i> G: <?= number_format($mission['materiality_global'] ?? 0, 0, ',', ' ') ?>
                                        </span>
                                        <span class="badge-materiality performance">
                                            <i class="fas fa-bullseye"></i> P: <?= number_format($mission['materiality_performance'] ?? 0, 0, ',', ' ') ?>
                                        </span>
                                        <span class="badge-materiality triviality">
                                            <i class="fas fa-thumbtack"></i> T: <?= number_format($mission['triviality'] ?? 0, 0, ',', ' ') ?>
                                        </span>
                                    </div>
                                    <div class="materiality-info">
                                        <small>Global > Performance > Trivialité</small>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= url('workpapers.php?mission_id=' . $mission['id']) ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <button class="btn btn-sm btn-warning" onclick="editMission(<?= $mission['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                    Aucune mission trouvée
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL CRÉATION AVEC LES 3 SEUILS -->
    <!-- ============================================================ -->
    <div class="modal fade" id="missionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="<?= url('mission_save.php') ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2 text-primary"></i>Nouvelle mission d'audit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Informations générales -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Entité auditée *</label>
                                    <select name="entity_id" class="form-select" required>
                                        <option value="">Sélectionner...</option>
                                        <?php foreach ($entities as $entity): ?>
                                            <option value="<?= $entity['id'] ?>"><?= htmlspecialchars($entity['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Exercice *</label>
                                    <input type="number" name="fiscal_year" class="form-control" value="<?= date('Y') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date de début *</label>
                                    <input type="date" name="start_date" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date de fin *</label>
                                    <input type="date" name="end_date" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <!-- Équipe -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Associé responsable</label>
                                    <select name="partner_id" class="form-select">
                                        <option value="">Non assigné</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Manager</label>
                                    <select name="manager_id" class="form-select">
                                        <option value="">Non assigné</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-bold text-primary"><i class="fas fa-calculator me-2"></i>Seuils de matérialité (ISA 320 & 450)</h6>
                        <p class="text-muted small">Basés sur les normes ISA 320 (matérialité) et ISA 450 (trivialité)</p>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-primary">Matérialité globale *</label>
                                    <div class="input-group">
                                        <input type="number" name="materiality_global" id="materiality_global" 
                                               class="form-control" placeholder="100000" required>
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                    <small class="text-muted">Seuil principal pour l'ensemble des états financiers (ISA 320)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-success">Matérialité de performance</label>
                                    <div class="input-group">
                                        <input type="number" name="materiality_performance" id="materiality_performance" 
                                               class="form-control" placeholder="70000">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                    <small class="text-muted">Seuil réduit pour les tests de détails (ISA 320)</small>
                                    <div class="form-text text-success">
                                        <i class="fas fa-lightbulb me-1"></i>Suggestions : 50-75% de la matérialité globale
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-secondary">Trivialité</label>
                                    <div class="input-group">
                                        <input type="number" name="triviality" id="triviality" 
                                               class="form-control" placeholder="5000">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                    <small class="text-muted">Seuil en dessous duquel les erreurs sont négligeables (ISA 450)</small>
                                    <div class="form-text text-secondary">
                                        <i class="fas fa-lightbulb me-1"></i>Suggestions : 3-5% de la matérialité globale
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statut -->
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="plan">Plan</option>
                                <option value="fieldwork">Travail terrain</option>
                                <option value="review">Revue</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-save me-1"></i>Créer la mission
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL ÉDITION MISSION -->
    <!-- ============================================================ -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="<?= url('mission_update.php') ?>" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2 text-warning"></i>Modifier la mission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="editModalBody">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2">Chargement des données...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calcul des seuils
        document.addEventListener('DOMContentLoaded', function() {
            const globalInput = document.getElementById('materiality_global');
            const performanceInput = document.getElementById('materiality_performance');
            const trivialityInput = document.getElementById('triviality');
            
            if (globalInput) {
                globalInput.addEventListener('input', function() {
                    const value = parseFloat(this.value) || 0;
                    
                    // Auto-calcul de la matérialité de performance (70%)
                    if (performanceInput && !performanceInput.dataset.manual) {
                        performanceInput.value = Math.round(value * 0.7);
                    }
                    
                    // Auto-calcul de la trivialité (5%)
                    if (trivialityInput && !trivialityInput.dataset.manual) {
                        trivialityInput.value = Math.round(value * 0.05);
                    }
                });
            }
            
            // Marquer les champs comme modifiés manuellement
            if (performanceInput) {
                performanceInput.addEventListener('focus', function() {
                    this.dataset.manual = 'true';
                });
            }
            if (trivialityInput) {
                trivialityInput.addEventListener('focus', function() {
                    this.dataset.manual = 'true';
                });
            }
        });

        // Éditer une mission
        function editMission(id) {
            const modal = new bootstrap.Modal(document.getElementById('editModal'));
            const body = document.getElementById('editModalBody');
            
            fetch('mission_get.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    let html = '';
                    html += '<input type="hidden" name="id" value="' + data.id + '">';
                    
                    html += '<div class="row">';
                    html += '<div class="col-md-6"><div class="mb-3">';
                    html += '<label class="form-label">Entité</label>';
                    html += '<input type="text" class="form-control" value="' + (data.entity_name || 'N/A') + '" disabled>';
                    html += '</div></div>';
                    html += '<div class="col-md-6"><div class="mb-3">';
                    html += '<label class="form-label">Exercice</label>';
                    html += '<input type="text" class="form-control" value="' + data.fiscal_year + '" disabled>';
                    html += '</div></div></div>';
                    
                    html += '<hr><h6 class="fw-bold text-primary"><i class="fas fa-calculator me-2"></i>Seuils de matérialité</h6>';
                    
                    html += '<div class="row">';
                    html += '<div class="col-md-4"><div class="mb-3">';
                    html += '<label class="form-label text-primary">Matérialité globale</label>';
                    html += '<input type="number" name="materiality_global" class="form-control" value="' + (data.materiality_global || 0) + '" required>';
                    html += '</div></div>';
                    html += '<div class="col-md-4"><div class="mb-3">';
                    html += '<label class="form-label text-success">Matérialité de performance</label>';
                    html += '<input type="number" name="materiality_performance" class="form-control" value="' + (data.materiality_performance || 0) + '">';
                    html += '</div></div>';
                    html += '<div class="col-md-4"><div class="mb-3">';
                    html += '<label class="form-label text-secondary">Trivialité</label>';
                    html += '<input type="number" name="triviality" class="form-control" value="' + (data.triviality || 0) + '">';
                    html += '</div></div></div>';
                    
                    html += '<div class="mb-3">';
                    html += '<label class="form-label">Statut</label>';
                    html += '<select name="status" class="form-select">';
                    html += '<option value="plan" ' + (data.status == 'plan' ? 'selected' : '') + '>Plan</option>';
                    html += '<option value="fieldwork" ' + (data.status == 'fieldwork' ? 'selected' : '') + '>Travail terrain</option>';
                    html += '<option value="review" ' + (data.status == 'review' ? 'selected' : '') + '>Revue</option>';
                    html += '<option value="closed" ' + (data.status == 'closed' ? 'selected' : '') + '>Clôturée</option>';
                    html += '</select>';
                    html += '</div>';
                    
                    body.innerHTML = html;
                })
                .catch(error => {
                    body.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des données</div>';
                });
            
            modal.show();
        }
    </script>
</body>
</html>