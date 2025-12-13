<?php
header("Content-Type: application/json");
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    echo json_encode([
        "success" => false,
        "error" => "Acceso no autorizado"
    ]);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        "success" => false,
        "error" => "Datos no válidos"
    ]);
    exit;
}

try {
    // Incluir el controlador
    require_once 'ConfiguracionControlador.php';
    $controlador = new ConfiguracionControlador();
    
    // Preparar datos para actualizar
    $datosActualizar = [
        'version_sistema' => trim($input['version_sistema'] ?? ''),
        'tipo_licencia' => trim($input['tipo_licencia'] ?? ''),
        'valida_hasta' => !empty($input['valida_hasta']) ? trim($input['valida_hasta']) : null,
        'desarrollado_por' => trim($input['desarrollado_por'] ?? ''),
        'direccion' => trim($input['direccion'] ?? ''),
        'correo_contacto' => trim($input['correo_contacto'] ?? ''),
        'telefono' => trim($input['telefono'] ?? ''),
        'entidad' => trim($input['entidad'] ?? ''),
        'enlace_web' => trim($input['enlace_web'] ?? ''),
        'ruta_logo' => trim($input['ruta_logo'] ?? '')
    ];
    
    // Validar campos requeridos
    if (empty($datosActualizar['version_sistema'])) {
        throw new Exception("La versión del sistema es requerida");
    }
    
    if (empty($datosActualizar['desarrollado_por'])) {
        throw new Exception("El nombre del desarrollador es requerido");
    }
    
    if (empty($datosActualizar['correo_contacto'])) {
        throw new Exception("El correo de contacto es requerido");
    }
    
    // Validar email
    if (!filter_var($datosActualizar['correo_contacto'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("El correo electrónico no es válido");
    }
    
    // Actualizar en la base de datos
    $resultado = $controlador->actualizarDatos($datosActualizar);
    
    if ($resultado) {
        echo json_encode([
            "success" => true,
            "message" => "Configuración actualizada correctamente"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No se pudo actualizar la configuración"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>