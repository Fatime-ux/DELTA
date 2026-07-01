<?php
require_once 'config.php';
requireLogin();

function detectBenfordAnomalies($glImportId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT debit, credit FROM gl_entries WHERE gl_import_id = ?");
    $stmt->execute([$glImportId]);
    $entries = $stmt->fetchAll();
    
    $digits = array_fill(1, 9, 0);
    $total = 0;
    
    foreach ($entries as $entry) {
        $amount = $entry['debit'] > 0 ? $entry['debit'] : $entry['credit'];
        if ($amount > 0) {
            $firstDigit = (int)substr((string)$amount, 0, 1);
            if ($firstDigit >= 1 && $firstDigit <= 9) {
                $digits[$firstDigit]++;
                $total++;
            }
        }
    }
    
    // Calcul des fréquences
    $benfordExpected = [1 => 30.1, 2 => 17.6, 3 => 12.5, 4 => 9.7, 5 => 7.9, 6 => 6.7, 7 => 5.8, 8 => 5.1, 9 => 4.6];
    $results = [];
    
    foreach ($digits as $digit => $count) {
        $observed = $total > 0 ? ($count / $total) * 100 : 0;
        $expected = $benfordExpected[$digit];
        $diff = abs($observed - $expected);
        $results[$digit] = [
            'observed' => round($observed, 2),
            'expected' => $expected,
            'diff' => round($diff, 2),
            'anomaly' => $diff > 5
        ];
    }
    
    return $results;
}

function detectDuplicates($glImportId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT account, description, debit, credit, COUNT(*) as count 
                           FROM gl_entries 
                           WHERE gl_import_id = ? 
                           GROUP BY account, description, debit, credit 
                           HAVING COUNT(*) > 1");
    $stmt->execute([$glImportId]);
    return $stmt->fetchAll();
}
?>