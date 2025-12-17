<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../controllers/MunicipioControlador.php';

try {
    // Crear controlador (ya maneja su propia conexión)
    $controlador = new MunicipioController();
    
    // Verificar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido. Solo se permite GET'
        ]);
        exit;
    }
    
    // Obtener todos los municipios
    $resultado = $controlador->obtenerTodos();
    
    if ($resultado['success']) {
        // Formatear los datos para la tabla
        $municipiosFormateados = array_map(function($municipio) {
            return [
                'id_municipio' => (int)$municipio['id_municipio'],
                'nombre' => htmlspecialchars($municipio['nombre']),
                'departamento' => htmlspecialchars($municipio['departamento'] ?? 'Meta'),
                'codigo_dane' => htmlspecialchars($municipio['codigo_dane'] ?? ''),
                'activo' => (bool)$municipio['activo'],
                'estado' => $municipio['activo'] ? 'Activo' : 'Inactivo'
            ];
        }, $resultado['data']);
        
        echo json_encode([
            'success' => true,
            'data' => $municipiosFormateados,
            'total' => count($municipiosFormateados),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $resultado['error'] ?? 'Error al obtener municipios',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    error_log('Error en ObtenerMunicipio.php: ' . $e->getMessage());
}