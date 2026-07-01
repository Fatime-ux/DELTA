<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('missions.php');
    exit;
}

$id = (int)$_POST['id'];
$materialityGlobal = (float)$_POST['materiality_global'];
$materialityPerformance = !empty($_POST['materiality_performance']) ? (float)$_POST['materiality_performance'] : $materialityGlobal * 0.7;
$triviality = !empty($_POST['triviality']) ? (float)$_POST['triviality'] : $materialityGlobal * 0.05;
$status = $_POST['status'];

// Validation
if (empty($id) || empty($materialityGlobal)) {
    $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis';
    redirect('missions.php');
    exit;
}

// Vérification de la cohérence des seuils
if ($materialityGlobal <= $materialityPerformance) {
    $_SESSION['error'] = 'La matérialité globale doit être supérieure à la matérialité de performance (ISA 320)';
    redirect('missions.php');
    exit;
}

if ($materialityPerformance <= $triviality) {
    $_SESSION['error'] = 'La matérialité de performance doit être supérieure à la trivialité (ISA 450)';
    redirect('missions.php');
    exit;
}

try {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("UPDATE engagements 
                          SET materiality_global = ?, 
                              materiality_performance = ?, 
                              triviality = ?, 
                              status = ?,
                              updated_at = NOW()
                          WHERE id = ?");

    if ($stmt->execute([$materialityGlobal, $materialityPerformance, $triviality, $status, $id])) {
        $_SESSION['success'] = 'Mission mise à jour avec succès ! Seuils de matérialité mis à jour.';
    } else {
        $_SESSION['error'] = 'Erreur lors de la mise à jour';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

redirect('missions.php');
exit;