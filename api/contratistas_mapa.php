<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

require_once '../config/database.php';
require_once '../models/ContratistaModel.php';
require_once '../models/MunicipioModel.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $database = new Database();
    $db = $database->conectar();
    $contratistaModel = new ContratistaModel($db);
    
    // Obtener parámetros de filtro
    $filtros = [
        'municipio' => $_GET['municipio'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'area' => $_GET['area'] ?? null,
        'tipo_vinculacion' => $_GET['tipo'] ?? null
    ];
    
    // Obtener todos los contratistas
    $contratistas = $contratistaModel->obtenerContratistasParaMapa();
    
    // Aplicar filtros si existen
    if (!empty(array_filter($filtros))) {
        $contratistas = filtrarContratistas($contratistas, $filtros);
    }
    
    if (empty($contratistas)) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'count' => 0,
            'message' => 'No hay contratistas que coincidan con los filtros'
        ]);
        exit();
    }
    
    // Procesar datos
    $resultados = array_map(function($contratista) {
        return [
            'id' => 'contratista_' . $contratista['id_detalle'],
            'id_detalle' => $contratista['id_detalle'],
            'id_persona' => $contratista['id_persona'],
            'nombre' => trim($contratista['nombres'] . ' ' . $contratista['apellidos']),
            'nombres' => $contratista['nombres'],
            'apellidos' => $contratista['apellidos'],
            'cedula' => $contratista['cedula'],
            'telefono' => $contratista['telefono'] ?? 'No registrado',
            'contrato' => $contratista['numero_contrato'],
            'fecha_inicio' => $contratista['fecha_inicio'],
            'fecha_final' => $contratista['fecha_final'],
            'area' => $contratista['area'],
            'tipo_vinculacion' => $contratista['tipo_vinculacion'],
            'municipio_principal' => $contratista['municipio_principal'],
            'municipio_secundario' => $contratista['municipio_secundario'],
            'municipio_terciario' => $contratista['municipio_terciario'],
            'direccion' => $contratista['direccion'],
            'created_at' => $contratista['created_at']
        ];
    }, $contratistas);
    
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'count' => count($resultados),
        'filters_applied' => array_filter($filtros),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos',
        'message' => $e->getMessage()
    ]);
}

// Función para filtrar contratistas
function filtrarContratistas($contratistas, $filtros) {
    return array_filter($contratistas, function($contratista) use ($filtros) {
        $coincide = true;
        
        // Filtrar por municipio (principal, secundario o terciario)
        if (!empty($filtros['municipio'])) {
            $municipioFiltro = strtolower(trim($filtros['municipio']));
            $municipiosContratista = [
                strtolower($contratista['municipio_principal'] ?? ''),
                strtolower($contratista['municipio_secundario'] ?? ''),
                strtolower($contratista['municipio_terciario'] ?? '')
            ];
            
            if (!in_array($municipioFiltro, $municipiosContratista)) {
                $coincide = false;
            }
        }
        
        // Filtrar por nombre (búsqueda parcial en nombres o apellidos)
        if (!empty($filtros['nombre'])) {
            $nombreBusqueda = strtolower(trim($filtros['nombre']));
            $nombreCompleto = strtolower($contratista['nombres'] . ' ' . $contratista['apellidos']);
            
            if (strpos($nombreCompleto, $nombreBusqueda) === false) {
                $coincide = false;
            }
        }
        
        // Filtrar por área
        if (!empty($filtros['area']) && $contratista['area'] != $filtros['area']) {
            $coincide = false;
        }
        
        // Filtrar por tipo de vinculación
        if (!empty($filtros['tipo_vinculacion']) && $contratista['tipo_vinculacion'] != $filtros['tipo_vinculacion']) {
            $coincide = false;
        }
        
        return $coincide;
    });
}
?>