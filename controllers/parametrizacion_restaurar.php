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
    
    // Datos predeterminados
    $datosPredeterminados = [
        'version_sistema' => '1.0.0',
        'tipo_licencia' => 'Evaluación',
        'valida_hasta' => '2026-03-31',
        'desarrollado_por' => 'SisgonTech',
        'direccion' => 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta',
        'correo_contacto' => 'gobernaciondelmeta@meta.gov.co',
        'telefono' => '(57 -608) 6 818503',
        'ruta_logo' => '../../imagenes/gobernacion.png',
        'entidad' => 'Logo Gobernación del Meta',
        'enlace_web' => 'https://www.meta.gov.co'
    ];
    
    // Eliminar el logo actual si existe y no es el predeterminado
    $configActual = $controlador->obtenerDatos();
    if (!empty($configActual['ruta_logo']) && 
        $configActual['ruta_logo'] !== '../../imagenes/gobernacion.png' &&
        file_exists($configActual['ruta_logo'])) {
        @unlink($configActual['ruta_logo']);
    }
    
    // Actualizar con valores predeterminados
    $resultado = $controlador->actualizarDatos($datosPredeterminados);
    
    if ($resultado) {
        echo json_encode([
            "success" => true,
            "message" => "Configuración restaurada correctamente",
            "ruta_logo" => $datosPredeterminados['ruta_logo']
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No se pudo restaurar la configuración"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>