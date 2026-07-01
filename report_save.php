<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('reports.php');
    exit;
}

$engagementId = (int)$_POST['engagement_id'];
$reportType = $_POST['report_type'];
$body = trim($_POST['body'] ?? '');

if (empty($engagementId) || empty($reportType)) {
    $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis';
    redirect('reports.php');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO reports (engagement_id, report_type, body, generated_at) 
                           VALUES (?, ?, ?, NOW())");

    if ($stmt->execute([$engagementId, $reportType, $body])) {
        $reportId = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_report', 'report', $reportId);
        $_SESSION['success'] = 'Rapport généré avec succès !';
    } else {
        $_SESSION['error'] = 'Erreur lors de la génération';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

redirect('reports.php');
exit;