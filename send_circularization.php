<?php
require_once 'config.php';
requireLogin();

function sendCircularizationEmail($recipient, $token, $missionName) {
    $subject = "DELTA Audit - Demande de confirmation";
    $link = APP_URL . "confirm_circularization.php?token=" . $token;
    
    $message = "Bonjour,\n\n";
    $message .= "Dans le cadre de la mission d'audit de $missionName, nous vous prions de bien vouloir confirmer les informations suivantes.\n\n";
    $message .= "Veuillez cliquer sur le lien ci-dessous pour répondre :\n";
    $message .= $link . "\n\n";
    $message .= "Cordialement,\n";
    $message .= "Cabinet DELTA Audit";
    
    $headers = "From: audit@delta-audit.com\r\n";
    $headers .= "Reply-To: audit@delta-audit.com\r\n";
    
    return mail($recipient, $subject, $message, $headers);
}

// Utilisation
if (isset($_GET['send']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM circularizations WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $circ = $stmt->fetch();
    
    if ($circ && sendCircularizationEmail($circ['recipient_email'], $circ['token'], $circ['mission_name'])) {
        $pdo->prepare("UPDATE circularizations SET status = 'envoye' WHERE id = ?")->execute([$circ['id']]);
        $_SESSION['success'] = 'Email envoyé avec succès !';
    } else {
        $_SESSION['error'] = 'Erreur lors de l\'envoi de l\'email';
    }
    header('Location: circularization.php');
    exit;
}
?>