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
    
    // Campos obligatorios actualizados
    $requiredFields = [
        'nombre_completo', 'cedula', 'correo', 'celular',
        'id_area', 'id_tipo_vinculacion', 'id_municipio_principal',
        'numero_contrato', 'fecha_contrato', 'fecha_inicio',
        'fecha_final', 'duracion_contrato', 'direccion_municipio_principal'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es requerido");
        }
    }
    
    // ====== FUNCIÓN PARA PROCESAR ARCHIVOS ======
    function procesarArchivo($nombreCampo, $tiposPermitidos = ['pdf']) {
        if (isset($_FILES[$nombreCampo]) && $_FILES[$nombreCampo]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$nombreCampo];
            
            // Validar tamaño máximo (5MB = 5 * 1024 * 1024 bytes)
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new Exception("El archivo {$nombreCampo} excede el tamaño máximo de 5MB");
            }
            
            // Validar extensión
            $fileName = strtolower($file['name']);
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            
            if (!in_array($fileExtension, $tiposPermitidos)) {
                $tiposStr = implode(', ', array_map('strtoupper', $tiposPermitidos));
                throw new Exception("Tipo de archivo no permitido para {$nombreCampo}. Solo se aceptan: {$tiposStr}");
            }
            
            // Validar tipo MIME (opcional pero recomendado)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            // Leer archivo como binario
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                throw new Exception("No se pudo leer el archivo {$nombreCampo}");
            }
            
            return [
                'archivo' => $content,
                'nombre_original' => $file['name'],
                'tipo_mime' => $mimeType,
                'tamano' => $file['size']
            ];
        }
        return null;
    }
    
    // ====== PROCESAR TODOS LOS ARCHIVOS ======
    $cv_data = procesarArchivo('adjuntar_cv', ['pdf', 'doc', 'docx']);
    $contrato_data = procesarArchivo('adjuntar_contrato', ['pdf']);
    $acta_inicio_data = procesarArchivo('adjuntar_acta_inicio', ['pdf']);
    $rp_data = procesarArchivo('adjuntar_rp', ['pdf']);
    
    // ====== PREPARAR DATOS PARA INSERTAR ======
    $datos = [
        'nombre_completo' => trim($_POST['nombre_completo']),
        'cedula' => preg_replace('/[^0-9]/', '', $_POST['cedula']),
        'correo' => filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL),
        'celular' => preg_replace('/[^0-9]/', '', $_POST['celular']),
        'direccion' => isset($_POST['direccion']) ? trim($_POST['direccion']) : '',
        
        // Nuevos campos de dirección específicos
        'direccion_municipio_principal' => isset($_POST['direccion_municipio_principal']) ? 
                                         trim($_POST['direccion_municipio_principal']) : '',
        'direccion_municipio_secundario' => isset($_POST['direccion_municipio_secundario']) ? 
                                          trim($_POST['direccion_municipio_secundario']) : null,
        'direccion_municipio_terciario' => isset($_POST['direccion_municipio_terciario']) ? 
                                         trim($_POST['direccion_municipio_terciario']) : null,
        
        // Campos de relación
        'id_area' => (int)$_POST['id_area'],
        'id_tipo_vinculacion' => (int)$_POST['id_tipo_vinculacion'],
        'id_municipio_principal' => (int)$_POST['id_municipio_principal'],
        'id_municipio_secundario' => !empty($_POST['id_municipio_secundario']) && 
                                    $_POST['id_municipio_secundario'] != '0' ? 
                                    (int)$_POST['id_municipio_secundario'] : null,
        'id_municipio_terciario' => !empty($_POST['id_municipio_terciario']) && 
                                   $_POST['id_municipio_terciario'] != '0' ? 
                                   (int)$_POST['id_municipio_terciario'] : null,
        
        // Campos del contrato
        'numero_contrato' => trim($_POST['numero_contrato']),
        'fecha_contrato' => $_POST['fecha_contrato'],
        'fecha_inicio' => $_POST['fecha_inicio'],
        'fecha_final' => $_POST['fecha_final'],
        'duracion_contrato' => trim($_POST['duracion_contrato']),
        'numero_registro_presupuestal' => isset($_POST['numero_registro_presupuestal']) ? 
                                         trim($_POST['numero_registro_presupuestal']) : '',
        'fecha_rp' => isset($_POST['fecha_rp']) ? $_POST['fecha_rp'] : ''
    ];
    
    // ====== AGREGAR DATOS DE ARCHIVOS SI EXISTEN ======
    // CV
    if ($cv_data !== null) {
        $datos['cv_archivo'] = $cv_data['archivo'];
        $datos['cv_nombre_original'] = $cv_data['nombre_original'];
        $datos['cv_tipo_mime'] = $cv_data['tipo_mime'];
        $datos['cv_tamano'] = $cv_data['tamano'];
    }
    
    // Contrato
    if ($contrato_data !== null) {
        $datos['contrato_archivo'] = $contrato_data['archivo'];
        $datos['contrato_nombre_original'] = $contrato_data['nombre_original'];
        $datos['contrato_tipo_mime'] = $contrato_data['tipo_mime'];
        $datos['contrato_tamano'] = $contrato_data['tamano'];
    }
    
    // Acta de Inicio
    if ($acta_inicio_data !== null) {
        $datos['acta_inicio_archivo'] = $acta_inicio_data['archivo'];
        $datos['acta_inicio_nombre_original'] = $acta_inicio_data['nombre_original'];
        $datos['acta_inicio_tipo_mime'] = $acta_inicio_data['tipo_mime'];
        $datos['acta_inicio_tamano'] = $acta_inicio_data['tamano'];
    }
    
    // Registro Presupuestal
    if ($rp_data !== null) {
        $datos['rp_archivo'] = $rp_data['archivo'];
        $datos['rp_nombre_original'] = $rp_data['nombre_original'];
        $datos['rp_tipo_mime'] = $rp_data['tipo_mime'];
        $datos['rp_tamano'] = $rp_data['tamano'];
    }
    
    // ====== VALIDACIONES ADICIONALES ======
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Correo electrónico inválido");
    }
    
    if (strlen($datos['cedula']) < 5) {
        throw new Exception("Cédula inválida");
    }
    
    // Validar que las direcciones de municipios opcionales estén presentes si se seleccionó el municipio
    if ($datos['id_municipio_secundario'] && empty($datos['direccion_municipio_secundario'])) {
        throw new Exception("Debe ingresar la dirección para el municipio secundario");
    }
    
    if ($datos['id_municipio_terciario'] && empty($datos['direccion_municipio_terciario'])) {
        throw new Exception("Debe ingresar la dirección para el municipio terciario");
    }
    
    // ====== REGISTRAR CONTRATISTA ======
    $resultado = $contratistaModel->registrarContratistaCompleto($datos);
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    error_log("Error en procesar_contratista.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>