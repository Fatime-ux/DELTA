<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('observations.php');
    exit;
}

$workpaperId = (int)$_POST['workpaper_id'];
$description = trim($_POST['description']);
$severity = $_POST['severity'];

if (empty($workpaperId) || empty($description) || empty($severity)) {
    $_SESSION['error'] = 'Tous les champs sont obligatoires';
    redirect('observations.php');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO observations (workpaper_id, description, severity, proposer_id, status) 
                           VALUES (?, ?, ?, ?, 'ouvert')");

    if ($stmt->execute([$workpaperId, $description, $severity, $_SESSION['user_id']])) {
        $obsId = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_observation', 'observation', $obsId);
        $_SESSION['success'] = 'Observation créée avec succès !';
    } else {
        $_SESSION['error'] = 'Erreur lors de la création';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

redirect('observations.php' . ($workpaperId ? '?workpaper_id=' . $workpaperId : ''));
exit;