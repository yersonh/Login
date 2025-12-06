<?php
session_start();
header('Content-Type: application/json');

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

// Verificar que venga la clave
if (!isset($_POST['clave']) || empty(trim($_POST['clave']))) {
    echo json_encode(['success' => false, 'message' => 'Clave no proporcionada']);
    exit();
}

$claveIngresada = trim($_POST['clave']);

// Conectar a la base de datos
require_once '../config/database.php';

try {
    // OBTENER TODOS LOS ADMINISTRADORES ACTIVOS
    $sql = "SELECT id, nombres, apellidos, correo, contrasena 
            FROM usuarios 
            WHERE tipo_usuario = 'administrador' 
            AND estado = 'activo'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $claveValida = false;
    $adminEncontrado = null;
    
    // VERIFICAR CONTRASEÑA CONTRA CADA ADMINISTRADOR USANDO password_verify()
    foreach ($administradores as $admin) {
        // password_verify() es para contraseñas hasheadas con password_hash()
        if (password_verify($claveIngresada, $admin['contrasena'])) {
            $claveValida = true;
            $adminEncontrado = $admin;
            
            // Log detallado (opcional)
            error_log("CLAVE VERIFICADA - Hash encontrado: " . substr($admin['contrasena'], 0, 20) . "...");
            break;
        }
    }
    
    if ($claveValida && $adminEncontrado) {
        // Clave válida - Guardar en sesión
        $_SESSION['verificado_por_admin'] = true;
        $_SESSION['admin_verificador_id'] = $adminEncontrado['id'];
        $_SESSION['admin_verificador_nombre'] = $adminEncontrado['nombres'] . ' ' . $adminEncontrado['apellidos'];
        $_SESSION['admin_verificador_correo'] = $adminEncontrado['correo'];
        $_SESSION['verificacion_timestamp'] = time();
        
        // Registrar en log para auditoría
        error_log("ACCESO AUTORIZADO - Asistente: " . ($_SESSION['correo'] ?? 'Desconocido') . 
                 " fue autorizado por Admin: " . $adminEncontrado['correo'] . 
                 " (" . $adminEncontrado['nombres'] . " " . $adminEncontrado['apellidos'] . ")" .
                 " - IP: " . $_SERVER['REMOTE_ADDR'] . 
                 " - Hora: " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true, 
            'message' => 'Clave de administrador verificada correctamente',
            'administrador' => $adminEncontrado['nombres'] . ' ' . $adminEncontrado['apellidos']
        ]);
        
    } else {
        // Registrar intento fallido para seguridad
        $intentosFallidos = $_SESSION['intentos_fallidos'] ?? 0;
        $intentosFallidos++;
        $_SESSION['intentos_fallidos'] = $intentosFallidos;
        
        error_log("INTENTO FALLIDO #{$intentosFallidos} - Asistente: " . ($_SESSION['correo'] ?? 'Desconocido') . 
                 " - Clave intentada: '" . substr($claveIngresada, 0, 3) . "***'" . 
                 " - IP: " . $_SERVER['REMOTE_ADDR'] . 
                 " - Hora: " . date('Y-m-d H:i:s'));
        
        // Bloquear después de 5 intentos fallidos
        if ($intentosFallidos >= 5) {
            error_log("BLOQUEO TEMPORAL - Demasiados intentos fallidos desde IP: " . $_SERVER['REMOTE_ADDR']);
            
            echo json_encode([
                'success' => false, 
                'message' => 'Demasiados intentos fallidos. Espere 15 minutos.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Clave de administrador incorrecta'
            ]);
        }
    }
    
} catch (PDOException $e) {
    // Log del error sin exponer detalles
    error_log("ERROR DB en verificar_clave: " . $e->getMessage() . " - IP: " . $_SERVER['REMOTE_ADDR']);
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error en el servidor. Intente nuevamente.'
    ]);
}
?>