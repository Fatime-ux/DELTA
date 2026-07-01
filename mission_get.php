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
    $stmt = $pdo->prepare("SELECT e.*, ent.name as entity_name 
                           FROM engagements e 
                           LEFT JOIN entities ent ON e.entity_id = ent.id 
                           WHERE e.id = ?");
    $stmt->execute([$id]);
    $mission = $stmt->fetch();
    
    if ($mission) {
        // S'assurer que les 3 seuils sont présents
        $mission['materiality_global'] = $mission['materiality_global'] ?? $mission['materiality'] ?? 100000;
        $mission['materiality_performance'] = $mission['materiality_performance'] ?? $mission['materiality_global'] * 0.7;
        $mission['triviality'] = $mission['triviality'] ?? $mission['materiality_global'] * 0.05;
        
        echo json_encode($mission);
    } else {
        echo json_encode(['error' => 'Mission non trouvée']);
    }
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;