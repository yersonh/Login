<?php
// logout.php
session_start();

// Registrar el logout
if (isset($_SESSION['correo'])) {
    error_log("Logout de usuario: " . $_SESSION['correo']);
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Destruir cookie remember_token
setcookie('remember_token', '', time() - 3600, '/');

// Redirigir al login
header("Location: index.php");
exit();
?>