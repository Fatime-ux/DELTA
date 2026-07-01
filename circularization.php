<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$message = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// ============================================================
// TRAITEMENT DE L'ENVOI D'EMAIL
// ============================================================
if (isset($_GET['send']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // Récupérer les informations de la circularisation
        $stmt = $pdo->prepare("SELECT c.*, e.entity_name 
                               FROM circularizations c 
                               LEFT JOIN engagements eng ON c.engagement_id = eng.id 
                               LEFT JOIN entities e ON eng.entity_id = e.id 
                               WHERE c.id = ?");
        $stmt->execute([$id]);
        $circ = $stmt->fetch();
        
        if (!$circ) {
            $_SESSION['error'] = 'Circularisation non trouvée';
        } elseif (empty($circ['recipient_email'])) {
            $_SESSION['error'] = 'Aucun email renseigné pour ce destinataire';
        } else {
            // ============================================================
            // CONSTRUCTION DE L'EMAIL
            // ============================================================
            $to = $circ['recipient_email'];
            $subject = "DELTA Audit - Demande de confirmation - " . $circ['entity_name'];
            
            // Corps de l'email (version HTML)
            $htmlContent = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .header { background: #667eea; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; }
                    .info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                    .info strong { display: inline-block; width: 150px; }
                    .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; margin-top: 20px; }
                    .btn { background: #667eea; color: white; padding: 10px 25px; text-decoration: none; border-radius: 5px; display: inline-block; }
                    .btn:hover { background: #764ba2; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>DELTA Audit</h2>
                    <p>Demande de confirmation</p>
                </div>
                <div class="content">
                    <p>Bonjour,</p>
                    <p>Dans le cadre de la mission d\'audit de <strong>' . $circ['entity_name'] . '</strong>, nous vous prions de bien vouloir confirmer les informations suivantes :</p>
                    
                    <div class="info">
                        <p><strong>Type :</strong> ' . ucfirst($circ['type']) . '</p>
                        <p><strong>Destinataire :</strong> ' . htmlspecialchars($circ['recipient_name']) . '</p>
                        <p><strong>Montant :</strong> ' . number_format($circ['amount'], 0, ',', ' ') . ' FCFA</p>
                        <p><strong>Référence :</strong> #CIRC-' . str_pad($circ['id'], 6, '0', STR_PAD_LEFT) . '</p>
                    </div>
                    
                    <p>Veuillez nous confirmer :</p>
                    <ul>
                        <li>✔️ Que ce montant est <strong>correct</strong></li>
                        <li>⚠️ Qu\'il existe un <strong>écart</strong> (précisez le montant)</li>
                        <li>❌ Que vous ne reconnaissez pas ce montant</li>
                    </ul>
                    
                    <p style="margin: 25px 0;">Pour répondre, cliquez sur le lien ci-dessous :</p>
                    
                    <div style="text-align: center;">
                        <a href="' . APP_URL . 'confirm_circularization.php?token=' . $circ['token'] . '" class="btn">
                            <i class="fas fa-check-circle"></i> Confirmer maintenant
                        </a>
                    </div>
                    
                    <p style="margin-top: 25px;">Lien direct : <a href="' . APP_URL . 'confirm_circularization.php?token=' . $circ['token'] . '">' . APP_URL . 'confirm_circularization.php?token=' . $circ['token'] . '</a></p>
                    
                    <p>Merci de votre collaboration.</p>
                    <p>Cordialement,<br><strong>Cabinet DELTA Audit</strong></p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' DELTA Audit - Conforme aux normes ISA et OHADA</p>
                    <p>Ce message est généré automatiquement, merci de ne pas y répondre directement.</p>
                </div>
            </body>
            </html>';
            
            // Version texte simple
            $textContent = "DELTA Audit - Demande de confirmation\n\n";
            $textContent .= "Bonjour,\n\n";
            $textContent .= "Dans le cadre de la mission d'audit de " . $circ['entity_name'] . ", nous vous prions de bien vouloir confirmer les informations suivantes :\n\n";
            $textContent .= "Type : " . ucfirst($circ['type']) . "\n";
            $textContent .= "Destinataire : " . $circ['recipient_name'] . "\n";
            $textContent .= "Montant : " . number_format($circ['amount'], 0, ',', ' ') . " FCFA\n";
            $textContent .= "Référence : #CIRC-" . str_pad($circ['id'], 6, '0', STR_PAD_LEFT) . "\n\n";
            $textContent .= "Veuillez confirmer ce montant via le lien suivant :\n";
            $textContent .= APP_URL . "confirm_circularization.php?token=" . $circ['token'] . "\n\n";
            $textContent .= "Merci de votre collaboration.\n";
            $textContent .= "Cordialement,\nCabinet DELTA Audit";
            
            // Headers pour l'email HTML
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: DELTA Audit <audit@delta-audit.com>\r\n";
            $headers .= "Reply-To: audit@delta-audit.com\r\n";
            
            // Envoi de l'email
            $sent = mail($to, $subject, $htmlContent, $headers);
            
            if ($sent) {
                // Mettre à jour le statut
                $stmt = $pdo->prepare("UPDATE circularizations SET status = 'envoye', sent_date = NOW() WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Demande envoyée avec succès à ' . $circ['recipient_email'];
            } else {
                $_SESSION['error'] = 'Erreur lors de l\'envoi de l\'email. Vérifiez la configuration du serveur mail.';
            }
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
    }
    
    header('Location: circularization.php');
    exit;
}

// ============================================================
// RÉCUPÉRATION DES DONNÉES
// ============================================================

// Récupérer les missions
$missions = $pdo->query("SELECT e.id, ent.name as entity_name, e.fiscal_year 
                         FROM engagements e 
                         LEFT JOIN entities ent ON e.entity_id = ent.id 
                         ORDER BY e.created_at DESC")->fetchAll();

// Récupérer les circularisations
$circs = $pdo->query("SELECT c.*, ent.name as entity_name, e.fiscal_year
                      FROM circularizations c 
                      LEFT JOIN engagements e ON c.engagement_id = e.id 
                      LEFT JOIN entities ent ON e.entity_id = ent.id 
                      ORDER BY c.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DELTA Audit - Circularisation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; }
        .container { max-width: 1400px; margin: auto; }
        .card-custom { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .btn-primary-custom { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; }
        .btn-primary-custom:hover { color: white; transform: translateY(-2px); }
        .btn-success-custom { background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; }
        .btn-success-custom:hover { color: white; transform: translateY(-2px); }
        .table th { background: #f8f9fa; }
        
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-badge.en_attente { background: #fff3e0; color: #e65100; }
        .status-badge.envoye { background: #e3f2fd; color: #1565c0; }
        .status-badge.relance { background: #fce4ec; color: #c62828; }
        .status-badge.recu { background: #e8f5e9; color: #2e7d32; }
        .status-badge.confirme { background: #e8f5e9; color: #2e7d32; }
        .status-badge.ecart { background: #fce4ec; color: #c62828; }
        
        .no-email-warning {
            font-size: 11px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-envelope me-2 text-primary"></i>Circularisation</h1>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#circModal">
                    <i class="fas fa-plus me-1"></i>Nouvelle demande
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

        <!-- Liste -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mission</th>
                            <th>Type</th>
                            <th>Destinataire</th>
                            <th>Email</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($circs) > 0): ?>
                            <?php foreach ($circs as $circ): ?>
                            <tr>
                                <td><?= $circ['id'] ?></td>
                                <td><?= htmlspecialchars($circ['entity_name'] ?? 'N/A') ?> (<?= $circ['fiscal_year'] ?>)</td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($circ['type']) ?></span></td>
                                <td><?= htmlspecialchars($circ['recipient_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($circ['recipient_email'] ?? '-') ?>
                                    <?php if (empty($circ['recipient_email'])): ?>
                                        <span class="no-email-warning">⚠️</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $circ['amount'] ? formatCurrency($circ['amount']) : '-' ?></td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'en_attente' => ['label' => 'En attente', 'class' => 'en_attente'],
                                        'envoye' => ['label' => 'Envoyé', 'class' => 'envoye'],
                                        'relance' => ['label' => 'Relancé', 'class' => 'relance'],
                                        'recu' => ['label' => 'Reçu', 'class' => 'recu'],
                                        'confirme' => ['label' => 'Confirmé', 'class' => 'confirme'],
                                        'ecart' => ['label' => 'Écart', 'class' => 'ecart']
                                    ];
                                    $status = $circ['status'] ?? 'en_attente';
                                    $s = $statusLabels[$status] ?? $statusLabels['en_attente'];
                                    ?>
                                    <span class="status-badge <?= $s['class'] ?>"><?= $s['label'] ?></span>
                                </td>
                                <td>
                                    <?php if ($circ['status'] == 'en_attente' || $circ['status'] == 'relance'): ?>
                                        <?php if (!empty($circ['recipient_email'])): ?>
                                            <a href="?send=<?= $circ['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Envoyer la demande à <?= htmlspecialchars($circ['recipient_email']) ?> ?')">
                                                <i class="fas fa-paper-plane"></i> Envoyer
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Aucun email renseigné">
                                                <i class="fas fa-paper-plane"></i> Envoyer
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($circ['status'] == 'envoye' || $circ['status'] == 'relance'): ?>
                                        <button class="btn btn-sm btn-warning" onclick="alert('Fonctionnalité en cours')">
                                            <i class="fas fa-redo"></i> Relancer
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($circ['status'] == 'recu'): ?>
                                        <button class="btn btn-sm btn-info" onclick="alert('Fonctionnalité en cours')">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                    Aucune demande de circularisation
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Création -->
    <div class="modal fade" id="circModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="circularization_save.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2 text-primary"></i>Nouvelle demande</h5>
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
                            <label class="form-label">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="clients">🏢 Clients</option>
                                <option value="banques">🏦 Banques</option>
                                <option value="fournisseurs">🏭 Fournisseurs</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Destinataire *</label>
                            <input type="text" name="recipient_name" class="form-control" placeholder="Nom du tiers" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="recipient_email" class="form-control" placeholder="email@example.com">
                            <small class="text-muted">Obligatoire pour envoyer la demande par email</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant</label>
                            <input type="number" name="amount" class="form-control" placeholder="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date de la demande *</label>
                            <input type="date" name="request_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
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