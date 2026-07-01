Bonjour, <?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?> —
<a href="<?= APP_URL ?>/dashboard">Dashboard</a> |
<a href="<?= APP_URL ?>/gl-upload">Import GL</a> |
<form style="display:inline" method="post" action="<?= APP_URL ?>/logout">
  <button type="submit">Déconnexion</button>
</form>
