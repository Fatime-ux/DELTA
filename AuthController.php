<?php
declare(strict_types=1);

final class AuthController {
  public function showLogin(): string {
    $title='Connexion'; ob_start(); require __DIR__.'/login.php'; $content=ob_get_clean();
    ob_start(); require __DIR__.'/layout.php'; return ob_get_clean();
  }
  public function login(): string {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = User::findByEmail($email);
    if ($user && password_verify($password, $user['password_hash'])) {
      Auth::login($user);
      header('Location: '.APP_URL.'/dashboard'); exit;
    }
    $error='Identifiants invalides.'; $title='Connexion';
    ob_start(); require __DIR__.'/login.php'; $content=ob_get_clean();
    ob_start(); require __DIR__.'/layout.php'; return ob_get_clean();
  }
  public function logout(): void { Auth::logout(); header('Location: '.APP_URL.'/login'); exit; }
}
