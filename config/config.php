<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'car_rent'); // Changé de 'car_rent' à 'carrent'
define('DB_USER', 'root');
define('DB_PASS', '');

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
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
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonctions utilitaires
function cleanInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Gestion des messages
function addError($type, $message) {
    $_SESSION['errors'][$type] = $message;
}

function getError($type) {
    return $_SESSION['errors'][$type] ?? '';
}

function clearErrors() {
    unset($_SESSION['errors']);
}

function addSuccess($message) {
    $_SESSION['success'] = $message;
}

function getSuccess() {
    return $_SESSION['success'] ?? '';
}

function clearSuccess() {
    unset($_SESSION['success']);
}
?>