<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Tous les champs sont obligatoires';
    redirect('login.php');
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        logAction($user['id'], 'login', 'user', $user['id']);
        redirect('dashboard.php');
    } else {
        $_SESSION['error'] = 'Email ou mot de passe incorrect';
        redirect('login.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur technique, veuillez réessayer';
    redirect('login.php');
}
exit;