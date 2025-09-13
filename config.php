<?php
// config.php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gestion_taller_motos');

// Conexión a la base de datos
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para redireccionar
function redirect($url) {
    header("Location: $url");
    exit();
}

// Función para verificar autenticación
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Función para verificar rol
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Función para enviar emails
function sendEmail($to, $subject, $message) {
    $headers = "From: no-reply@tallermotos.com" . "\r\n" .
               "Reply-To: no-reply@tallermotos.com" . "\r\n" .
               "X-Mailer: PHP/" . phpversion() .
               "MIME-Version: 1.0" . "\r\n" .
               "Content-type: text/html; charset=UTF-8" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function isClienteAuthenticated() {
    return isset($_SESSION['cliente_id']);
}
?>