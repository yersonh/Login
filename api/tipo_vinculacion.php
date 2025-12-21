<?php
// api/tipo_vinculacion.php
require_once '../controllers/TipoVinculacionController.php';

header('Content-Type: application/json');

// Iniciar sesión para verificar permisos
session_start();

// Verificar que el usuario esté autenticado y sea administrador
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Acceso denegado. Solo administradores pueden realizar esta acción.'
    ]);
    exit();
}

// Instanciar el controlador
$tipoVinculacionController = new TipoVinculacionController();

// Determinar la acción según el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        manejarGET($tipoVinculacionController);
        break;
        
    case 'POST':
        manejarPOST($tipoVinculacionController);
        break;
        
    case 'PUT':
        manejarPUT($tipoVinculacionController);
        break;
        
    case 'PATCH':
        manejarPATCH($tipoVinculacionController);
        break;
        
    case 'DELETE':
        manejarDELETE($tipoVinculacionController);
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
        break;
}

function manejarGET($tipoVinculacionController) {
    if (isset($_GET['id_tipo'])) {
        // Obtener tipo por ID
        $id_tipo = (int)$_GET['id_tipo'];
        $result = $tipoVinculacionController->obtenerPorId($id_tipo);
    } elseif (isset($_GET['buscar'])) {
        // Buscar tipos
        $termino = trim($_GET['buscar']);
        if (empty($termino)) {
            $result = $tipoVinculacionController->obtenerTodos();
        } else {
            $result = $tipoVinculacionController->buscar($termino);
        }
    } elseif (isset($_GET['activas'])) {
        // Obtener solo tipos activos
        $result = $tipoVinculacionController->obtenerActivos();
    } else {
        // Obtener todos los tipos
        $result = $tipoVinculacionController->obtenerTodos();
    }
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'no encontrado') !== false ? 404 : 500);
    }
    
    echo json_encode($result);
}

function manejarPOST($tipoVinculacionController) {
    // Crear nuevo tipo
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El nombre del tipo es requerido'
        ]);
        return;
    }
    
    // El código es opcional, si no viene, establecer null
    if (isset($data['codigo']) && empty(trim($data['codigo']))) {
        $data['codigo'] = null;
    }
    
    $result = $tipoVinculacionController->crear($data);
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'Ya existe') !== false ? 409 : 400);
    } else {
        http_response_code(201); // Created
    }
    
    echo json_encode($result);
}

function manejarPUT($tipoVinculacionController) {
    // Actualizar tipo existente
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (!isset($data['id_tipo']) || empty($data['id_tipo'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere el ID del tipo'
        ]);
        return;
    }
    
    if (!isset($data['nombre']) || empty(trim($data['nombre']))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El nombre del tipo es requerido'
        ]);
        return;
    }
    
    $result = $tipoVinculacionController->actualizar($data);
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'no encontrado') !== false ? 404 : 
                           (isset($result['error']) && strpos($result['error'], 'Ya existe') !== false ? 409 : 400));
    }
    
    echo json_encode($result);
}

function manejarPATCH($tipoVinculacionController) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (!isset($data['id_tipo']) || empty($data['id_tipo'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere el ID del tipo'
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
    
    $result = $tipoVinculacionController->cambiarEstado($data);
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        http_response_code(isset($result['error']) && strpos($result['error'], 'no encontrado') !== false ? 404 : 400);
    }
    
    echo json_encode($result);
}

function manejarDELETE($tipoVinculacionController) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validaciones básicas
    if (!isset($data['id_tipo']) && !isset($data['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Se requiere el ID del tipo'
        ]);
        return;
    }
    
    // Obtener ID (acepta ambos nombres de campo)
    $id = isset($data['id_tipo']) ? $data['id_tipo'] : $data['id'];
    
    $result = $tipoVinculacionController->eliminar($id);
    
    // Establecer código HTTP apropiado
    if (isset($result['success']) && !$result['success']) {
        // Si hay error de dependencias, usar 409 Conflict
        if (isset($result['error']) && strpos($result['error'], 'No se puede eliminar') !== false) {
            http_response_code(409); // Conflict
        } elseif (isset($result['error']) && strpos($result['error'], 'no encontrado') !== false) {
            http_response_code(404); // Not Found
        } else {
            http_response_code(400); // Bad Request
        }
    } else {
        http_response_code(200); // OK
    }
    
    echo json_encode($result);
}
?>