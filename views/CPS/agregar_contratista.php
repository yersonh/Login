<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

require_once '../../config/database.php';
require_once '../../models/AreaModel.php';
require_once '../../models/MunicipioModel.php';
require_once '../../models/TipoVinculacionModel.php';

// NOTA: Ya tienes las variables BREVO_API_KEY, SMTP_FROM, SMTP_FROM_NAME en Railway

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    $redireccion = match($_SESSION['tipo_usuario'] ?? '') {
        'administrador', 'usuario' => '../menu.php',
        default => '../../index.php'
    };
    header("Location: $redireccion");
    exit();
}

$nombreUsuario = htmlspecialchars($_SESSION['nombres'] ?? '');
$apellidoUsuario = htmlspecialchars($_SESSION['apellidos'] ?? '');
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
$nombreCompleto = empty($nombreCompleto) ? 'Usuario del Sistema' : $nombreCompleto;

try {
    $database = new Database();
    $db = $database->conectar();
    
    $areaModel = new AreaModel($db);
    $municipioModel = new MunicipioModel($db);
    $tipoModel = new TipoVinculacionModel($db);
    
    $areas = $areaModel->obtenerAreasActivas();
    $municipios = $municipioModel->obtenerMunicipiosActivos();
    $tiposVinculacion = $tipoModel->obtenerTiposActivos();
    
    function generarConsecutivo($db) {
    try {
        // Obtener el máximo actual del id_detalle
        $sql = "SELECT MAX(id_detalle) AS ultimo FROM detalle_contrato";
        $stmt = $db->query($sql);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si no hay registros, empezamos en 1
        $ultimo = $fila['ultimo'];

        if ($ultimo === null) {
            return 1;
        }

        // De lo contrario sumamos +1 al máximo encontrado
        return $ultimo + 1;

    } catch (Exception $e) {
        error_log("Error al generar consecutivo: " . $e->getMessage());
        return 1;
    }
}

    $consecutivo = generarConsecutivo($db);
    
} catch (Exception $e) {
    error_log("Error al cargar datos del formulario: " . $e->getMessage());
    die("Error al cargar el formulario. Por favor contacte al administrador.");
}

// ============== NUEVA FUNCIÓN PARA ENVIAR CORREO CON API DE BREVO ==============
/**
 * Función para enviar correo de confirmación usando API de Brevo
 */
function enviarCorreoConfirmacionAPI($correoDestino, $nombreContratista, $consecutivo) {
    try {
        // Obtener API Key de las variables de entorno
        $apiKey = getenv('BREVO_API_KEY');
        if (!$apiKey) {
            error_log("❌ BREVO_API_KEY no configurada");
            return false;
        }
        
        // Obtener configuración del remitente
        $fromEmail = getenv('SMTP_FROM') ?: 'no-reply@' . $_SERVER['HTTP_HOST'];
        $fromName = getenv('SMTP_FROM_NAME') ?: 'Sistema SGEA - Secretaría de Minas y Energía';
        
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
// ============== FIN NUEVA FUNCIÓN ==============

// Verificar si hay un POST (para cuando se procese el formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_formulario'])) {
    // Aquí va la lógica para procesar el formulario
    // Esto se ejecuta cuando tu JavaScript envía los datos
    
    // Ejemplo de cómo se usaría:
    /*
    $nombreContratista = $_POST['nombre_completo'] ?? '';
    $correoContratista = $_POST['correo'] ?? '';
    
    // 1. Guardar contratista en la BD
    // $resultado = guardarContratistaEnBD($_POST);
    
    // 2. Si se guardó exitosamente, enviar correo
    if ($resultado['success']) {
        $correoEnviado = enviarCorreoConfirmacionAPI(
            $correoContratista,
            $nombreContratista,
            $resultado['consecutivo']
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Contratista registrado' . ($correoEnviado ? ' y correo enviado' : ' (pero falló correo)'),
            'consecutivo' => $resultado['consecutivo']
        ]);
    }
    */
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Contratista - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../styles/agregar_contratista.css">
</head>
<body>
    
    <div class="app-container">
        <header class="app-header">
            <div class="header-content">
                <div class="department-info">
                    <h1>GOBERNACIÓN DEL META</h1>
                    <h2>Secretaría de Minas y Energía</h2>
                </div>
                <div class="user-profile">
                    <div class="welcome-user">
                        <i class="fas fa-user-circle"></i>
                        <span>Bienvenido(a) </span>
                        <strong style="font-size: 25px; font-weight: bold;">
                            <?php echo htmlspecialchars($nombreCompleto); ?>
                        </strong>
                    </div>
                    <div class="user-role">Asistente</div>
                </div>
            </div>
        </header>
        
        <main class="app-main">
            <div class="welcome-section">
                <h3>Registrar Contratista / CPS</h3>
            </div>
            
            <div class="form-container">
                <!-- Añadir CSRF token oculto -->
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32)); ?>">
                <!-- Campo oculto para identificar el envío -->
                <input type="hidden" name="procesar_formulario" value="1">
                
                <div class="consecutivo-display">
                    <i class="fa-solid fa-user"></i> <strong>Contratista N°:</strong>
                    <span class="consecutivo-number"><?php echo $consecutivo; ?></span>
                </div>
                
                <div class="datetime-display">
                    <i class="fas fa-clock"></i> Ahora: 
                    <?php echo date('d/m/Y h:i:s A'); ?>
                </div>
                
                <!-- Sección 1: Datos Personales -->
<div class="form-section">
    <h3 class="form-subtitle">DATOS PERSONALES</h3>
    
    <!-- Campo de Foto de Perfil - Full width arriba -->
    <div class="form-group full-width">
        <label class="form-label" for="foto_perfil">
            <i class="fas fa-camera"></i> Foto del Contratista
        </label>
        
        <div class="foto-container">
            <!-- Vista previa de la foto -->
            <div class="foto-preview" id="fotoPreview">
                <img id="fotoPreviewImg" style="display: none; max-width: 100%; border-radius: 8px;">
            </div>
            
            <!-- Input para subir la foto -->
            <div class="foto-input-group">
                <div class="file-input-container foto-input">
                    <input type="file" 
                        id="foto_perfil" 
                        name="foto_perfil" 
                        class="file-input-control" 
                        accept=".jpg,.jpeg,.png,.gif,.JPG,.JPEG,.PNG,.GIF"
                        data-max-size="2">
                    <div class="file-input-info">
                        <span class="file-input-text">Seleccionar foto...</span>
                        <span class="file-input-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Mensaje de error -->
                <div class="foto-error" id="fotoError" style="display: none;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="fotoErrorMessage"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid para los demás campos (se mantiene igual) -->
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label" for="nombre_completo">
                Nombre completo <span class="required">*</span>
            </label>
            <input type="text" 
                   id="nombre_completo" 
                   name="nombre_completo" 
                   class="form-control" 
                   placeholder="Ingrese el nombre completo"
                   required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="profesion">
                Profesión / Ocupación
            </label>
            <input type="text" 
                id="profesion" 
                name="profesion" 
                class="form-control" 
                placeholder="Ingrese profesión u ocupación">
        </div>
        
        <div class="form-group">
            <label class="form-label" for="cedula">
                Cédula <span class="required">*</span>
            </label>
            <input type="text" 
                   id="cedula" 
                   name="cedula" 
                   class="form-control medium" 
                   placeholder="Número de identificación"
                   required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="correo">
                Correo <span class="required">*</span>
            </label>
            <input type="email" 
                   id="correo" 
                   name="correo" 
                   class="form-control" 
                   placeholder="ejemplo@dominio.com"
                   required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="celular">
                Número de celular <span class="required">*</span>
            </label>
            <input type="tel" 
                   id="celular" 
                   name="celular" 
                   class="form-control medium" 
                   placeholder="Ingrese número de celular"
                   required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="direccion">
                <i class="fas fa-home"></i> Dirección
            </label>
            <input type="text" 
                   id="direccion" 
                   name="direccion" 
                   class="form-control" 
                   placeholder="Dirección completa">
        </div>
        
        <div class="form-group">
            <label class="form-label" for="id_tipo_vinculacion">
                Tipo de vinculación <span class="required">*</span>
            </label>
            <select id="id_tipo_vinculacion" name="id_tipo_vinculacion" class="form-control" required>
                <option value="">Seleccione</option>
                <?php foreach ($tiposVinculacion as $tipo): ?>
                <option value="<?= htmlspecialchars($tipo['id_tipo']) ?>">
                    <?= htmlspecialchars($tipo['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
                
                <!-- Sección 2: Información Geográfica -->
                <div class="form-section">
                    <h3 class="form-subtitle">INFORMACIÓN GEOGRÁFICA</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="id_municipio_principal">
                                Municipio 1 (principal) <span class="required">*</span>
                            </label>
                            <select id="id_municipio_principal" name="id_municipio_principal" class="form-control" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($municipios as $municipio): ?>
                                <option value="<?= htmlspecialchars($municipio['id_municipio']) ?>" 
                                        <?= ($municipio['nombre'] == 'Villavicencio') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($municipio['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="direccion_municipio_principal">
                                Dirección en municipio principal <span class="required">*</span>
                            </label>
                            <input type="text" 
                                id="direccion_municipio_principal" 
                                name="direccion_municipio_principal" 
                                class="form-control" 
                                placeholder="Dirección completa donde trabaja"
                                required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_municipio_secundario">
                                Municipio 2 (opcional)
                            </label>
                            <select id="id_municipio_secundario" name="id_municipio_secundario" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="0">Ninguno</option>
                                <?php foreach ($municipios as $municipio): ?>
                                <option value="<?= htmlspecialchars($municipio['id_municipio']) ?>">
                                    <?= htmlspecialchars($municipio['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Campo de dirección para municipio secundario (inicialmente oculto) -->
                        <div class="form-group direccion-opcional" id="grupo_direccion_secundario" style="display: none;">
                            <label class="form-label" for="direccion_municipio_secundario">
                                Dirección en municipio 2
                            </label>
                            <input type="text" 
                                id="direccion_municipio_secundario" 
                                name="direccion_municipio_secundario" 
                                class="form-control" 
                                placeholder="Dirección completa donde trabaja">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_municipio_terciario">
                                Municipio 3 (opcional)
                            </label>
                            <select id="id_municipio_terciario" name="id_municipio_terciario" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="0">Ninguno</option>
                                <?php foreach ($municipios as $municipio): ?>
                                <option value="<?= htmlspecialchars($municipio['id_municipio']) ?>">
                                    <?= htmlspecialchars($municipio['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Campo de dirección para municipio terciario (inicialmente oculto) -->
                        <div class="form-group direccion-opcional" id="grupo_direccion_terciario" style="display: none;">
                            <label class="form-label" for="direccion_municipio_terciario">
                                Dirección en municipio 3
                            </label>
                            <input type="text" 
                                id="direccion_municipio_terciario" 
                                name="direccion_municipio_terciario" 
                                class="form-control" 
                                placeholder="Dirección completa donde trabaja">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_area">
                                Área <span class="required">*</span>
                            </label>
                            <select id="id_area" name="id_area" class="form-control" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?= htmlspecialchars($area['id_area']) ?>">
                                    <?= htmlspecialchars($area['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 3: Información del Contrato -->
                <div class="form-section">
                    <h3 class="form-subtitle">INFORMACIÓN DEL CONTRATO</h3>
                    
                    <div class="info-section">
                        <p><strong>Nota:</strong> Complete la información del contrato según los documentos oficiales</p>
                    </div>
                    
                    <div class="contract-info-grid">
                        <div class="form-group">
                            <label class="form-label" for="numero_contrato">
                                Número de contrato <span class="required">*</span>
                            </label>
                            <input type="text" 
                                id="numero_contrato" 
                                name="numero_contrato" 
                                class="form-control medium" 
                                placeholder="Número del contrato"
                                required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_contrato">
                                Fecha del contrato <span class="required">*</span>
                            </label>
                            <input type="text" 
                                id="fecha_contrato" 
                                name="fecha_contrato" 
                                class="form-control small" 
                                placeholder="dd/mm/aaaa"
                                required>
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_inicio">
                                Fecha inicio <span class="required">*</span>
                            </label>
                            <input type="text" 
                                id="fecha_inicio" 
                                name="fecha_inicio" 
                                class="form-control small" 
                                placeholder="dd/mm/aaaa"
                                required>
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_final">
                                Fecha final <span class="required">*</span>
                            </label>
                            <input type="text" 
                                id="fecha_final" 
                                name="fecha_final" 
                                class="form-control small" 
                                placeholder="dd/mm/aaaa"
                                required>
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="duracion_contrato">
                                Duración del contrato <span class="required">*</span>
                            </label>
                            <input type="text" 
                                id="duracion_contrato" 
                                name="duracion_contrato" 
                                class="form-control small" 
                                placeholder="Se calculará automáticamente"
                                readonly
                                required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="numero_registro_presupuestal">
                                Número registro presupuestal
                            </label>
                            <input type="text" 
                                id="numero_registro_presupuestal" 
                                name="numero_registro_presupuestal" 
                                class="form-control medium" 
                                placeholder="Número RP">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_rp">
                                Fecha RP
                            </label>
                            <input type="text" 
                                id="fecha_rp" 
                                name="fecha_rp" 
                                class="form-control small" 
                                placeholder="dd/mm/aaaa">
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>
                    </div>
                    
                    <!-- Subsección para archivos adjuntos -->
                    <div style="margin-top: 30px; border-top: 1px solid #eaeaea; padding-top: 25px;">
                        <h4 style="color: var(--secondary-color); margin-bottom: 20px; font-size: 18px;">
                            <i class="fas fa-paperclip"></i> DOCUMENTOS ADJUNTOS
                        </h4>
                        
                        <div class="contract-info-grid">
                            <!-- Adjuntar CV -->
                            <div class="form-group">
                                <label class="form-label" for="adjuntar_cv">
                                    <i class="fas fa-file-pdf"></i> Adjuntar CV (Hoja de Vida)
                                </label>
                                <div class="file-input-container">
                                    <input type="file" 
                                        id="adjuntar_cv" 
                                        name="adjuntar_cv" 
                                        class="file-input-control" 
                                        accept=".pdf,.doc,.docx">
                                    <div class="file-input-info">
                                        <span class="file-input-text">Seleccionar archivo...</span>
                                        <span class="file-input-icon">
                                            <i class="fas fa-paperclip"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> Formatos: PDF, DOC, DOCX (Máx. 5MB)
                                </div>
                                
                                <!-- Vista previa CV -->
                                <div class="file-preview-simple" id="cvPreview" style="display: none; margin-top: 8px;">
                                    <div class="preview-content">
                                        <i class="fas fa-file-pdf preview-icon"></i>
                                        <span class="preview-filename" id="cvFilename"></span>
                                        <button type="button" class="preview-remove" onclick="removeCV()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adjuntar Contrato -->
                            <div class="form-group">
                                <label class="form-label" for="adjuntar_contrato">
                                    <i class="fas fa-file-contract"></i> Contrato PDF
                                </label>
                                <div class="file-input-container">
                                    <input type="file" 
                                        id="adjuntar_contrato" 
                                        name="adjuntar_contrato" 
                                        class="file-input-control" 
                                        accept=".pdf">
                                    <div class="file-input-info">
                                        <span class="file-input-text">Seleccionar archivo...</span>
                                        <span class="file-input-icon">
                                            <i class="fas fa-paperclip"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> Formato: PDF (Máx. 5MB)
                                </div>
                                
                                <!-- Vista previa Contrato -->
                                <div class="file-preview-simple" id="contratoPreview" style="display: none; margin-top: 8px;">
                                    <div class="preview-content">
                                        <i class="fas fa-file-contract preview-icon"></i>
                                        <span class="preview-filename" id="contratoFilename"></span>
                                        <button type="button" class="preview-remove" onclick="removeContrato()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adjuntar Acta de Inicio -->
                            <div class="form-group">
                                <label class="form-label" for="adjuntar_acta_inicio">
                                    <i class="fas fa-file-signature"></i> Acta de Inicio PDF
                                </label>
                                <div class="file-input-container">
                                    <input type="file" 
                                        id="adjuntar_acta_inicio" 
                                        name="adjuntar_acta_inicio" 
                                        class="file-input-control" 
                                        accept=".pdf">
                                    <div class="file-input-info">
                                        <span class="file-input-text">Seleccionar archivo...</span>
                                        <span class="file-input-icon">
                                            <i class="fas fa-paperclip"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> Formato: PDF (Máx. 5MB)
                                </div>
                                
                                <!-- Vista previa Acta de Inicio -->
                                <div class="file-preview-simple" id="actaPreview" style="display: none; margin-top: 8px;">
                                    <div class="preview-content">
                                        <i class="fas fa-file-signature preview-icon"></i>
                                        <span class="preview-filename" id="actaFilename"></span>
                                        <button type="button" class="preview-remove" onclick="removeActaInicio()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adjuntar RP -->
                            <div class="form-group">
                                <label class="form-label" for="adjuntar_rp">
                                    <i class="fas fa-file-invoice-dollar"></i> Registro Presupuestal PDF
                                </label>
                                <div class="file-input-container">
                                    <input type="file" 
                                        id="adjuntar_rp" 
                                        name="adjuntar_rp" 
                                        class="file-input-control" 
                                        accept=".pdf">
                                    <div class="file-input-info">
                                        <span class="file-input-text">Seleccionar archivo...</span>
                                        <span class="file-input-icon">
                                            <i class="fas fa-paperclip"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i> Formato: PDF (Máx. 5MB)
                                </div>
                                
                                <!-- Vista previa RP -->
                                <div class="file-preview-simple" id="rpPreview" style="display: none; margin-top: 8px;">
                                    <div class="preview-content">
                                        <i class="fas fa-file-invoice-dollar preview-icon"></i>
                                        <span class="preview-filename" id="rpFilename"></span>
                                        <button type="button" class="preview-remove" onclick="removeRP()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelarBtn">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="guardarBtn">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </main>
        
        <button class="volver-btn" id="volverBtn">
            <i class="fas fa-arrow-left"></i>
            <span>Volver al Menú</span>
        </button>
        
         <footer class="app-footer">
    <div class="footer-center">
        <?php
        $logoUrl = ConfigHelper::obtenerLogoUrl();
        $entidad = htmlspecialchars(ConfigHelper::obtener('entidad', 'Gobernación del Meta'));
        $version = htmlspecialchars(ConfigHelper::obtenerVersionCompleta());
        $desarrollador = htmlspecialchars(ConfigHelper::obtener('desarrollado_por', 'SisgonTech'));
        $direccion = htmlspecialchars(ConfigHelper::obtener('direccion'));
        $correo = htmlspecialchars(ConfigHelper::obtener('correo_contacto'));
        $telefono = htmlspecialchars(ConfigHelper::obtener('telefono'));
        $anio = date('Y');
        ?>
        
        <div class="footer-logo-container">
            <img src="<?php echo htmlspecialchars($logoUrl); ?>" 
                alt="<?php echo $entidad; ?>" 
                class="license-logo"
                onerror="this.onerror=null; this.src='/imagenes/gobernacion.png'">
        </div>
        
        <!-- Primera línea concatenada -->
        <p>
            © <?php echo $anio; ?> <?php echo $entidad; ?> <?php echo $version; ?>® desarrollado por 
            <strong><?php echo $desarrollador; ?></strong>
        </p>
        
        <!-- Segunda línea concatenada -->
        <p>
            <?php echo $direccion; ?> - Asesores e-Governance Solutions para Entidades Públicas <?php echo $anio; ?>® 
            By: Ing. Rubén Darío González García <?php echo $telefono; ?>. Contacto: <strong><?php echo $correo; ?></strong> - Reservados todos los derechos de autor.  
        </p>
        

    </div>
</footer>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="../../javascript/agregar_contratista.js"></script>
    
</body>
</html>