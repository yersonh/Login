<?php
// api/areas.php
require_once '../controllers/AreaController.php';

header('Content-Type: application/json');

// Instanciar el controlador
$areaController = new AreaController();

// Determinar la acción según el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        manejarGET($areaController);
        break;
        
    case 'POST':
        manejarPOST($areaController);
        break;
        
    case 'PUT':
        manejarPUT($areaController);
        break;
        
    case 'PATCH':
        manejarPATCH($areaController);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
        break;
}

function manejarGET($areaController) {
    if (isset($_GET['id'])) {
        // Obtener área por ID
        $id = (int)$_GET['id'];
        $result = $areaController->obtenerPorId($id);
    } elseif (isset($_GET['buscar'])) {
        // Buscar áreas
        $termino = trim($_GET['buscar']);
        if (empty($termino)) {
            $result = $areaController->obtenerTodas();
        } else {
            $result = $areaController->buscar($termino);
        }
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
}

function manejarPOST($areaController) {
    // Crear nueva área
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El nombre del área es requerido'
        ]);
        return;
    }
    
    if (!isset($data['codigo_area']) || empty(trim($data['codigo_area']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El código del área es requerido'
        ]);
        return;
    }
    
    $result = $areaController->crear($data);
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'Ya existe') !== false ? 409 : 400);
    } else {
        http_response_code(201); // Created
    }
    
    echo json_encode($result);
}

function manejarPUT($areaController) {
    // Actualizar área existente
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere el ID del área'
        ]);
        return;
    }
    
    if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El nombre del área es requerido'
        ]);
        return;
    }
    
    $result = $areaController->actualizar($data);
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'no encontrada') !== false ? 404 : 
                           (isset($result['error']) && strpos($result['error'], 'Ya existe') !== false ? 409 : 400));
    }
    
    echo json_encode($result);
}

function manejarPATCH($areaController) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere el ID del área'
        ]);
        return;
    }
    
    if (!isset($data['activo'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere especificar el estado (activo)'
        ]);
        return;
    }
    
    // Convertir activo a booleano
    $data['activo'] = filter_var($data['activo'], FILTER_VALIDATE_BOOLEAN);
    
    $result = $areaController->cambiarEstado($data);
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'no encontrada') !== false ? 404 : 400);
    }
    
    echo json_encode($result);
}
?>