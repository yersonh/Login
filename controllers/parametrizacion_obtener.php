<?php
header("Content-Type: application/json");

// Incluir el controlador
require_once 'ConfiguracionControlador.php';

try {
    // Crear instancia del controlador
    $controlador = new ConfiguracionControlador();
    
    // Obtener datos
    $data = $controlador->obtenerDatos();
    
    // Verificar si hay datos
    if (empty($data)) {
        echo json_encode([
            "success" => false,
            "error" => "No se encontraron datos de configuración"
        ]);
        exit;
    }
    
    // Devolver datos en formato JSON
    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
    
} catch (Exception $e) {
    // Manejar errores
    echo json_encode([
        "success" => false,
        "error" => "Error al cargar configuración: " . $e->getMessage()
    ]);
}
?>