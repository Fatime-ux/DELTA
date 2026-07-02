<?php
declare(strict_types=1);

// ============================================================
// CONFIGURATION DELTA AUDIT - CORRIGÉE
// ============================================================

// --- Constantes de base ---
const APP_URL = 'http://localhost/DELTA/';  // <-- IMPORTANT : avec le dossier
const DB_HOST = '127.0.0.1';
const DB_NAME = 'DELTA';
const DB_USER = 'root';
const DB_PASS = '';

// --- Démarrer la session ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// FONCTIONS UTILITAIRES
// ============================================================

// Connexion PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    return $pdo;
}

// Générer une URL complète
function url($path = '') {
    return APP_URL . ltrim($path, '/');
}

// Redirection avec URL complète
function redirect($path = '') {
    header('Location: ' . url($path));
    exit;
}

// Échapper pour HTML
function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Formater une date
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '-';
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch(Exception $e) {
        return $date;
    }
}

// Formater un montant
function formatCurrency($amount) {
    if ($amount === null) return '-';
    return number_format((float)$amount, 0, ',', ' ') . ' FCFA';
}

// Badge de statut
function getStatusBadge($status) {
    $map = [
        'plan' => 'info', 
        'fieldwork' => 'warning', 
        'review' => 'primary', 
        'closed' => 'success',
        'en_cours' => 'warning', 
        'a_reviser' => 'danger', 
        'validee' => 'success',
        'envoye' => 'info', 
        'recu' => 'success', 
        'en_attente' => 'secondary',
        'ouvert' => 'warning',
        'en_revue' => 'primary',
        'clos' => 'success'
    ];
    $label = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $label . '">' . escape($status) . '</span>';
}

// Badge de sévérité
function getSeverityBadge($severity) {
    $map = [
        'mineur' => 'info', 
        'significatif' => 'warning', 
        'majeur' => 'danger'
    ];
    $label = $map[$severity] ?? 'secondary';
    return '<span class="badge bg-' . $label . '">' . escape($severity) . '</span>';
}

// Journalisation
function logAction($userId, $action, $entityType, $entityId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO journals (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $entityType, $entityId]);
    } catch(Exception $e) {
        // Log silencieux
        return false;
    }
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Exiger la connexion
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
        exit;
    }
}

// Exiger un rôle spécifique
function requireRole($role) {
    requireLogin();
    $roles = ['auditeur' => 1, 'manager' => 2, 'associe' => 3, 'admin' => 4];
    if (!isset($_SESSION['user_role']) || $roles[$_SESSION['user_role']] < $roles[$role]) {
        redirect('dashboard.php?error=permission_denied');
        exit;
    }
}

// Récupérer l'utilisateur courant
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch(Exception $e) {
        return null;
    }
}

// Générer un token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Messages flash
function setFlash($message, $type = 'success') {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Afficher le flash
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $class . ' alert-dismissible fade show">';
        echo '<i class="fas fa-' . ($flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle') . ' me-2"></i>';
        echo escape($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}
?>
