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
    
    $cv = $contratistaModel->obtenerCV($id_detalle);
    
    if (!$cv || empty($cv['cv_archivo'])) {
        http_response_code(404);
        echo "CV no encontrado";
        exit();
    }
    
    // Configurar headers para descarga
    header('Content-Type: ' . $cv['cv_tipo_mime']);
    header('Content-Disposition: attachment; filename="' . $cv['cv_nombre_original'] . '"');
    header('Content-Length: ' . $cv['cv_tamano']);
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Enviar el contenido binario
    echo $cv['cv_archivo'];
    exit();
    
} catch (Exception $e) {
    http_response_code(500);
    echo "Error al descargar el CV: " . $e->getMessage();
    exit();
}
?>