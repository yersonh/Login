<?php
header("Content-Type: application/json");
session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    echo json_encode([
        "success" => false,
        "error" => "Acceso no autorizado. Solo administradores pueden realizar esta acción."
    ]);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        "success" => false,
        "error" => "No se recibieron datos válidos."
    ]);
    exit;
}

try {
    // Incluir el controlador
    require_once 'ConfiguracionControlador.php';
    $controlador = new ConfiguracionControlador();
    
    // Preparar datos para actualizar (SOLO configuración del sistema)
    $datosActualizar = [
        'version_sistema' => trim($input['version_sistema'] ?? ''),
        'tipo_licencia' => trim($input['tipo_licencia'] ?? ''),
        'valida_hasta' => !empty($input['valida_hasta']) ? trim($input['valida_hasta']) : null,
        'desarrollado_por' => trim($input['desarrollado_por'] ?? ''),
        'direccion' => trim($input['direccion'] ?? ''),
        'correo_contacto' => trim($input['correo_contacto'] ?? ''),
        'telefono' => trim($input['telefono'] ?? '')
        // NOTA: NO incluimos entidad, enlace_web, ruta_logo aquí
        // esos se actualizan con parametrizacion_actualizar_logo.php
    ];
    
    // Validar campos requeridos
    $errores = [];
    
    if (empty($datosActualizar['version_sistema'])) {
        $errores[] = "La versión del sistema es requerida";
    }
    
    if (empty($datosActualizar['desarrollado_por'])) {
        $errores[] = "El nombre del desarrollador es requerido";
    }
    
    if (empty($datosActualizar['correo_contacto'])) {
        $errores[] = "El correo de contacto es requerido";
    }
    
    // Validar email
    if (!empty($datosActualizar['correo_contacto']) && !filter_var($datosActualizar['correo_contacto'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no tiene un formato válido";
    }
    
    // Validar fecha si existe
    if (!empty($datosActualizar['valida_hasta'])) {
        $fecha = DateTime::createFromFormat('Y-m-d', $datosActualizar['valida_hasta']);
        if (!$fecha || $fecha->format('Y-m-d') !== $datosActualizar['valida_hasta']) {
            $errores[] = "La fecha de validez no tiene un formato válido (YYYY-MM-DD)";
        }
    }
    
    // Si hay errores, retornarlos
    if (!empty($errores)) {
        echo json_encode([
            "success" => false,
            "error" => implode(", ", $errores)
        ]);
        exit;
    }
    
    // Actualizar en la base de datos
    $resultado = $controlador->actualizarDatos($datosActualizar);
    
    if ($resultado) {
        echo json_encode([
            "success" => true,
            "message" => "Configuración del sistema actualizada correctamente",
            "data" => $datosActualizar
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No se pudo actualizar la configuración en la base de datos"
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error en parametrizacion_actualizar.php: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "error" => "Error interno del servidor: " . $e->getMessage()
    ]);
}
?>