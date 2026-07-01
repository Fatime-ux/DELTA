<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('missions.php');
    exit;
}

// Récupération des données
$entityId = (int)$_POST['entity_id'];
$fiscalYear = (int)$_POST['fiscal_year'];
$startDate = $_POST['start_date'];
$endDate = $_POST['end_date'];
$partnerId = $_POST['partner_id'] ? (int)$_POST['partner_id'] : null;
$managerId = $_POST['manager_id'] ? (int)$_POST['manager_id'] : null;

// Les 3 seuils de matérialité
$materialityGlobal = (float)$_POST['materiality_global'];
$materialityPerformance = !empty($_POST['materiality_performance']) ? (float)$_POST['materiality_performance'] : $materialityGlobal * 0.7;
$triviality = !empty($_POST['triviality']) ? (float)$_POST['triviality'] : $materialityGlobal * 0.05;

$status = $_POST['status'] ?? 'plan';

// Validation
if (empty($entityId) || empty($fiscalYear) || empty($startDate) || empty($endDate) || empty($materialityGlobal)) {
    $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis (entité, exercice, dates, matérialité globale)';
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
    
    $stmt = $pdo->prepare("INSERT INTO engagements 
                          (entity_id, fiscal_year, start_date, end_date, partner_id, manager_id, 
                           materiality_global, materiality_performance, triviality, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([
        $entityId, 
        $fiscalYear, 
        $startDate, 
        $endDate, 
        $partnerId, 
        $managerId, 
        $materialityGlobal, 
        $materialityPerformance, 
        $triviality,
        $status
    ])) {
        $missionId = $pdo->lastInsertId();
        $_SESSION['success'] = 'Mission créée avec succès ! Seuils de matérialité enregistrés.';
    } else {
        $_SESSION['error'] = 'Erreur lors de la création de la mission';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

redirect('missions.php');
exit;