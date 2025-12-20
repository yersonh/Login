<?php
// contratistas_mapa.php - VERSIÓN CON FILTROS
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
    
    // ================= ACCIÓN: OBTENER MUNICIPIOS =================
    if (isset($_GET['action']) && $_GET['action'] === 'municipios') {
        $sql = "SELECT DISTINCT m1.nombre AS municipio 
                FROM detalle_contrato dc
                JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
                WHERE dc.direccion IS NOT NULL AND dc.direccion != ''
                ORDER BY m1.nombre";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(array_column($municipios, 'municipio'));
        exit();
    }
    
    // ================= OBTENER CONTRATISTAS =================
    $municipioFiltro = isset($_GET['municipio']) && $_GET['municipio'] !== 'todos' 
        ? $_GET['municipio'] 
        : null;
    
    // Obtener todos los contratistas
    $contratistas = $contratistaModel->obtenerContratistasParaMapa();
    
    // Aplicar filtro por municipio si existe
    if ($municipioFiltro) {
        $contratistas = array_filter($contratistas, function($c) use ($municipioFiltro) {
            return isset($c['municipio_principal']) && $c['municipio_principal'] === $municipioFiltro;
        });
        $contratistas = array_values($contratistas); // Reindexar
    }
    
    // Si no hay contratistas después del filtro
    if (empty($contratistas)) {
        $mensaje = $municipioFiltro 
            ? "No hay contratistas en $municipioFiltro" 
            : 'No hay contratistas con direcciones registradas';
        
        echo json_encode([
            'success' => true,
            'data' => [],
            'filtro' => $municipioFiltro,
            'total' => 0,
            'message' => $mensaje,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
    
    // ================= PROCESAR CONTRATISTAS =================
    $resultados = [];
    foreach ($contratistas as $contratista) {
        $resultados[] = [
            'id' => 'contratista_' . $contratista['id_detalle'],
            'nombre' => $contratista['nombres'] . ' ' . $contratista['apellidos'],
            'cedula' => $contratista['cedula'],
            'telefono' => $contratista['telefono'] ?? 'No registrado',
            'contrato' => $contratista['numero_contrato'],
            'area' => $contratista['area'],
            'municipio' => $contratista['municipio_principal'],
            'direccion' => $contratista['direccion'],
            'popup_content' => "
                <div class='popup-contratista'>
                    <h4><strong>👤 " . htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']) . "</strong></h4>
                    <p><strong>Cédula:</strong> " . htmlspecialchars($contratista['cedula']) . "</p>
                    <p><strong>Teléfono:</strong> " . htmlspecialchars($contratista['telefono'] ?? 'No registrado') . "</p>
                    <p><strong>Área:</strong> " . htmlspecialchars($contratista['area']) . "</p>
                    <p><strong>Contrato:</strong> " . htmlspecialchars($contratista['numero_contrato']) . "</p>
                    <p><strong>Municipio:</strong> " . htmlspecialchars($contratista['municipio_principal']) . "</p>
                    <p><strong>Dirección:</strong> " . htmlspecialchars($contratista['direccion']) . "</p>
                </div>
            "
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $resultados,
        'filtro' => $municipioFiltro,
        'total' => count($resultados),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}
?>