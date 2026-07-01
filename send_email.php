<?php
// Configuration de l'email avec PHPMailer
function sendEmail($to, $subject, $htmlContent, $textContent = null) {
    require_once 'vendor/autoload.php'; // Si vous utilisez Composer
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuration SMTP (exemple avec Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'votre_email@gmail.com';
        $mail->Password   = 'votre_mot_de_passe';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        $mail->setFrom('audit@delta-audit.com', 'DELTA Audit');
        $mail->addAddress($to);
        
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;
        $mail->AltBody = $textContent ?? strip_tags($htmlContent);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi : " . $mail->ErrorInfo);
        return false;
    }
}
?>