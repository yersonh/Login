<?php
// verificar_clave.php - VERSIÓN MODIFICADA PARA INICIAR SESIÓN COMO ADMIN

session_start();
header('Content-Type: application/json');

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que el usuario esté logueado como asistente
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    echo json_encode(['success' => false, 'message' => 'Debe estar logueado como asistente']);
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
    $database = new Database();
    $pdo = $database->conectar();
    
    if (!$pdo) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    // CONSULTA: Buscar administrador con esa contraseña
    $sql = "SELECT 
                u.id_usuario,
                u.correo,
                u.contrasena,
                u.tipo_usuario,
                p.nombres,
                p.apellidos,
                p.documento,
                p.telefono
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
        // GUARDAR DATOS DEL ASISTENTE ORIGINAL PARA PODER REGRESAR
        $datosAsistenteOriginal = [
            'id_usuario' => $_SESSION['usuario_id'],
            'nombres' => $_SESSION['nombres'],
            'apellidos' => $_SESSION['apellidos'],
            'correo' => $_SESSION['correo'],
            'tipo_usuario' => $_SESSION['tipo_usuario']
        ];
        
        // REINICIAR SESIÓN e INICIAR COMO ADMINISTRADOR
        session_regenerate_id(true);
        
        // Establecer datos del administrador
        $_SESSION['usuario_id'] = $adminEncontrado['id_usuario'];
        $_SESSION['nombres'] = $adminEncontrado['nombres'];
        $_SESSION['apellidos'] = $adminEncontrado['apellidos'];
        $_SESSION['correo'] = $adminEncontrado['correo'];
        $_SESSION['tipo_usuario'] = $adminEncontrado['tipo_usuario'];
        $_SESSION['documento'] = $adminEncontrado['documento'];
        $_SESSION['telefono'] = $adminEncontrado['telefono'];
        
        // Guardar información del cambio
        $_SESSION['usuario_original'] = $datosAsistenteOriginal;
        $_SESSION['admin_login_timestamp'] = time();
        $_SESSION['admin_login_method'] = 'clave_asistente';
        
        error_log("SESIÓN CAMBIADA: Asistente " . $datosAsistenteOriginal['correo'] . 
                  " ahora es Admin " . $adminEncontrado['correo']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sesión iniciada como administrador',
            'administrador' => $adminEncontrado['nombres'] . ' ' . $adminEncontrado['apellidos']
        ]);
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Clave de administrador incorrecta'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos'
    ]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor'
    ]);
}
?>