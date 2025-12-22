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
        $contratistasFiltrados = filtrarContratistas($contratistas, $filtros);
        
        // CORRECCIÓN CRÍTICA: Asegurarnos de que siempre sea un array
        if (empty($contratistasFiltrados)) {
            $contratistasFiltrados = [];
        }
        
        // Re-indexar el array para asegurar índices consecutivos
        $contratistasFiltrados = array_values($contratistasFiltrados);
        
        $contratistas = $contratistasFiltrados;
    }
    
    // CORRECCIÓN: Verificar que $contratistas sea un array antes de procesar
    if (!is_array($contratistas)) {
        $contratistas = [];
    }
    
    if (empty($contratistas)) {
        echo json_encode([
            'success' => true,
            'data' => [], // Siempre devolver array vacío, no null
            'count' => 0,
            'message' => 'No hay contratistas que coincidan con los filtros'
        ]);
        exit();
    }
    
    // Procesar datos - asegurar que siempre procesamos un array
    $resultados = [];
    foreach ($contratistas as $contratista) {
        // Verificar que $contratista sea un array válido
        if (is_array($contratista)) {
            // Crear array de sitios de trabajo
            $sitios_trabajo = [];
            
            // Sitio principal (siempre debe existir)
            if (!empty($contratista['direccion_municipio_principal']) && !empty($contratista['municipio_principal'])) {
                $sitios_trabajo[] = [
                    'tipo' => 'principal',
                    'municipio' => $contratista['municipio_principal'],
                    'direccion' => $contratista['direccion_municipio_principal'],
                    'municipio_id' => $contratista['id_municipio_principal'] ?? null
                ];
            }
            
            // Sitio secundario (opcional)
            if (!empty($contratista['direccion_municipio_secundario']) && !empty($contratista['municipio_secundario'])) {
                $sitios_trabajo[] = [
                    'tipo' => 'secundario',
                    'municipio' => $contratista['municipio_secundario'],
                    'direccion' => $contratista['direccion_municipio_secundario'],
                    'municipio_id' => $contratista['id_municipio_secundario'] ?? null
                ];
            }
            
            // Sitio terciario (opcional)
            if (!empty($contratista['direccion_municipio_terciario']) && !empty($contratista['municipio_terciario'])) {
                $sitios_trabajo[] = [
                    'tipo' => 'terciario',
                    'municipio' => $contratista['municipio_terciario'],
                    'direccion' => $contratista['direccion_municipio_terciario'],
                    'municipio_id' => $contratista['id_municipio_terciario'] ?? null
                ];
            }
            
            $resultados[] = [
                'id' => 'contratista_' . ($contratista['id_detalle'] ?? '0'),
                'id_detalle' => $contratista['id_detalle'] ?? null,
                'id_persona' => $contratista['id_persona'] ?? null,
                'nombre' => trim(($contratista['nombres'] ?? '') . ' ' . ($contratista['apellidos'] ?? '')),
                'nombres' => $contratista['nombres'] ?? '',
                'apellidos' => $contratista['apellidos'] ?? '',
                'cedula' => $contratista['cedula'] ?? '',
                'telefono' => $contratista['telefono'] ?? 'No registrado',
                'contrato' => $contratista['numero_contrato'] ?? '',
                'fecha_inicio' => $contratista['fecha_inicio'] ?? null,
                'fecha_final' => $contratista['fecha_final'] ?? null,
                'area' => $contratista['area'] ?? '',
                'tipo_vinculacion' => $contratista['tipo_vinculacion'] ?? '',
                'municipio_principal' => $contratista['municipio_principal'] ?? '',
                'municipio_secundario' => $contratista['municipio_secundario'] ?? '',
                'municipio_terciario' => $contratista['municipio_terciario'] ?? '',
                // Direcciones de trabajo
                'direccion_principal' => $contratista['direccion_municipio_principal'] ?? '',
                'direccion_secundaria' => $contratista['direccion_municipio_secundario'] ?? '',
                'direccion_terciaria' => $contratista['direccion_municipio_terciario'] ?? '',
                // Array de sitios de trabajo (para el mapa)
                'sitios_trabajo' => $sitios_trabajo,
                'created_at' => $contratista['created_at'] ?? null
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $resultados, // Esto siempre será un array
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

// Función para filtrar contratistas - MEJORADA
function filtrarContratistas($contratistas, $filtros) {
    // Asegurarnos de que $contratistas sea un array
    if (!is_array($contratistas)) {
        return [];
    }
    
    $resultadosFiltrados = [];
    
    foreach ($contratistas as $contratista) {
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
            $nombreCompleto = strtolower(($contratista['nombres'] ?? '') . ' ' . ($contratista['apellidos'] ?? ''));
            
            if (strpos($nombreCompleto, $nombreBusqueda) === false) {
                $coincide = false;
            }
        }
        
        // Filtrar por área
        if (!empty($filtros['area']) && ($contratista['area'] ?? '') != $filtros['area']) {
            $coincide = false;
        }
        
        // Filtrar por tipo de vinculación
        if (!empty($filtros['tipo_vinculacion']) && ($contratista['tipo_vinculacion'] ?? '') != $filtros['tipo_vinculacion']) {
            $coincide = false;
        }
        
        if ($coincide) {
            $resultadosFiltrados[] = $contratista;
        }
    }
    
    return $resultadosFiltrados;
}
?>