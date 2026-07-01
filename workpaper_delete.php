<?php
require_once 'config.php';
requireLogin();

// Vérifier que l'ID est passé dans l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID de feuille de travail manquant';
    header('Location: workpapers.php');
    exit;
}

$id = (int)$_GET['id'];

if (!$id) {
    $_SESSION['error'] = 'ID de feuille de travail invalide';
    header('Location: workpapers.php');
    exit;
}

try {
    $pdo = getDB();
    
    // Vérifier que la feuille existe
    $stmt = $pdo->prepare("SELECT id, title FROM workpapers WHERE id = ?");
    $stmt->execute([$id]);
    $workpaper = $stmt->fetch();
    
    if (!$workpaper) {
        $_SESSION['error'] = 'Feuille de travail non trouvée';
        header('Location: workpapers.php');
        exit;
    }
    
    // Supprimer la feuille de travail
    $stmt = $pdo->prepare("DELETE FROM workpapers WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'Feuille de travail "' . htmlspecialchars($workpaper['title']) . '" supprimée avec succès !';
    } else {
        $_SESSION['error'] = 'Erreur lors de la suppression';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

// Rediriger vers la liste des feuilles
header('Location: workpapers.php');
exit;