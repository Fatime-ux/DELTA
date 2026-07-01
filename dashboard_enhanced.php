<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();

// Statistiques avancées
$stats = [
    'total_missions' => $pdo->query("SELECT COUNT(*) FROM engagements")->fetchColumn(),
    'missions_plan' => $pdo->query("SELECT COUNT(*) FROM engagements WHERE status = 'plan'")->fetchColumn(),
    'missions_fieldwork' => $pdo->query("SELECT COUNT(*) FROM engagements WHERE status = 'fieldwork'")->fetchColumn(),
    'missions_review' => $pdo->query("SELECT COUNT(*) FROM engagements WHERE status = 'review'")->fetchColumn(),
    'missions_closed' => $pdo->query("SELECT COUNT(*) FROM engagements WHERE status = 'closed'")->fetchColumn(),
    'wp_total' => $pdo->query("SELECT COUNT(*) FROM workpapers")->fetchColumn(),
    'wp_draft' => $pdo->query("SELECT COUNT(*) FROM workpapers WHERE status = 'draft'")->fetchColumn(),
    'wp_in_progress' => $pdo->query("SELECT COUNT(*) FROM workpapers WHERE status = 'in_progress'")->fetchColumn(),
    'wp_validated' => $pdo->query("SELECT COUNT(*) FROM workpapers WHERE status = 'validated'")->fetchColumn(),
    'observations_total' => $pdo->query("SELECT COUNT(*) FROM observations")->fetchColumn(),
    'observations_open' => $pdo->query("SELECT COUNT(*) FROM observations WHERE status != 'closed'")->fetchColumn(),
    'observations_closed' => $pdo->query("SELECT COUNT(*) FROM observations WHERE status = 'closed'")->fetchColumn(),
    'circularizations_total' => $pdo->query("SELECT COUNT(*) FROM circularizations")->fetchColumn(),
    'circularizations_pending' => $pdo->query("SELECT COUNT(*) FROM circularizations WHERE status = 'pending'")->fetchColumn(),
];

// Calcul du taux d'avancement global
$advancement = $stats['wp_total'] > 0 ? round(($stats['wp_validated'] / $stats['wp_total']) * 100, 1) : 0;
?>

<!-- Dans le HTML, ajoutez -->
<div class="row">
    <div class="col-md-4">
        <div class="stat-card">
            <h3><?= $advancement ?>%</h3>
            <p>Taux d'avancement global</p>
            <div class="progress">
                <div class="progress-bar" style="width: <?= $advancement ?>%; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
            </div>
        </div>
    </div>
</div>