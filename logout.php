<?php
require_once 'config.php';

if (isLoggedIn()) {
    logAction($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id']);
}

session_destroy();
redirect('index.php');
exit;