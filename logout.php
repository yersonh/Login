<?php
// logout.php - VERSIÓN CORREGIDA
session_start();

// Registrar logout
if (isset($_SESSION['correo'])) {
    error_log("Logout de usuario: " . $_SESSION['correo']);
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Destruir cualquier otra cookie personalizada
setcookie('remember_token', '', time() - 3600, '/');

// HEADERS PARA PREVENIR CACHÉ
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// HEADERS PARA PREVENIR VUELTA ATRÁS
header("Location: ../index.php");
header("HTTP/1.1 302 Found");
exit();
?>