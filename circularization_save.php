<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('circularization.php');
    exit;
}

$engagementId = (int)$_POST['engagement_id'];
$type = $_POST['type'];
$recipientName = trim($_POST['recipient_name']);
$email = filter_var($_POST['recipient_email'] ?? '', FILTER_SANITIZE_EMAIL);
$amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
$requestDate = $_POST['request_date'];

if (empty($engagementId) || empty($type) || empty($recipientName) || empty($requestDate)) {
    $_SESSION['error'] = 'Tous les champs obligatoires doivent être remplis';
    redirect('circularization.php');
    exit;
}

try {
    $pdo = getDB();
    $token = generateToken(64);

    $stmt = $pdo->prepare("INSERT INTO circularizations 
                           (engagement_id, type, recipient_name, recipient_email, amount, request_date, status, token) 
                           VALUES (?, ?, ?, ?, ?, ?, 'en_attente', ?)");

    if ($stmt->execute([$engagementId, $type, $recipientName, $email, $amount, $requestDate, $token])) {
        $circId = $pdo->lastInsertId();
        logAction($_SESSION['user_id'], 'create_circularization', 'circularization', $circId);
        $_SESSION['success'] = 'Demande de circularisation créée avec succès !';
    } else {
        $_SESSION['error'] = 'Erreur lors de la création';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur : ' . $e->getMessage();
}

redirect('circularization.php');
exit;