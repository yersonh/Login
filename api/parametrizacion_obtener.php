<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../controllers/ConfiguracionControlador.php';

try {

    if (!class_exists('ConfiguracionControlador')) {
        throw new Exception("Controlador no encontrado");
    }

    $controlador = new ConfiguracionControlador();
    $data = $controlador->obtenerDatos();

    if (empty($data)) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "No se encontraron datos de configuración"
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => $data
    ]);

} catch (Exception $e) {

    // Aquí idealmente se hace error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error interno al cargar la configuración"
    ]);
}
