<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

require_once '../config/database.php';
require_once '../models/ContratistaModel.php';

// AGREGAR: ConfigHelper para obtener información del sistema
require_once '../helpers/config_helper.php';

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
    
    // Campos obligatorios actualizados (incluyendo profesion)
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
    
    // ====== FUNCIÓN PARA PROCESAR ARCHIVOS (GENERAL) ======
    function procesarArchivo($nombreCampo, $tiposPermitidos = ['pdf'], $maxSizeMB = 5) {
        if (isset($_FILES[$nombreCampo]) && $_FILES[$nombreCampo]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$nombreCampo];
            
            // Validar tamaño máximo
            $maxSize = $maxSizeMB * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new Exception("El archivo {$nombreCampo} excede el tamaño máximo de {$maxSizeMB}MB");
            }
            
            // Validar extensión
            $fileName = strtolower($file['name']);
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            
            if (!in_array($fileExtension, $tiposPermitidos)) {
                $tiposStr = implode(', ', array_map('strtoupper', $tiposPermitidos));
                throw new Exception("Tipo de archivo no permitido para {$nombreCampo}. Solo se aceptan: {$tiposStr}");
            }
            
            // Leer archivo como binario
            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                throw new Exception("No se pudo leer el archivo {$nombreCampo}");
            }
            
            // Obtener tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            return [
                'archivo' => $content,
                'nombre_original' => $file['name'],
                'tipo_mime' => $mimeType,
                'tamano' => $file['size']
            ];
        }
        return null;
    }
    
    // ====== FUNCIÓN ESPECIAL PARA PROCESAR FOTOS ======
    function procesarFoto($nombreCampo, $maxSizeMB = 10) {
    if (isset($_FILES[$nombreCampo]) && $_FILES[$nombreCampo]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$nombreCampo];
        
        // Log para debug
        error_log("Procesando foto: " . $file['name'] . " - Tamaño: " . $file['size'] . " bytes");
        error_log("Tipo reportado por PHP: " . $file['type']);
        
        // Validar tamaño máximo
        $maxSize = $maxSizeMB * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("La foto de perfil excede el tamaño máximo de {$maxSizeMB}MB");
        }
        
        // Validar tipo de imagen por extensión (PRINCIPAL)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'jfif'];
        $fileName = strtolower($file['name']);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $tiposStr = implode(', ', array_map('strtoupper', $allowedExtensions));
            throw new Exception("Tipo de imagen no permitido. Solo se aceptan: {$tiposStr}");
        }
        
        // Validar MIME type con opciones más flexibles
        $allowedMimeTypes = [
            'image/jpeg', 
            'image/pjpeg', 
            'image/jpg',
            'image/png', 
            'image/x-png',  // MIME type alternativo para PNG
            'image/gif',
            'image/x-gif'
        ];
        
        // Usar fileinfo como respaldo si mime_content_type falla
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file['tmp_name']);
        } else {
            $mimeType = $file['type'];
        }
        
        // Log del MIME type detectado
        error_log("MIME type detectado: " . $mimeType);
        
        // Validación más flexible: aceptar si pasa por extensión O por MIME type
        $extensionValida = in_array($fileExtension, $allowedExtensions);
        $mimeTypeValido = in_array($mimeType, $allowedMimeTypes);
        
        if (!$extensionValida && !$mimeTypeValido) {
            error_log("Validación fallida - Extensión: $fileExtension, MIME: $mimeType");
            throw new Exception("Tipo de archivo no permitido para la foto. Extensión: $fileExtension, Tipo: $mimeType");
        }
        
        // Leer archivo como binario
        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            throw new Exception("No se pudo leer la foto");
        }
        
        // Verificar que realmente sea una imagen válida
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new Exception("El archivo no es una imagen válida");
        }
        
        return [
            'archivo' => $content,
            'nombre_original' => $file['name'],
            'tipo_mime' => $mimeType
        ];
    }
    return null;
}
    
    // ====== NUEVA FUNCIÓN: ENVIAR CORREO DE CONFIRMACIÓN ======
    function enviarCorreoConfirmacionAPI($correoDestino, $nombreContratista, $consecutivo) {
        try {
            // Obtener API Key de las variables de entorno
            $apiKey = getenv('BREVO_API_KEY');
            if (!$apiKey) {
                error_log("❌ BREVO_API_KEY no configurada");
                return false;
            }
            
            // Obtener configuración del remitente
            $fromEmail = getenv('SMTP_FROM');
            $fromName = getenv('SMTP_FROM_NAME');
            
            if (!$fromEmail) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $fromEmail = 'no-reply@' . $_SERVER['HTTP_HOST'];
            }
            if (!$fromName) {
                $fromName = 'Sistema SGEA - Secretaría de Minas y Energía';
            }
            
            // Configurar la URL base para el logo
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
            $logo_url = $base_url . "/imagenes/gobernacion.png";
            
            // Obtener información del sistema
            $entidad = ConfigHelper::obtener('entidad', 'Gobernación del Meta');
            $secretaria = ConfigHelper::obtener('secretaria', 'Secretaría de Minas y Energía');
            $sistema = ConfigHelper::obtener('sistema_nombre', 'Sistema SGEA');
            $anioActual = date('Y');
            $fechaActual = date('d/m/Y');
            $horaActual = date('h:i A');
            
            // Generar contenido HTML del correo
            $htmlContent = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body {
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                        background-color: #f8f9fa;
                    }
                    .container {
                        background: white;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                        border: 1px solid #e0e0e0;
                    }
                    .header {
                        padding: 25px 20px;
                        text-align: center;
                        border-bottom: 1px solid #e0e0e0;
                        background: #ffffff;
                    }
                    .logo {
                        max-width: 180px;
                        height: auto;
                        margin-top: 15px;
                    }
                    .content {
                        padding: 30px;
                    }
                    .info-box {
                        background: #f8f9fa;
                        border-left: 4px solid #1e8ee9;
                        padding: 15px;
                        margin: 20px 0;
                        border-radius: 0 4px 4px 0;
                    }
                    .footer {
                        background: #f8f9fa;
                        padding: 20px;
                        text-align: center;
                        color: #666;
                        font-size: 13px;
                        border-top: 1px solid #e9ecef;
                    }
                    .highlight {
                        background: #e8f4fd;
                        padding: 10px 15px;
                        border-radius: 4px;
                        margin: 15px 0;
                        border-left: 3px solid #1e8ee9;
                    }
                    @media (max-width: 480px) {
                        .content { padding: 20px; }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2 style='color: #333; margin: 0 0 5px 0; font-size: 20px;'>
                            $sistema
                        </h2>
                        <p style='color: #666; margin: 0 0 15px 0; font-size: 14px;'>
                            Sistema de Gestión y Enrutamiento Administrativo
                        </p>
                        <img src='$logo_url' alt='Logo $entidad' class='logo' style='max-width: 180px;'>
                    </div>
                    
                    <div class='content'>
                        <p style='font-size: 15px;'>
                            Estimado(a) <strong>$nombreContratista</strong>,
                        </p>
                        
                        <p style='font-size: 15px;'>
                            Le informamos que ha sido <strong>registrado exitosamente</strong> 
                            en el sistema de contratistas de la <strong>$secretaria</strong> 
                            de la <strong>$entidad</strong>.
                        </p>
                        
                        <div class='info-box'>
                            <h3 style='color: #1e8ee9; margin-top: 0;'>Información de registro:</h3>
                            <p><strong>N° de Contratista:</strong> $consecutivo</p>
                            <p><strong>Fecha de registro:</strong> $fechaActual</p>
                            <p><strong>Hora de registro:</strong> $horaActual</p>
                        </div>
                        
                        <div class='highlight'>
                            <p style='margin: 0;'>
                                <strong>📋 Importante:</strong> Este registro le permite 
                                acceder a los servicios y seguimiento de sus contratos 
                                ante la secretaría.
                            </p>
                        </div>
                        
                        <p style='font-size: 15px;'>
                            Si tiene alguna pregunta o requiere asistencia, por favor 
                            comuníquese con el área de contratación de la secretaría.
                        </p>
                        
                        <p style='font-size: 15px; margin-top: 25px;'>
                            Atentamente,<br>
                            <strong>Equipo de Contratación</strong><br>
                            $secretaria<br>
                            $entidad
                        </p>
                    </div>
                    
                    <div class='footer'>
                        <p style='margin: 5px 0;'><strong>$sistema</strong></p>
                        <p style='margin: 5px 0; font-size: 12px;'>$entidad - $secretaria</p>
                        <p style='margin-top: 15px; font-size: 11px; color: #999;'>
                            Este es un mensaje automático generado por el sistema.<br>
                            Favor no responder a esta dirección de correo.<br>
                            &copy; $anioActual $entidad. Todos los derechos reservados.
                        </p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Preparar payload para la API de Brevo
            $payload = [
                "sender" => [
                    "name"  => $fromName,
                    "email" => $fromEmail
                ],
                "to" => [
                    [
                        "email" => $correoDestino,
                        "name"  => $nombreContratista
                    ]
                ],
                "subject" => "Confirmación de Registro - Contratista N° $consecutivo",
                "htmlContent" => $htmlContent
            ];
            
            // Enviar usando cURL a la API de Brevo
            $ch = curl_init("https://api.brevo.com/v3/smtp/email");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: application/json",
                "Content-Type: application/json",
                "api-key: $apiKey"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("✅ Correo enviado exitosamente a: $correoDestino");
                return true;
            } else {
                error_log("❌ Error al enviar correo - Código: $httpCode - Respuesta: $response");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Excepción al enviar correo: " . $e->getMessage());
            return false;
        }
    }
    // ====== FIN NUEVA FUNCIÓN ======
    
    // ====== PROCESAR TODOS LOS ARCHIVOS ======
    $cv_data = procesarArchivo('adjuntar_cv', ['pdf', 'doc', 'docx']);
    $contrato_data = procesarArchivo('adjuntar_contrato', ['pdf']);
    $acta_inicio_data = procesarArchivo('adjuntar_acta_inicio', ['pdf']);
    $rp_data = procesarArchivo('adjuntar_rp', ['pdf']);
    $foto_data = procesarFoto('foto_perfil'); // Nuevo: Foto de perfil
    
    // ====== PREPARAR DATOS PARA INSERTAR ======
    $datos = [
        'nombre_completo' => trim($_POST['nombre_completo']),
        'cedula' => preg_replace('/[^0-9]/', '', $_POST['cedula']),
        'correo' => filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL),
        'celular' => preg_replace('/[^0-9]/', '', $_POST['celular']),
        'profesion' => isset($_POST['profesion']) ? trim($_POST['profesion']) : null, // NUEVO CAMPO
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
    
    // ====== PREPARAR ARCHIVOS PARA ENVIAR ======
    $archivos = [];
    
    // Foto de perfil (se maneja aparte en el modelo)
    if ($foto_data !== null) {
        $archivos['foto_perfil'] = $_FILES['foto_perfil'];
    }
    
    // CV
    if ($cv_data !== null) {
        $datos['cv_archivo'] = $cv_data['archivo'];
        $datos['cv_nombre_original'] = $cv_data['nombre_original'];
        $datos['cv_tipo_mime'] = $cv_data['tipo_mime'];
        $datos['cv_tamano'] = $cv_data['tamano'];
        $archivos['adjuntar_cv'] = $_FILES['adjuntar_cv'];
    }
    
    // Contrato
    if ($contrato_data !== null) {
        $datos['contrato_archivo'] = $contrato_data['archivo'];
        $datos['contrato_nombre_original'] = $contrato_data['nombre_original'];
        $datos['contrato_tipo_mime'] = $contrato_data['tipo_mime'];
        $datos['contrato_tamano'] = $contrato_data['tamano'];
        $archivos['adjuntar_contrato'] = $_FILES['adjuntar_contrato'];
    }
    
    // Acta de Inicio
    if ($acta_inicio_data !== null) {
        $datos['acta_inicio_archivo'] = $acta_inicio_data['archivo'];
        $datos['acta_inicio_nombre_original'] = $acta_inicio_data['nombre_original'];
        $datos['acta_inicio_tipo_mime'] = $acta_inicio_data['tipo_mime'];
        $datos['acta_inicio_tamano'] = $acta_inicio_data['tamano'];
        $archivos['adjuntar_acta_inicio'] = $_FILES['adjuntar_acta_inicio'];
    }
    
    // Registro Presupuestal
    if ($rp_data !== null) {
        $datos['rp_archivo'] = $rp_data['archivo'];
        $datos['rp_nombre_original'] = $rp_data['nombre_original'];
        $datos['rp_tipo_mime'] = $rp_data['tipo_mime'];
        $datos['rp_tamano'] = $rp_data['tamano'];
        $archivos['adjuntar_rp'] = $_FILES['adjuntar_rp'];
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
    $resultado = $contratistaModel->registrarContratistaCompleto($datos, $archivos);
    
    // ====== ENVIAR CORREO DE CONFIRMACIÓN DESPUÉS DE GUARDAR EXITOSAMENTE ======
    if ($resultado['success']) {
        // Obtener el consecutivo del resultado o usar el ID
        $consecutivo = $resultado['id_detalle'] ?? $resultado['consecutivo'] ?? 'N/A';
        
        // Enviar correo de confirmación
        $correoEnviado = enviarCorreoConfirmacionAPI(
            $datos['correo'],
            $datos['nombre_completo'],
            $consecutivo
        );
        
        // Agregar información del correo al resultado
        $resultado['correo_enviado'] = $correoEnviado;
        
        // Log del resultado del correo
        if ($correoEnviado) {
            error_log("✅ Correo de confirmación enviado exitosamente a: " . $datos['correo']);
        } else {
            error_log("⚠️ Contratista registrado pero falló el envío de correo a: " . $datos['correo']);
        }
    }
    
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