<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

require_once '../config/database.php';
require_once '../models/ContratistaModel.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->conectar();
    $contratistaModel = new ContratistaModel($db);
    
    // Obtener todos los contratistas para el mapa
    $contratistas = $contratistaModel->obtenerContratistasParaMapa();
    
    // Si no hay contratistas
    if (empty($contratistas)) {
        echo json_encode([]);
        exit();
    }
    
    // Procesar los datos para el mapa
    $resultados = [];
    foreach ($contratistas as $contratista) {
        // Crear un ID único para el marcador
        $markerId = 'contratista_' . $contratista['id_detalle'];
        
        // Formatear la información para el popup
        $popupContent = "
            <div class='popup-contratista'>
                <h4><strong>👤 " . htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']) . "</strong></h4>
                <p><strong>Cédula:</strong> " . htmlspecialchars($contratista['cedula']) . "</p>
                <p><strong>Teléfono:</strong> " . htmlspecialchars($contratista['telefono']) . "</p>
                <p><strong>Área:</strong> " . htmlspecialchars($contratista['area']) . "</p>
                <p><strong>Contrato:</strong> " . htmlspecialchars($contratista['numero_contrato']) . "</p>
                <p><strong>Municipio Principal:</strong> " . htmlspecialchars($contratista['municipio_principal']) . "</p>
                <p><strong>Dirección:</strong> " . htmlspecialchars($contratista['direccion']) . "</p>
            </div>
        ";
        
        $resultados[] = [
            'id' => $markerId,
            'nombre' => $contratista['nombres'] . ' ' . $contratista['apellidos'],
            'cedula' => $contratista['cedula'],
            'contrato' => $contratista['numero_contrato'],
            'area' => $contratista['area'],
            'municipio' => $contratista['municipio_principal'],
            'direccion' => $contratista['direccion'],
            'popup_content' => $popupContent,
            // Nota: Necesitaremos geocodificar la dirección para obtener lat/lng
            // Por ahora, usaremos coordenadas aproximadas del municipio
            'lat' => null, // Se obtendrá por geocodificación
            'lng' => null  // Se obtendrá por geocodificación
        ];
    }
    
    echo json_encode($resultados);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener datos: ' . $e->getMessage()]);
}
?>  