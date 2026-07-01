<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM entities WHERE id = ?");
    $stmt->execute([$id]);
    $entity = $stmt->fetch();
    
    if ($entity) {
        echo json_encode($entity);
    } else {
        echo json_encode(['error' => 'Entité non trouvée']);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;