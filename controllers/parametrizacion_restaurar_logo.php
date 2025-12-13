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

try {
    // Incluir el controlador
    require_once 'ConfiguracionControlador.php';
    $controlador = new ConfiguracionControlador();
    
    // Obtener la configuración actual
    $configActual = $controlador->obtenerDatos();
    
    // Preparar datos para restaurar (logo predeterminado)
    $datosActualizar = [
        'ruta_logo' => '../../imagenes/gobernacion.png',
        'entidad' => 'Logo Gobernación del Meta',
        'enlace_web' => 'https://www.meta.gov.co'
    ];
    
    // Mantener los otros datos existentes
    if (!empty($configActual)) {
        $datosActualizar['version_sistema'] = $configActual['version_sistema'] ?? '';
        $datosActualizar['tipo_licencia'] = $configActual['tipo_licencia'] ?? '';
        $datosActualizar['valida_hasta'] = $configActual['valida_hasta'] ?? null;
        $datosActualizar['desarrollado_por'] = $configActual['desarrollado_por'] ?? '';
        $datosActualizar['direccion'] = $configActual['direccion'] ?? '';
        $datosActualizar['correo_contacto'] = $configActual['correo_contacto'] ?? '';
        $datosActualizar['telefono'] = $configActual['telefono'] ?? '';
    }
    
    // Eliminar el logo actual si existe y no es el predeterminado
    if (!empty($configActual['ruta_logo']) && 
        $configActual['ruta_logo'] !== '../../imagenes/gobernacion.png' &&
        file_exists($configActual['ruta_logo'])) {
        @unlink($configActual['ruta_logo']);
    }
    
    // Actualizar en la base de datos
    $resultado = $controlador->actualizarDatos($datosActualizar);
    
    if ($resultado) {
        echo json_encode([
            "success" => true,
            "message" => "Logo restaurado correctamente",
            "ruta_logo" => $datosActualizar['ruta_logo'],
            "entidad" => $datosActualizar['entidad'],
            "enlace_web" => $datosActualizar['enlace_web']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No se pudo restaurar el logo"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>