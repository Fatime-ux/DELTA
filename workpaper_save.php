<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('workpapers.php');
    exit;
}

$engagementId = (int)$_POST['engagement_id'];
$cycle = $_POST['cycle'];
$title = trim($_POST['title']);
$assignedTo = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;
$status = $_POST['status'] ?? 'en_cours';

if (empty($engagementId) || empty($cycle) || empty($title)) {
    $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis';
    redirect('workpapers.php');
    exit;
}

try {
    $pdo = getDB();
    
    // ============================================================
    // CONVERSION DU CYCLE EN MINUSCULES POUR L'ENUM
    // ============================================================
    
    // Table de correspondance : Code/Nom -> Valeur ENUM (minuscules)
    $cycleMapping = [
        'VEN' => 'ventes',
        'ventes' => 'ventes',
        'ACH' => 'achats',
        'achats' => 'achats',
        'PAI' => 'paie',
        'paie' => 'paie',
        'TRE' => 'tresorerie',
        'tresorerie' => 'tresorerie',
        'IMM' => 'immobilisations',
        'immobilisations' => 'immobilisations',
        'STO' => 'stocks',
        'stocks' => 'stocks'
    ];
    
    // Convertir en minuscules
    $cycleLower = strtolower(trim($cycle));
    
    // Si c'est un nom ou code, le convertir
    $cycleToStore = $cycleMapping[$cycle] ?? $cycleMapping[$cycleLower] ?? 'autres';
    
    // Vérifier que la valeur est valide pour l'ENUM
    $validEnums = ['ventes', 'achats', 'paie', 'tresorerie', 'immobilisations', 'stocks'];
    if (!in_array($cycleToStore, $validEnums)) {
        $cycleToStore = 'autres';
    }
    
    $stmt = $pdo->prepare("INSERT INTO workpapers (engagement_id, cycle, title, assigned_to, status) 
                           VALUES (?, ?, ?, ?, ?)");

    if ($stmt->execute([$engagementId, $cycleToStore, $title, $assignedTo, $status])) {
        $wpId = $pdo->lastInsertId();
        $_SESSION['success'] = 'Feuille de travail créée avec succès !';
    } else {
        $_SESSION['error'] = 'Erreur lors de la création';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

redirect('workpapers.php' . ($engagementId ? '?mission_id=' . $engagementId : ''));
exit;