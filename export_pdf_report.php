<?php
require_once 'config.php';
requireLogin();

// Installation de TCPDF via Composer ou téléchargement manuel
// require_once('vendor/autoload.php');

function generatePDFReport($reportId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT r.*, e.entity_name, e.fiscal_year, u.name as partner_name 
                           FROM reports r 
                           LEFT JOIN engagements e ON r.engagement_id = e.id 
                           LEFT JOIN users u ON r.signed_by_partner = u.id 
                           WHERE r.id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();
    
    if (!$report) return false;
    
    // Création du PDF avec TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('DELTA Audit');
    $pdf->SetAuthor('DELTA Audit');
    $pdf->SetTitle('Rapport d\'audit - ' . $report['entity_name']);
    $pdf->SetSubject('Rapport d\'audit');
    $pdf->SetMargins(15, 20, 15);
    $pdf->AddPage();
    
    // En-tête
    $html = '<h1 style="color:#667eea; text-align:center;">DELTA Audit</h1>';
    $html .= '<h2 style="text-align:center;">Rapport d\'audit financier</h2>';
    $html .= '<hr>';
    $html .= '<p><strong>Entité :</strong> ' . $report['entity_name'] . '</p>';
    $html .= '<p><strong>Exercice :</strong> ' . $report['fiscal_year'] . '</p>';
    $html .= '<p><strong>Type :</strong> ' . $report['report_type'] . '</p>';
    $html .= '<p><strong>Généré le :</strong> ' . date('d/m/Y H:i') . '</p>';
    $html .= '<hr>';
    $html .= '<h3>Rapport</h3>';
    $html .= '<p>' . nl2br($report['body'] ?? 'Aucun contenu.') . '</p>';
    $html .= '<hr>';
    $html .= '<p style="text-align:center; color:#666; font-size:10px;">';
    $html .= 'Rapport généré par DELTA Audit - Conforme aux normes ISA et OHADA';
    $html .= '</p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Sauvegarde
    $filename = 'rapport_' . $reportId . '_' . date('Ymd') . '.pdf';
    $path = 'uploads/pdf/' . $filename;
    $pdf->Output($path, 'F');
    
    // Mise à jour en BDD
    $pdo->prepare("UPDATE reports SET generated_pdf = ? WHERE id = ?")->execute([$filename, $reportId]);
    
    return $filename;
}
?>