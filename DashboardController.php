<?php
declare(strict_types=1);

final class DashboardController {
  public function index(): string {
    $pdo = DB::pdo();

    $progress = $pdo->query('SELECT * FROM vw_engagement_progress ORDER BY engagement_id DESC')
                    ->fetchAll(\PDO::FETCH_ASSOC);

    $severity = $pdo->query('SELECT * FROM vw_obs_severity_by_engagement ORDER BY engagement_id DESC')
                    ->fetchAll(\PDO::FETCH_ASSOC);

    $obs = $pdo->query('SELECT o.*, w.title AS workpaper_title
                        FROM observations o
                        JOIN workpapers w ON w.id = o.workpaper_id
                        ORDER BY o.created_at DESC
                        LIMIT 10')
               ->fetchAll(\PDO::FETCH_ASSOC);

    $journals = $pdo->query('SELECT * FROM journals ORDER BY created_at DESC LIMIT 10')
                    ->fetchAll(\PDO::FETCH_ASSOC);

    $title='Dashboard mission';
    ob_start(); require __DIR__.'/dashboard.php'; $content=ob_get_clean();
    ob_start(); require __DIR__.'/layout.php'; return ob_get_clean();
  }
}
