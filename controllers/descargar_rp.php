<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo "Acceso no autorizado";
    exit();
}

require_once '../config/database.php';
require_once '../models/ContratistaModel.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "ID no especificado";
    exit();
}

$id_detalle = (int)$_GET['id'];

try {
    $database = new Database();
    $db = $database->conectar();
    $contratistaModel = new ContratistaModel($db);
    
    $rp = $contratistaModel->obtenerRP($id_detalle);
    
    if (!$rp || empty($rp['rp_archivo'])) {
        http_response_code(404);
        echo "Registro presupuestal no encontrado";
        exit();
    }
    
    // Configurar headers para descarga
    header('Content-Type: ' . $rp['rp_tipo_mime']);
    header('Content-Disposition: attachment; filename="' . $rp['rp_nombre_original'] . '"');
    header('Content-Length: ' . $rp['rp_tamano']);
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Enviar el contenido binario
    echo $rp['rp_archivo'];
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Error al descargar el registro presupuestal: " . $e->getMessage();
    exit();
}
?>