<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}
require_once '../models/MunicipioModel.php';

header('Content-Type: application/json');

try {
    $municipioModel = new MunicipioModel();
    
    // Si se pasa un ID específico
    if (isset($_GET['id'])) {
        $municipio = $municipioModel->obtenerPorId($_GET['id']);
        
        if ($municipio) {
            echo json_encode([
                'success' => true,
                'data' => $municipio
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Municipio no encontrado'
            ]);
        }
        exit();
    }
    
    // Obtener todos los municipios activos
    $municipios = $municipioModel->obtenerMunicipiosActivos();
    
    // Si se busca por nombre
    if (isset($_GET['buscar'])) {
        $municipios = $municipioModel->buscarPorNombre($_GET['buscar']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $municipios,
        'count' => count($municipios)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener municipios',
        'message' => $e->getMessage()
    ]);
}
?>