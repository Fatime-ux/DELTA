<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$message = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ============================================================
// TRAITEMENT DE LA SIGNATURE
// ============================================================
if (isset($_GET['sign']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'associe') {
            $_SESSION['error'] = 'Seul un associé ou un administrateur peut signer un rapport';
        } else {
            $stmt = $pdo->prepare("UPDATE reports 
                                  SET signed_by_partner = ?, 
                                      signature_datetime = NOW() 
                                  WHERE id = ? AND signed_by_partner IS NULL");
            if ($stmt->execute([$_SESSION['user_id'], $id])) {
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success'] = 'Rapport signé avec succès !';
                    logAction($_SESSION['user_id'], 'sign_report', 'report', $id);
                } else {
                    $_SESSION['error'] = 'Ce rapport est déjà signé ou n\'existe pas';
                }
            } else {
                $_SESSION['error'] = 'Erreur lors de la signature';
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    }
    header('Location: reports.php');
    exit;
}

// ============================================================
// AFFICHAGE DU PDF POUR IMPRESSION
// ============================================================
if (isset($_GET['pdf']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT r.*, 
                                      e.name as entity_name, 
                                      eng.fiscal_year,
                                      u.name as partner_name 
                               FROM reports r 
                               LEFT JOIN engagements eng ON r.engagement_id = eng.id 
                               LEFT JOIN entities e ON eng.entity_id = e.id 
                               LEFT JOIN users u ON r.signed_by_partner = u.id 
                               WHERE r.id = ?");
        $stmt->execute([$id]);
        $report = $stmt->fetch();
        
        if (!$report) {
            $_SESSION['error'] = 'Rapport non trouvé';
            header('Location: reports.php');
            exit;
        }
        
        // Afficher le rapport en version imprimable
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rapport d'audit - <?= htmlspecialchars($report['entity_name']) ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: auto; color: #333; }
                .header { text-align: center; border-bottom: 3px solid #667eea; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #667eea; font-size: 28px; }
                .header .subtitle { color: #666; font-size: 14px; margin-top: 5px; }
                .meta { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
                .meta p { margin: 5px 0; }
                .meta strong { display: inline-block; width: 180px; }
                .content { margin: 30px 0; line-height: 1.8; }
                .content .report-body { 
                    white-space: pre-wrap; 
                    font-family: Arial, sans-serif; 
                    font-size: 14px; 
                    line-height: 1.8;
                    background: #fafafa;
                    padding: 20px;
                    border-radius: 8px;
                    border-left: 4px solid #667eea;
                    min-height: 200px;
                }
                .signature-section { margin-top: 50px; border-top: 2px solid #e0e0e0; padding-top: 30px; }
                .signature-box {
                    display: inline-block;
                    width: 45%;
                    margin: 10px;
                    padding: 20px;
                    border: 1px dashed #ccc;
                    border-radius: 8px;
                    text-align: center;
                }
                .signature-box .signature-line { border-bottom: 1px solid #333; width: 80%; margin: 10px auto; height: 50px; }
                .signature-box .signed { color: #28a745; font-weight: bold; }
                .footer { text-align: center; color: #999; font-size: 11px; margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; }
                .report-type {
                    display: inline-block;
                    padding: 4px 14px;
                    border-radius: 15px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .report-type.sans_reserve { background: #e8f5e9; color: #2e7d32; }
                .report-type.avec_reserve { background: #fff3e0; color: #e65100; }
                .report-type.refus { background: #fce4ec; color: #c62828; }
                .report-type.defavorable { background: #fce4ec; color: #b71c1c; }
                .no-print { text-align: center; margin-top: 30px; padding: 20px; }
                .no-print .btn { padding: 10px 30px; margin: 0 10px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; }
                .no-print .btn-primary { background: #667eea; color: white; }
                .no-print .btn-secondary { background: #6c757d; color: white; }
                .no-print .btn-success { background: #28a745; color: white; }
                .no-print .btn:hover { opacity: 0.8; }
                
                @media print {
                    .no-print { display: none !important; }
                    body { padding: 20px; }
                    .meta { background: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    .report-type { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📊 DELTA Audit</h1>
                <p class="subtitle">Cabinet d'audit financier</p>
                <p style="font-size: 12px; color: #999;">Conforme aux normes ISA et OHADA</p>
            </div>
            
            <h2 style="text-align: center; color: #1a1a2e;">Rapport d'audit</h2>
            
            <div class="meta">
                <p><strong>Entité auditée :</strong> <?= htmlspecialchars($report['entity_name']) ?></p>
                <p><strong>Exercice :</strong> <?= $report['fiscal_year'] ?></p>
                <p><strong>Type de rapport :</strong> 
                    <span class="report-type <?= $report['report_type'] ?>"><?= str_replace('_', ' ', $report['report_type']) ?></span>
                </p>
                <p><strong>Généré le :</strong> <?= date('d/m/Y à H:i', strtotime($report['generated_at'])) ?></p>
                <p><strong>Statut :</strong> <?= $report['signed_by_partner'] ? '✅ Signé' : '⏳ Non signé' ?></p>
                <?php if ($report['signed_by_partner']): ?>
                    <p><strong>Signé par :</strong> <?= htmlspecialchars($report['partner_name']) ?></p>
                    <p><strong>Date de signature :</strong> <?= date('d/m/Y à H:i', strtotime($report['signature_datetime'])) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="content">
                <h3>Rapport d'audit</h3>
                <div class="report-body">
                    <?= nl2br(htmlspecialchars($report['body'] ?? 'Aucun contenu rédigé pour ce rapport.')) ?>
                </div>
            </div>
            
            <div class="signature-section">
                <h3 style="color: #667eea;">Signatures</h3>
                <div style="display: flex; justify-content: space-around; flex-wrap: wrap;">
                    <div class="signature-box">
                        <p><strong>L'Auditeur</strong></p>
                        <div class="signature-line"></div>
                        <p style="font-size: 12px; color: #666;">Signature de l'auditeur</p>
                    </div>
                    <div class="signature-box">
                        <p><strong>L'Associé</strong></p>
                        <div class="signature-line"></div>
                        <p style="font-size: 12px; color: #666;">Signature de l'associé</p>
                        <?php if ($report['signed_by_partner']): ?>
                            <p class="signed">✅ Signé le <?= date('d/m/Y', strtotime($report['signature_datetime'])) ?></p>
                        <?php else: ?>
                            <p style="color: #f44336; font-size: 12px;">⏳ En attente de signature</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Rapport généré par DELTA Audit - <?= date('Y') ?></p>
                <p>Document confidentiel - Destiné uniquement aux parties autorisées</p>
            </div>
            
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> 📄 Imprimer / PDF
                </button>
                <a href="reports.php" class="btn btn-secondary">⬅ Retour</a>
                <?php if (!$report['signed_by_partner'] && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'associe')): ?>
                    <a href="?sign=<?= $report['id'] ?>" class="btn btn-success" onclick="return confirm('Confirmer la signature ?')">
                        ✍️ Signer
                    </a>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        exit;
        
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
        header('Location: reports.php');
        exit;
    }
}

// ============================================================
// RÉCUPÉRATION DES DONNÉES
// ============================================================

// Récupérer les missions
$missions = $pdo->query("SELECT eng.id, e.name as entity_name, eng.fiscal_year 
                         FROM engagements eng 
                         LEFT JOIN entities e ON eng.entity_id = e.id 
                         ORDER BY eng.created_at DESC")->fetchAll();

// Récupérer les rapports
$reports = $pdo->query("SELECT r.*, 
                               e.name as entity_name, 
                               eng.fiscal_year,
                               u.name as partner_name 
                        FROM reports r 
                        LEFT JOIN engagements eng ON r.engagement_id = eng.id 
                        LEFT JOIN entities e ON eng.entity_id = e.id 
                        LEFT JOIN users u ON r.signed_by_partner = u.id 
                        ORDER BY r.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Rapports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .table th { background: #f8f9fa; }
        
        .report-type {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .report-type.sans_reserve { background: #e8f5e9; color: #2e7d32; }
        .report-type.avec_reserve { background: #fff3e0; color: #e65100; }
        .report-type.refus { background: #fce4ec; color: #c62828; }
        .report-type.defavorable { background: #fce4ec; color: #b71c1c; }
        
        .signature-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .signature-badge.signe { background: #e8f5e9; color: #2e7d32; }
        .signature-badge.non_signe { background: #fce4ec; color: #c62828; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-file-pdf me-2 text-danger"></i>Rapports d'audit</h1>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <i class="fas fa-plus me-1"></i>Nouveau rapport
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

        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mission</th>
                            <th>Type</th>
                            <th>Généré le</th>
                            <th>Signé par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reports) > 0): ?>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= $report['id'] ?></td>
                                <td><?= htmlspecialchars($report['entity_name'] ?? 'N/A') ?> (<?= $report['fiscal_year'] ?>)</td>
                                <td>
                                    <span class="report-type <?= $report['report_type'] ?>">
                                        <?= str_replace('_', ' ', $report['report_type']) ?>
                                    </span>
                                </td>
                                <td><?= $report['generated_at'] ? date('d/m/Y H:i', strtotime($report['generated_at'])) : '-' ?></td>
                                <td>
                                    <?php if ($report['signed_by_partner']): ?>
                                        <span class="signature-badge signe">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?= htmlspecialchars($report['partner_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="signature-badge non_signe">
                                            <i class="fas fa-clock me-1"></i>Non signé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Voir / Télécharger PDF -->
                                    <a href="?pdf=<?= $report['id'] ?>" class="btn btn-sm btn-danger" title="Voir / Télécharger PDF" target="_blank">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                    
                                    <!-- Signer -->
                                    <?php if (!$report['signed_by_partner'] && ($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'associe')): ?>
                                        <a href="?sign=<?= $report['id'] ?>" class="btn btn-sm btn-success" 
                                           onclick="return confirm('Confirmer la signature de ce rapport ?')">
                                            <i class="fas fa-signature"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-file-pdf fa-2x text-muted mb-2 d-block"></i>
                                    Aucun rapport généré
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Création -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="report_save.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2 text-danger"></i>Générer un rapport</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Mission *</label>
                            <select name="engagement_id" class="form-select" required>
                                <option value="">Sélectionner...</option>
                                <?php foreach ($missions as $mission): ?>
                                    <option value="<?= $mission['id'] ?>">
                                        <?= htmlspecialchars($mission['entity_name'] ?? 'N/A') ?> - <?= $mission['fiscal_year'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type de rapport *</label>
                            <select name="report_type" class="form-select" required>
                                <option value="sans_reserve">✅ Sans réserve</option>
                                <option value="avec_reserve">⚠️ Avec réserve</option>
                                <option value="refus">❌ Refus de certification</option>
                                <option value="defavorable">🔴 Avis défavorable</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contenu du rapport</label>
                            <textarea name="body" class="form-control" rows="5" placeholder="Rédigez le rapport ici..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-file-pdf me-1"></i>Générer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>