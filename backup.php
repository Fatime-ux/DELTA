<?php
require_once 'config.php';
requireRole('admin');

try {
    $pdo = getDB();

    // Récupérer toutes les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $output = "-- ========================================\n";
    $output .= "-- Sauvegarde DELTA Audit - " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- ========================================\n\n";

    foreach ($tables as $table) {
        // Structure
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch();
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $row['Create Table'] . ";\n\n";
        
        // Données
        $data = $pdo->query("SELECT * FROM `$table`");
        if ($data->rowCount() > 0) {
            $output .= "INSERT INTO `$table` VALUES\n";
            $rows = $data->fetchAll(PDO::FETCH_NUM);
            $values = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, $row);
                $values[] = "(" . implode(", ", $escaped) . ")";
            }
            $output .= implode(",\n", $values) . ";\n\n";
        }
    }

    // Headers pour téléchargement
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="delta_audit_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    header('Content-Length: ' . strlen($output));

    echo $output;
    exit;

} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur lors de la sauvegarde : ' . $e->getMessage();
    redirect('admin_users.php');
    exit;
}