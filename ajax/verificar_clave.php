<?php
// verificar_clave.php - VERSIÓN CORREGIDA PARA TU CLASE DATABASE

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

// Conectar a la base de datos USANDO LA CLASE
require_once '../config/database.php';

try {
    // 1. Instanciar la clase Database
    $database = new Database();
    
    // 2. Obtener la conexión PDO
    $pdo = $database->conectar();
    
    // 3. Verificar si la conexión fue exitosa
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    // 4. CONSULTA CORREGIDA CON JOIN
    $sql = "SELECT 
                u.id_usuario,
                u.correo,
                u.contrasena,
                p.nombres,
                p.apellidos
            FROM usuario u
            INNER JOIN persona p ON u.id_persona = p.id_persona
            WHERE u.tipo_usuario = 'administrador'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $claveValida = false;
    $adminEncontrado = null;
    
    // VERIFICAR CONTRASEÑA
    foreach ($administradores as $admin) {
        if (password_verify($claveIngresada, $admin['contrasena'])) {
            $claveValida = true;
            $adminEncontrado = $admin;
            break;
        }
    }
    
    if ($claveValida && $adminEncontrado) {
        // Guardar en sesión
        $_SESSION['verificado_por_admin'] = true;
        $_SESSION['admin_verificador_id'] = $adminEncontrado['id_usuario'];
        $_SESSION['admin_verificador_nombre'] = $adminEncontrado['nombres'] . ' ' . $adminEncontrado['apellidos'];
        $_SESSION['admin_verificador_correo'] = $adminEncontrado['correo'];
        $_SESSION['verificacion_timestamp'] = time();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Clave de administrador verificada correctamente',
            'administrador' => $adminEncontrado['nombres'] . ' ' . $adminEncontrado['apellidos']
        ]);
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Clave de administrador incorrecta'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error PDO en verificar_clave: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error general en verificar_clave: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>