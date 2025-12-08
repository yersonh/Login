<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Verificar si es una petición AJAX
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    
    // Si no es AJAX, devolver error
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Validar token CSRF si se envía
if (isset($_POST['csrf_token'])) {
    if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Token CSRF inválido
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Error de seguridad. Por favor, recargue la página.',
            'csrf_error' => true
        ]);
        exit();
    }
}

// Registrar el logout para auditoría
$usuario_id = $_SESSION['usuario_id'] ?? 'Desconocido';
$correo = $_SESSION['correo'] ?? 'Desconocido';
error_log("LOGOUT - Usuario ID: $usuario_id - Correo: $correo - IP: " . $_SERVER['REMOTE_ADDR']);

// 1. Eliminar cookie remember_token si existe
if (isset($_COOKIE['remember_token'])) {
    // Primero eliminar de la base de datos
    try {
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->conectar();
        
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE token = :token");
        $stmt->bindParam(':token', $_COOKIE['remember_token']);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Error eliminando remember_token de BD: " . $e->getMessage());
    }
    
    // Eliminar cookie del cliente
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// 2. Destruir todas las variables de sesión
$_SESSION = array();

// 3. Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión
session_destroy();

// 5. Generar nuevo token CSRF para la próxima sesión
session_start(); // Iniciar nueva sesión para el token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
session_write_close();

// 6. Responder con éxito
echo json_encode([
    'success' => true, 
    'message' => 'Sesión cerrada correctamente',
    'redirect' => '../index.php'
]);
exit();
?>