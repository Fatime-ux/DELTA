<?php
declare(strict_types=1);

final class Auth {
  public static function check(): bool { return isset($_SESSION['user']); }
  public static function login(array $u): void {
    $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
    session_regenerate_id(true);
  }
  public static function logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure']??false, $p['httponly']??true);
    }
    session_destroy();
  }
  public static function user(): ?array { return $_SESSION['user'] ?? null; }
}
