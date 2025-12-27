<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

require_once '../config/database.php';
require_once '../models/ContratistaModel.php';
require_once '../helpers/config_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Función auxiliar para correo básico
function enviarCorreoBasico($apiKey, $fromEmail, $fromName, $correoDestino, $nombreContratista, $consecutivo, $entidad, $sistema, $logo_url, $anioActual) {
    $htmlBasico = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>body {font-family: 'Segoe UI', sans-serif; line-height: 1.6; color: #333;}</style>
    </head>
    <body>
        <h2>$sistema</h2>
        <p>Estimado(a) $nombreContratista,</p>
        <p>Ha sido registrado exitosamente como contratista de la $entidad.</p>
        <p><strong>N° de Contratista:</strong> $consecutivo</p>
        <p><strong>Fecha de registro:</strong> " . date('d/m/Y') . "</p>
        <br>
        <hr>
        <p style='font-size: 12px; color: #666;'>© $anioActual $entidad</p>
    </body>
    </html>
    ";
    
    $subjectBasico = "Confirmación de Registro - Contratista #$consecutivo";
    
    $payload = [
        "sender" => ["name" => $fromName, "email" => $fromEmail],
        "to" => [["email" => $correoDestino, "name" => $nombreContratista]],
        "subject" => $subjectBasico,
        "htmlContent" => $htmlBasico
    ];
    
    return enviarPayloadBrevo($apiKey, $payload);
}

// Función para enviar payload a Brevo
function enviarPayloadBrevo($apiKey, $payload) {
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
        error_log("✅ Correo enviado exitosamente a: " . $payload['to'][0]['email']);
        return true;
    } else {
        error_log("❌ Error al enviar correo - Código: $httpCode - Respuesta: $response");
        return false;
    }
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
    
    // FUNCIÓN PARA PROCESAR ARCHIVOS (GENERAL)
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
    
    // FUNCIÓN ESPECIAL PARA PROCESAR FOTOS
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
                'image/x-png',
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
    
// NUEVA FUNCIÓN: ENVIAR CORREO DE CONFIRMACIÓN MEJORADO
function enviarCorreoConfirmacionAPI($correoDestino, $nombreContratista, $consecutivo, $contratistaModel) {
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
        
        // Configurar URLs base
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
        $logo_url = $base_url . "/imagenes/sisgoTech.png";
        $logo_footer_url = $base_url . "/imagenes/gobernacion.png";
        
        // Obtener información del sistema usando ConfigHelper
        $entidad = ConfigHelper::obtener('entidad', 'Gobernación del Meta');
        $sistema = ConfigHelper::obtener('sistema_nombre', 'Sistema SGEA');
        $anioActual = date('Y');
        
        // Obtener datos completos del contratista desde la base de datos
        $datosContratista = $contratistaModel->obtenerContratistaPorId($consecutivo);
        
        if (!$datosContratista) {
            error_log("⚠️ No se pudieron obtener datos del contratista ID: $consecutivo");
            // Si no hay datos completos, enviar correo básico
            return enviarCorreoBasico($apiKey, $fromEmail, $fromName, $correoDestino, $nombreContratista, $consecutivo, $entidad, $sistema, $logo_url, $anioActual);
        }
        
        // Obtener datos para el footer usando ConfigHelper
        $version = ConfigHelper::obtenerVersionCompleta();
        $desarrollador = ConfigHelper::obtener('desarrollado_por', 'SisgonTech');
        $direccionFooter = ConfigHelper::obtener('direccion', 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta');
        $correoContacto = ConfigHelper::obtener('correo_contacto', 'gobernaciondelmeta@meta.gov.co');
        $telefono = ConfigHelper::obtener('telefono', '(57 -608) 6 818503');
        
        // Formatear datos del contratista
        $nombreCompleto = htmlspecialchars($datosContratista['nombres'] . ' ' . $datosContratista['apellidos']);
        $identificacion = htmlspecialchars($datosContratista['cedula'] ?? 'No especificada');
        $profesion = htmlspecialchars($datosContratista['profesion'] ?? 'No especificada');
        $tipoVinculacion = htmlspecialchars($datosContratista['tipo_vinculacion_nombre'] ?? 'No especificada');
        $areaTrabajo = htmlspecialchars($datosContratista['area_nombre'] ?? 'No especificada');
        
        // Direcciones
        $direccionPrincipal = htmlspecialchars($datosContratista['direccion_municipio_principal'] ?? 'No especificada');
        $municipioPrincipal = htmlspecialchars($datosContratista['municipio_principal_nombre'] ?? 'No especificada');
        $direccionCompleta = $direccionPrincipal;
        if ($municipioPrincipal != 'No especificada') {
            $direccionCompleta .= ", $municipioPrincipal";
        }
        
        // Datos del contrato
        $numeroContrato = htmlspecialchars($datosContratista['numero_contrato'] ?? 'No especificado');
        $fechaContrato = isset($datosContratista['fecha_contrato']) ? 
            date('d/m/Y', strtotime(str_replace('/', '-', $datosContratista['fecha_contrato']))) : 'No especificada';
        $fechaInicio = isset($datosContratista['fecha_inicio']) ? 
            date('d/m/Y', strtotime(str_replace('/', '-', $datosContratista['fecha_inicio']))) : 'No especificada';
        $fechaFinal = isset($datosContratista['fecha_final']) ? 
            date('d/m/Y', strtotime(str_replace('/', '-', $datosContratista['fecha_final']))) : 'No especificada';
        $duracionContrato = htmlspecialchars($datosContratista['duracion_contrato'] ?? 'No especificada');
        
        // Calcular duración en días
        $duracionDias = "No calculada";
        if (isset($datosContratista['fecha_inicio']) && isset($datosContratista['fecha_final'])) {
            try {
                $inicio = DateTime::createFromFormat('d/m/Y', $datosContratista['fecha_inicio']);
                $final = DateTime::createFromFormat('d/m/Y', $datosContratista['fecha_final']);
                
                if ($inicio && $final) {
                    $diferencia = $inicio->diff($final);
                    $duracionDias = $diferencia->days . " días";
                }
            } catch (Exception $e) {
                $duracionDias = $duracionContrato;
            }
        }
        
        // Datos RP
        $numeroRP = !empty($datosContratista['numero_registro_presupuestal']) ? 
            htmlspecialchars($datosContratista['numero_registro_presupuestal']) : 'No aplica';
        $fechaRP = isset($datosContratista['fecha_rp']) && !empty($datosContratista['fecha_rp']) ? 
            date('d/m/Y', strtotime(str_replace('/', '-', $datosContratista['fecha_rp']))) : 'No aplica';
        
        // Asunto del correo
        $subject = "Confirmación de Registro - Contratista #$consecutivo - $entidad";
        
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
                    max-width: 700px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #ffffff;
                }
                .container {
                    background: white;
                    border-radius: 5px;
                    overflow: hidden;
                    border: 1px solid #ddd;
                }
                .header {
                    padding: 25px 20px;
                    text-align: center;
                    border-bottom: 1px solid #eee;
                    background: white;
                }
                .logo {
                    max-width: 150px;
                    height: auto;
                    margin-bottom: 10px;
                }
                .content {
                    padding: 25px;
                }
                .section {
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #eee;
                }
                .section:last-child {
                    border-bottom: none;
                }
                .section-title {
                    font-size: 16px;
                    color: #333;
                    font-weight: bold;
                    margin-bottom: 15px;
                    padding-bottom: 5px;
                    border-bottom: 1px solid #ddd;
                }
                .data-item {
                    margin-bottom: 10px;
                    line-height: 1.4;
                }
                .data-label {
                    font-weight: 600;
                    color: #000000;
                    display: inline;
                    margin-right: 5px;
                }
                .data-value {
                    color: #000000;
                    display: inline;
                }
                .saludo {
                    font-size: 15px;
                    line-height: 1.6;
                    margin-bottom: 20px;
                }
                .footer-email {
                    background: #f5f5f5;
                    padding: 20px;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                    border-top: 1px solid #ddd;
                }
                .footer-logo-container {
                    margin-bottom: 10px;
                }
                .license-logo {
                    max-width: 60px;
                    height: auto;
                    opacity: 0.7;
                }
                .footer-line {
                    margin: 5px 0;
                    line-height: 1.4;
                }
                .footer-strong {
                    font-weight: bold;
                    color: #333;
                }
                @media (max-width: 480px) {
                    .content { padding: 15px; }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='$logo_url' alt='Logo $entidad' class='logo'>
                    <h2 style='margin: 0 0 5px 0; font-size: 18px;'>$sistema</h2>
                    <p style='margin: 0; color: #666; font-size: 13px;'>Sistema de Gestión y Enrutamiento Administrativo</p>
                </div>
                
                <div class='content'>
                    <div class='saludo'>
                        <p>Estimado(a) <strong>$nombreContratista</strong>,</p>
                        <p>Le informamos que ha sido <strong>registrado exitosamente</strong> en el sistema de contratación de la <strong>$entidad</strong>.</p>
                    </div>
                    
                    <!-- SECCIÓN 1: DATOS PERSONALES -->
                    <div class='section'>
                        <div class='section-title'>DATOS PERSONALES</div>
                        <div class='data-item'><span class='data-label'>Nombre completo:</span> <span class='data-value'>$nombreCompleto</span></div>
                        <div class='data-item'><span class='data-label'>Identificación:</span> <span class='data-value'>$identificacion</span></div>
                        <div class='data-item'><span class='data-label'>Profesión:</span> <span class='data-value'>$profesion</span></div>
                        <div class='data-item'><span class='data-label'>Tipo de vinculación:</span> <span class='data-value'>$tipoVinculacion</span></div>
                        <div class='data-item'><span class='data-label'>Área de trabajo:</span> <span class='data-value'>$areaTrabajo</span></div>
                        <div class='data-item'><span class='data-label'>Dirección:</span> <span class='data-value'>$direccionCompleta</span></div>
                    </div>
                    
                    <!-- SECCIÓN 2: DATOS DEL CONTRATO -->
                    <div class='section'>
                        <div class='section-title'>DATOS DEL CONTRATO</div>
                        <div class='data-item'><span class='data-label'>Número de contrato:</span> <span class='data-value'>$numeroContrato</span></div>
                        <div class='data-item'><span class='data-label'>Fecha del contrato:</span> <span class='data-value'>$fechaContrato</span></div>
                        <div class='data-item'><span class='data-label'>Fecha de inicio:</span> <span class='data-value'>$fechaInicio</span></div>
                        <div class='data-item'><span class='data-label'>Fecha de terminación:</span> <span class='data-value'>$fechaFinal</span></div>
                        <div class='data-item'><span class='data-label'>Duración del contrato:</span> <span class='data-value'>$duracionContrato ($duracionDias)</span></div>
                        <div class='data-item'><span class='data-label'>Número RP:</span> <span class='data-value'>$numeroRP</span></div>
                        <div class='data-item'><span class='data-label'>Fecha RP:</span> <span class='data-value'>$fechaRP</span></div>
                    </div>
                    
                    <p style='font-size: 14px; margin-top: 20px;'>
                        Si tiene alguna pregunta o requiere asistencia, por favor comuníquese con el área de contratación de la secretaría.
                    </p>
                    
                    <p style='font-size: 14px; margin-top: 20px;'>
                        Atentamente,<br>
                        <strong>Equipo de Contratación</strong><br>
                        $entidad
                    </p>
                </div>
                
                <!-- FOOTER CON RUTA DIRECTA -->
                <div class='footer-email'>
                    <div class='footer-logo-container'>
                        <img src='$logo_footer_url' 
                            alt='$entidad' 
                            class='license-logo'>
                    </div>
                    
                    <!-- Primera línea -->
                    <div class='footer-line'>
                        © $anioActual $entidad $version® desarrollado por 
                        <span class='footer-strong'>$desarrollador</span>
                    </div>
                    
                    <!-- Segunda línea -->
                    <div class='footer-line'>
                        $direccionFooter - Asesores e-Governance Solutions para Entidades Públicas $anioActual® 
                        By: Ing. Rubén Darío González García $telefono. Contacto: <span class='footer-strong'>$correoContacto</span> - Reservados todos los derechos de autor.
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Preparar payload y enviar
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
            "subject" => $subject,
            "htmlContent" => $htmlContent
        ];
        
        return enviarPayloadBrevo($apiKey, $payload);
        
    } catch (Exception $e) {
        error_log("❌ Excepción al enviar correo: " . $e->getMessage());
        return false;
    }
}
    
    // PROCESAR TODOS LOS ARCHIVOS
    $cv_data = procesarArchivo('adjuntar_cv', ['pdf', 'doc', 'docx']);
    $contrato_data = procesarArchivo('adjuntar_contrato', ['pdf']);
    $acta_inicio_data = procesarArchivo('adjuntar_acta_inicio', ['pdf']);
    $rp_data = procesarArchivo('adjuntar_rp', ['pdf']);
    $foto_data = procesarFoto('foto_perfil'); // Nuevo: Foto de perfil
    
    // PREPARAR DATOS PARA INSERTAR
    $datos = [
        'nombre_completo' => trim($_POST['nombre_completo']),
        'cedula' => preg_replace('/[^0-9]/', '', $_POST['cedula']),
        'correo' => filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL),
        'celular' => preg_replace('/[^0-9]/', '', $_POST['celular']),
        'profesion' => isset($_POST['profesion']) ? trim($_POST['profesion']) : null,
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
    
    // PREPARAR ARCHIVOS PARA ENVIAR
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
    
    // VALIDACIONES ADICIONALES
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
    
    // REGISTRAR CONTRATISTA
    $resultado = $contratistaModel->registrarContratistaCompleto($datos, $archivos);
    
    // ENVIAR CORREO DE CONFIRMACIÓN DESPUÉS DE GUARDAR EXITOSAMENTE
    if ($resultado['success']) {
        // Obtener el ID del contratista registrado
        $idDetalle = $resultado['id_detalle'] ?? null;
        
        if ($idDetalle) {
            // Enviar correo de confirmación con datos completos de la BD
            $correoEnviado = enviarCorreoConfirmacionAPI(
                $datos['correo'],
                $datos['nombre_completo'],
                $idDetalle,
                $contratistaModel
            );
            
            // Agregar información del correo al resultado
            $resultado['correo_enviado'] = $correoEnviado;
            
            // Log del resultado del correo
            if ($correoEnviado) {
                error_log("✅ Correo de confirmación enviado exitosamente a: " . $datos['correo']);
            } else {
                error_log("⚠️ Contratista registrado pero falló el envío de correo a: " . $datos['correo']);
            }
        } else {
            error_log("⚠️ No se pudo obtener ID del contratista para enviar correo");
            $resultado['correo_enviado'] = false;
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