<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();
$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $engagementId = (int)$_POST['engagement_id'];
    
    if (($handle = fopen($file, 'r')) !== false) {
        // Lire l'en-tête
        $headers = fgetcsv($handle, 1000, ';');
        
        // Sauvegarder l'import
        $stmt = $pdo->prepare("INSERT INTO gl_imports (engagement_id, filename, uploaded_by) VALUES (?, ?, ?)");
        $stmt->execute([$engagementId, $_FILES['csv_file']['name'], $_SESSION['user_id']]);
        $importId = $pdo->lastInsertId();
        
        $count = 0;
        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            if (count($data) >= 5) {
                $stmt = $pdo->prepare("INSERT INTO gl_entries 
                                      (gl_import_id, entry_date, journal, account, description, debit, credit, ref) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $importId,
                    date('Y-m-d', strtotime($data[0])),
                    $data[1] ?? '',
                    $data[2] ?? '',
                    $data[3] ?? '',
                    (float)str_replace(',', '.', $data[4] ?? 0),
                    (float)str_replace(',', '.', $data[5] ?? 0),
                    $data[6] ?? ''
                ]);
                $count++;
            }
        }
        fclose($handle);
        $message = "$count écritures importées avec succès !";
    }
}

$missions = $pdo->query("SELECT e.id, ent.name as entity_name, e.fiscal_year 
                         FROM engagements e 
                         LEFT JOIN entities ent ON e.entity_id = ent.id 
                         ORDER BY e.id DESC")->fetchAll();
?>