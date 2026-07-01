<?php
declare(strict_types=1);

final class DB {
  private static ?\PDO $pdo = null;

  public static function pdo(): \PDO {
    if (!self::$pdo) {
      $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
      $opt = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
      ];
      try {
        self::$pdo = new \PDO($dsn, DB_USER, DB_PASS, $opt);
      } catch (\PDOException $e) {
        http_response_code(500);
        exit('Erreur DB');
      }
    }
    return self::$pdo;
  }
}
