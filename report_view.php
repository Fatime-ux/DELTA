<?php
require_once 'config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: reports.php');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT r.*, e.name as entity_name, e.fiscal_year, u.name as partner_name 
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Rapport #<?= $report['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .report-header { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .report-body { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .report-type {
            display: inline-block;
            padding: 5px 18px;
            border-radius: 20px;
            font-weight: 600;
        }
        .report-type.sans_reserve { background: #e8f5e9; color: #2e7d32; }
        .report-type.avec_reserve { background: #fff3e0; color: #e65100; }
        .report-type.refus { background: #fce4ec; color: #c62828; }
        .report-type.defavorable { background: #fce4ec; color: #b71c1c; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .content { white-space: pre-wrap; font-family: Arial, sans-serif; line-height: 1.8; }
        .signature-box { border: 1px dashed #ccc; border-radius: 10px; padding: 20px; text-align: center; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-file-pdf me-2 text-danger"></i>Rapport d'audit #<?= $report['id'] ?></h1>
            <div>
                <a href="reports.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <a href="?pdf=<?= $report['id'] ?>" class="btn btn-danger ms-2">
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </a>
            </div>
        </div>

        <div class="report-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="mb-3"><?= htmlspecialchars($report['entity_name']) ?></h3>
                    <p><strong>Exercice :</strong> <?= $report['fiscal_year'] ?></p>
                    <p><strong>Type :</strong> <span class="report-type <?= $report['report_type'] ?>"><?= str_replace('_', ' ', $report['report_type']) ?></span></p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Généré le :</strong> <?= date('d/m/Y H:i', strtotime($report['generated_at'])) ?></p>
                    <p><strong>Signé par :</strong> 
                        <?= $report['signed_by_partner'] ? htmlspecialchars($report['partner_name']) : '⏳ Non signé' ?>
                    </p>
                    <?php if ($report['signed_by_partner']): ?>
                        <p><strong>Signé le :</strong> <?= date('d/m/Y H:i', strtotime($report['signature_datetime'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="report-body">
            <h3 class="text-primary">Rapport d'audit</h3>
            <hr>
            <div class="content">
                <?= nl2br(htmlspecialchars($report['body'] ?? 'Aucun contenu rédigé pour ce rapport.')) ?>
            </div>
            
            <div class="signature-section mt-5">
                <h5>Signature</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="signature-box">
                            <p><strong>L'Associé</strong></p>
                            <div style="border-bottom: 1px solid #333; width: 80%; margin: 10px auto; height: 40px;"></div>
                            <?php if ($report['signed_by_partner']): ?>
                                <p class="text-success">✅ Signé le <?= date('d/m/Y', strtotime($report['signature_datetime'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted">⏳ En attente de signature</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="signature-box">
                            <p><strong>L'Auditeur</strong></p>
                            <div style="border-bottom: 1px solid #333; width: 80%; margin: 10px auto; height: 40px;"></div>
                            <p class="text-muted">⏳ En attente de signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>