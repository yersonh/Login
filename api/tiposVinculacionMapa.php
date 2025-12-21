<?php
// api/tiposVinculacionMapa.php

// Configuración de CORS y headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../models/TipoVinculacionModel.php';

// Función para enviar respuesta JSON
function enviarRespuesta($success, $data = null, $error = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        enviarRespuesta(false, null, 'Método no permitido', 405);
    }
    
    // Instanciar modelo
    $tipoVinculacionModel = new TipoVinculacionModel();
    
    // Obtener tipos de vinculación (solo activos para el mapa)
    $tipos = $tipoVinculacionModel->obtenerTiposActivos();
    
    // Formatear datos para el frontend
    $tiposFormateados = array_map(function($tipo) {
        return [
            'id' => (int)$tipo['id_tipo'],
            'nombre' => htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8'),
            'codigo' => htmlspecialchars($tipo['codigo'], ENT_QUOTES, 'UTF-8'),
            'value' => htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8'), // Para compatibilidad con selects
            'text' => htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8')   // Para compatibilidad con selects
        ];
    }, $tipos);
    
    // Ordenar alfabéticamente por nombre
    usort($tiposFormateados, function($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });
    
    // Enviar respuesta exitosa
    enviarRespuesta(true, $tiposFormateados);
    
} catch (PDOException $e) {
    // Error de base de datos
    error_log('Error en tiposVinculacionMapa.php: ' . $e->getMessage());
    enviarRespuesta(false, null, 'Error de base de datos: ' . $e->getMessage(), 500);
    
} catch (Exception $e) {
    // Error general
    error_log('Error general en tiposVinculacionMapa.php: ' . $e->getMessage());
    enviarRespuesta(false, null, 'Error interno del servidor', 500);
}