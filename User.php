<?php
declare(strict_types=1);

final class User {
  public static function findByEmail(string $email): ?array {
    $st = DB::pdo()->prepare('SELECT * FROM users WHERE email=:e AND status="active" LIMIT 1');
    $st->execute([':e'=>$email]);
    $row = $st->fetch(\PDO::FETCH_ASSOC);
    return $row ?: null;
  }
}
