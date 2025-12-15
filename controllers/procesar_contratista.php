<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

require_once '../config/database.php';
require_once '../models/ContratistaModel.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

try {
    $database = new Database();
    $db = $database->conectar();
    
    $contratistaModel = new ContratistaModel($db);
    
    $requiredFields = [
        'nombre_completo', 'cedula', 'correo', 'celular',
        'id_area', 'id_tipo_vinculacion', 'id_municipio_principal',
        'numero_contrato', 'fecha_contrato', 'fecha_inicio',
        'fecha_final', 'duracion_contrato'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }
    
    $datos = [
        'nombre_completo' => trim($_POST['nombre_completo']),
        'cedula' => preg_replace('/[^0-9]/', '', $_POST['cedula']),
        'correo' => filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL),
        'celular' => preg_replace('/[^0-9]/', '', $_POST['celular']),
        'direccion' => isset($_POST['direccion']) ? trim($_POST['direccion']) : '',
        'id_area' => (int)$_POST['id_area'],
        'id_tipo_vinculacion' => (int)$_POST['id_tipo_vinculacion'],
        'id_municipio_principal' => (int)$_POST['id_municipio_principal'],
        'id_municipio_secundario' => !empty($_POST['id_municipio_secundario']) && $_POST['id_municipio_secundario'] != '0' ? (int)$_POST['id_municipio_secundario'] : null,
        'id_municipio_terciario' => !empty($_POST['id_municipio_terciario']) && $_POST['id_municipio_terciario'] != '0' ? (int)$_POST['id_municipio_terciario'] : null,
        'numero_contrato' => trim($_POST['numero_contrato']),
        'fecha_contrato' => $_POST['fecha_contrato'],
        'fecha_inicio' => $_POST['fecha_inicio'],
        'fecha_final' => $_POST['fecha_final'],
        'duracion_contrato' => trim($_POST['duracion_contrato']),
        'numero_registro_presupuestal' => isset($_POST['numero_registro_presupuestal']) ? trim($_POST['numero_registro_presupuestal']) : '',
        'fecha_rp' => isset($_POST['fecha_rp']) ? $_POST['fecha_rp'] : ''
    ];
    
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo electrónico inválido");
    }
    
    if (strlen($datos['cedula']) < 5) {
        throw new Exception("Cédula inválida");
    }
    
    $resultado = $contratistaModel->registrarContratistaCompleto($datos);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>