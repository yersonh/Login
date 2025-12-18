<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/MunicipioControlador.php';

try {
    $controlador = new MunicipioController();
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    switch ($metodo) {
        case 'GET':
            // SOLO para obtener UN municipio por ID o buscar
            if (isset($_GET['id'])) {
                $resultado = $controlador->obtenerPorId($_GET['id']);
            } elseif (isset($_GET['buscar'])) {
                $resultado = $controlador->buscar($_GET['buscar']);
            } else {
                // Redirigir a ObtenerMunicipio.php si quieren todos
                throw new Exception('Use /api/ObtenerMunicipio.php para obtener todos los municipios');
            }
            echo json_encode($resultado);
            break;
            
        case 'POST':
            // Crear nuevo municipio
            $input = json_decode(file_get_contents('php://input'), true);
            $resultado = $controlador->crear($input);
            echo json_encode($resultado);
            break;
            
        case 'PUT':
            // Actualizar municipio
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) throw new Exception('ID requerido');
            $resultado = $controlador->actualizar($input['id'], $input);
            echo json_encode($resultado);
            break;
            
        case 'PATCH':
            // Cambiar estado (activar/desactivar)
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) throw new Exception('ID requerido');
            if (!isset($input['activo'])) throw new Exception('Estado (activo) requerido');
            
            $resultado = $controlador->cambiarEstado($input['id'], (bool)$input['activo']);
            echo json_encode($resultado);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}