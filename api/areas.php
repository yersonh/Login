<?php
// api/areas.php
require_once '../controllers/AreaController.php';

header('Content-Type: application/json');

// Instanciar el controlador
$areaController = new AreaController();

// Determinar la acción según el método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        // Obtener área por ID
        $id = (int)$_GET['id'];
        $result = $areaController->obtenerPorId($id);
    } elseif (isset($_GET['activas'])) {
        // Obtener solo áreas activas
        $result = $areaController->obtenerActivas();
    } else {
        // Obtener todas las áreas
        $result = $areaController->obtenerTodas();
    }
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'no encontrada') !== false ? 404 : 500);
    }
    
    echo json_encode($result);
    
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}
?>