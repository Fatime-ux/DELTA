<?php
// Place ce fichier à la racine DELTA, ouvre [localhost](http://localhost/DELTA/generate_hash.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pwd = $_POST['pwd'] ?? '';
  if ($pwd === '') { echo 'Mot de passe vide'; exit; }
  $hash = password_hash($pwd, PASSWORD_BCRYPT);
  echo '<p>Hash:</p><pre style="white-space:pre-wrap">'.$hash.'</pre>';
  exit;
}
?>
<!doctype html><meta charset="utf-8"><title>Générer hash</title>
<form method="post">
  <label>Mot de passe à hasher<br><input type="text" name="pwd" required></label><br><br>
  <button>Générer</button>
</form>
