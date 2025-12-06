<?php
session_start();
header('Content-Type: application/json');

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que el usuario esté logueado (aunque sea asistente)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

// Verificar que venga la clave
if (!isset($_POST['clave']) || empty(trim($_POST['clave']))) {
    echo json_encode(['success' => false, 'message' => 'Clave no proporcionada']);
    exit();
}

$clave = trim($_POST['clave']);

// Conectar a la base de datos (ajusta estas credenciales)
require_once '../config/database.php';

try {
    // Preparar consulta para buscar un administrador con esa contraseña
    $sql = "SELECT id, nombres, apellidos, correo FROM usuarios 
            WHERE tipo_usuario = 'administrador' 
            AND contrasena = :contrasena 
            AND estado = 'activo' 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':contrasena', $clave);
    $stmt->execute();
    
    $administrador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($administrador) {
        // Clave válida de administrador
        // Opcional: Guardar en sesión que fue verificado por administrador
        $_SESSION['verificado_por_admin'] = true;
        $_SESSION['admin_verificador_id'] = $administrador['id'];
        $_SESSION['admin_verificador_nombre'] = $administrador['nombres'] . ' ' . $administrador['apellidos'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Clave de administrador verificada',
            'administrador' => [
                'nombre' => $administrador['nombres'] . ' ' . $administrador['apellidos'],
                'correo' => $administrador['correo']
            ]
        ]);
    } else {
        // Clave incorrecta o no es de administrador
        echo json_encode([
            'success' => false, 
            'message' => 'Clave de administrador incorrecta o no autorizada'
        ]);
    }
    
} catch (PDOException $e) {
    // Log del error (en producción)
    error_log("Error en verificación de clave: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error en el servidor. Intente nuevamente.'
    ]);
}