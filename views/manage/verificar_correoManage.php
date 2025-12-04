<?php
session_start(); 

require_once '../../config/database.php';
require_once '../../models/usuario.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['correo']) || empty(trim($_POST['correo']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Correo no proporcionado']);
    exit;
}
$correo = filter_var(trim($_POST['correo']), FILTER_VALIDATE_EMAIL);
if (!$correo) {
    http_response_code(400);
    echo json_encode(['error' => 'Correo inválido']);
    exit;
}

try {
    $database = new Database();
    $db = $database->conectar();

    $usuarioModel = new Usuario($db);
    
    $existe = $usuarioModel->existeCorreo($correo);
    
    echo json_encode(['existe' => $existe]);
    
} catch (PDOException $e) {
    error_log("Error en verificar_correoManage.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
    exit;
} catch (Exception $e) {
    error_log("Error general en verificar_correoManage.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
    exit;
}
?>