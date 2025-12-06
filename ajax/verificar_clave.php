<?php
// AJAX/VERIFICAR_CLAVE.PHP - VERSIÓN CON DEPURACIÓN COMPLETA

// ========== ACTIVAR TODOS LOS ERRORES ==========
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla
ini_set('log_errors', 1);
ini_set('error_log', '../php_errors.log'); // Archivo específico para errores

// Iniciar buffer para capturar cualquier output accidental
ob_start();

session_start();

// Verificar si hay output antes de los headers
if (ob_get_length() > 0) {
    $output_before = ob_get_clean();
    error_log("⚠️ Output antes de headers: " . $output_before);
    ob_start();
}

// Verificar que sea una petición AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

header('Content-Type: application/json');

// ========== LOG INICIAL ==========
error_log("🔍 === INICIO VERIFICACIÓN ===");
error_log("📝 Método: " . $_SERVER['REQUEST_METHOD']);
error_log("🔑 Clave recibida (primeros 3): " . (isset($_POST['clave']) ? substr(trim($_POST['clave']), 0, 3) . "***" : 'NO'));
error_log("👤 Sesión usuario_id: " . ($_SESSION['usuario_id'] ?? 'NO'));
error_log("🎭 Sesión tipo_usuario: " . ($_SESSION['tipo_usuario'] ?? 'NO'));

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario'])) {
    error_log("❌ ERROR: Sesión no válida");
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

// Verificar que venga la clave
if (!isset($_POST['clave']) || empty(trim($_POST['clave']))) {
    error_log("❌ ERROR: Clave no proporcionada");
    echo json_encode(['success' => false, 'message' => 'Clave no proporcionada']);
    exit();
}

$claveIngresada = trim($_POST['clave']);
error_log("✅ Clave recibida correctamente");

// ========== CONEXIÓN A BASE DE DATOS ==========
error_log("💾 Intentando conectar a BD...");

$configFile = '../config/database.php';
if (!file_exists($configFile)) {
    error_log("❌ ERROR: No existe archivo de configuración: " . $configFile);
    echo json_encode(['success' => false, 'message' => 'Error de configuración del servidor']);
    exit();
}

error_log("✅ Archivo de configuración encontrado");

require_once $configFile;

// Verificar que $pdo exista
if (!isset($pdo)) {
    error_log("❌ ERROR: Variable \$pdo no definida después de incluir config");
    echo json_encode(['success' => false, 'message' => 'Error de conexión a base de datos']);
    exit();
}

error_log("✅ Variable \$pdo definida");

try {
    // ========== CONSULTA SQL ==========
    $sql = "SELECT 
                u.id_usuario,
                u.correo,
                u.contrasena,
                p.nombres,
                p.apellidos
            FROM usuarios u
            INNER JOIN persona p ON u.id_persona = p.id_persona
            WHERE u.tipo_usuario = 'administrador'";
    
    error_log("📋 SQL a ejecutar: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta SQL");
    }
    
    error_log("✅ Consulta preparada");
    
    $stmt->execute();
    error_log("✅ Consulta ejecutada");
    
    $administradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("👥 Administradores encontrados: " . count($administradores));
    
    // Mostrar info de cada admin para depuración
    foreach ($administradores as $i => $admin) {
        error_log("   Admin {$i}: " . $admin['correo'] . " - " . $admin['nombres'] . " " . $admin['apellidos']);
        error_log("     Hash inicio: " . substr($admin['contrasena'], 0, 30) . "...");
        error_log("     Longitud hash: " . strlen($admin['contrasena']));
    }
    
    // ========== VERIFICACIÓN DE CLAVE ==========
    $claveValida = false;
    $adminEncontrado = null;
    
    foreach ($administradores as $admin) {
        error_log("🔐 Verificando contra admin: " . $admin['correo']);
        
        if (password_verify($claveIngresada, $admin['contrasena'])) {
            $claveValida = true;
            $adminEncontrado = $admin;
            error_log("🎉 ¡COINCIDENCIA ENCONTRADA para: " . $admin['correo'] . "!");
            break;
        } else {
            error_log("❌ No coincide para: " . $admin['correo']);
        }
    }
    
    if ($claveValida && $adminEncontrado) {
        // ========== ÉXITO ==========
        $_SESSION['verificado_por_admin'] = true;
        $_SESSION['admin_verificador_id'] = $adminEncontrado['id_usuario'];
        $_SESSION['admin_verificador_nombre'] = $adminEncontrado['nombres'] . ' ' . $adminEncontrado['apellidos'];
        $_SESSION['admin_verificador_correo'] = $adminEncontrado['correo'];
        $_SESSION['verificacion_timestamp'] = time();
        
        error_log("✅ VERIFICACIÓN EXITOSA");
        error_log("   Asistente: " . ($_SESSION['correo'] ?? 'Desconocido'));
        error_log("   Autorizado por: " . $_SESSION['admin_verificador_nombre']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Clave de administrador verificada correctamente',
            'administrador' => $adminEncontrado['nombres'] . ' ' . $adminEncontrado['apellidos']
        ]);
        
    } else {
        // ========== CLAVE INCORRECTA ==========
        error_log("❌ VERIFICACIÓN FALLIDA - Ningún admin coincide");
        
        echo json_encode([
            'success' => false, 
            'message' => 'Clave de administrador incorrecta'
        ]);
    }
    
} catch (PDOException $e) {
    // ========== ERROR PDO ==========
    error_log("💥 ERROR PDO: " . $e->getMessage());
    error_log("   Código error: " . $e->getCode());
    error_log("   Archivo: " . $e->getFile());
    error_log("   Línea: " . $e->getLine());
    
    // Información adicional para debugging
    if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
        error_log("   Tipo: Error SQLSTATE");
    }
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos. Contacte al administrador.'
    ]);
    
} catch (Exception $e) {
    // ========== ERROR GENERAL ==========
    error_log("💥 ERROR GENERAL: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}

// ========== FIN ==========
error_log("🏁 === FIN VERIFICACIÓN ===");

// Limpiar buffer
ob_end_flush();
?>