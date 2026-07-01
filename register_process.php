<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error'] = 'Tous les champs sont obligatoires';
    redirect('register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error'] = 'Le mot de passe doit contenir au moins 8 caractères';
    redirect('register.php');
    exit;
}

try {
    $pdo = getDB();

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Cet email est déjà utilisé';
        redirect('register.php');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'auditeur', 'active')");

    if ($stmt->execute([$name, $email, $hashedPassword])) {
        $_SESSION['success'] = 'Compte créé avec succès ! Connectez-vous.';
        redirect('login.php');
    } else {
        $_SESSION['error'] = 'Erreur lors de la création du compte';
        redirect('register.php');
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur technique : ' . $e->getMessage();
    redirect('register.php');
}
exit;