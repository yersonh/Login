<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}
require_once '../models/AreaModel.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $areaModel = new AreaModel();
    
    // Obtener solo áreas activas para el mapa
    $areas = $areaModel->obtenerAreasActivas();
    
    echo json_encode([
        'success' => true,
        'data' => $areas,
        'count' => count($areas)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener áreas',
        'message' => $e->getMessage()
    ]);
}
?>