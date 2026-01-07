<?php
require_once __DIR__ . '/config_helper.php';

class EmailHelper {
    
    public static function enviarCorreoAprobacion($correoDestino, $nombreContratista, $correoUsuario) {
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
                $fromName = 'Sistema SGEA - Sistema de Gestión y Enrutamiento Administrativo';
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
            
            // Obtener datos para el footer usando ConfigHelper
            $version = ConfigHelper::obtenerVersionCompleta();
            $desarrollador = ConfigHelper::obtener('desarrollado_por', 'SisgonTech');
            $direccionFooter = ConfigHelper::obtener('direccion', 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta');
            $correoContacto = ConfigHelper::obtener('correo_contacto', 'gobernaciondelmeta@meta.gov.co');
            $telefono = ConfigHelper::obtener('telefono', '(57 -608) 6 818503');
            
            // URL de acceso al sistema (ajusta según tu configuración)
            $urlLogin = $base_url . "/index.php";
            
            // Asunto del correo
            $subject = "Cuenta de Usuario Aprobada - $sistema - $entidad";
            
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
                        max-width: 180px;
                        height: auto;
                        margin-bottom: 10px;
                    }
                    .content {
                        padding: 25px;
                    }
                    .highlight-box {
                        background-color: #f0f8ff;
                        border-left: 4px solid #007bff;
                        padding: 20px;
                        margin: 25px 0;
                        border-radius: 0 5px 5px 0;
                    }
                    .credentials-box {
                        background-color: #f8f9fa;
                        border: 1px solid #dee2e6;
                        border-radius: 5px;
                        padding: 20px;
                        margin: 20px 0;
                    }
                    .credential-item {
                        margin-bottom: 12px;
                        font-size: 15px;
                    }
                    .credential-label {
                        font-weight: 600;
                        color: #0066cc;
                        min-width: 140px;
                        display: inline-block;
                    }
                    .section-title {
                        font-size: 16px;
                        color: #333;
                        font-weight: bold;
                        margin-bottom: 15px;
                        padding-bottom: 5px;
                        border-bottom: 1px solid #ddd;
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
                        max-width: 180px;
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
                        .credential-label { display: block; margin-bottom: 5px; }
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
                            <p>Nos complace informarle que su <strong>solicitud de cuenta de usuario ha sido aprobada</strong> en el sistema <strong>$sistema</strong> de la <strong>$entidad</strong>.</p>
                            <p>A partir de ahora puede acceder a todas las funcionalidades del sistema con sus credenciales.</p>
                        </div>
                        
                        <!-- SECCIÓN DE CREDENCIALES -->
                        <div class='credentials-box'>
                            <div class='section-title'>CREDENCIALES DE ACCESO</div>
                            
                            <div class='credential-item'>
                                <span class='credential-label'>Correo electrónico:</span>
                                <strong>$correoUsuario</strong>
                            </div>
                            
                            <div class='credential-item'>
                                <span class='credential-label'>Contraseña:</span>
                                La que usted registró durante la solicitud
                            </div>
                            
                            <div class='credential-item'>
                                <span class='credential-label'>URL de acceso:</span>
                                <a href='$urlLogin' style='color: #0066cc; word-break: break-all;'>
                                    $urlLogin
                                </a>
                            </div>
                        </div>
                        
                        <!-- SECCIÓN DE INSTRUCCIONES -->
                        <div class='highlight-box'>
                            <div class='section-title'>INSTRUCCIONES IMPORTANTES</div>
                            <ul style='margin: 0 0 0 20px; padding: 0;'>
                                <li style='margin-bottom: 10px;'>Guarde esta información en un lugar seguro</li>
                                <li style='margin-bottom: 10px;'>La primera vez que ingrese, se recomienda cambiar su contraseña</li>
                                <li style='margin-bottom: 10px;'>Si olvida su contraseña, utilice la opción \"Recuperar contraseña\"</li>
                                <li>Para problemas de acceso, contacte al administrador del sistema</li>
                            </ul>
                        </div>
                        
                        <!-- SECCIÓN DE SEGURIDAD -->
                        <div style='background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                            <p style='margin: 0; font-size: 14px; color: #856404;'>
                                <strong>⚠️ NOTA DE SEGURIDAD:</strong><br>
                                Por su seguridad, nunca comparta sus credenciales con otras personas. 
                                El equipo de $entidad nunca le pedirá su contraseña por correo o teléfono.
                            </p>
                        </div>
                        
                        <p style='font-size: 14px; margin-top: 20px;'>
                            Si tiene alguna pregunta o requiere asistencia técnica, por favor comuníquese con el área de sistemas.
                        </p>
                        
                        <p style='font-size: 14px; margin-top: 20px;'>
                            Atentamente,<br>
                            <strong>Equipo de Sistemas</strong><br>
                            $entidad
                        </p>
                    </div>
                    
                    <!-- FOOTER -->
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
            
            // Preparar payload para Brevo
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
            
            // Enviar usando Brevo API
            return self::enviarPayloadBrevo($apiKey, $payload);
            
        } catch (Exception $e) {
            error_log("❌ Excepción al enviar correo de aprobación: " . $e->getMessage());
            return false;
        }
    }
    
    private static function enviarPayloadBrevo($apiKey, $payload) {
        try {
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, "https://api.brevo.com/v3/smtp/email");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "accept: application/json",
                "api-key: " . $apiKey,
                "content-type: application/json"
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                error_log("✅ Correo enviado exitosamente a: " . $payload['to'][0]['email']);
                return true;
            } else {
                error_log("❌ Error al enviar correo. Código: $httpCode, Respuesta: $response, Error: $curlError");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("❌ Error en enviarPayloadBrevo: " . $e->getMessage());
            return false;
        }
    }
        /**
     * Envía notificación de contrato por vencer
     */
    public static function enviarNotificacionContratoPorVencer($correoDestino, $nombreContratista, $fechaVencimiento, $diasRestantes, $numeroContrato) {
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
                $fromName = 'Sistema SGEA - Sistema de Gestión y Enrutamiento Administrativo';
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
            
            // Obtener datos para el footer usando ConfigHelper
            $version = ConfigHelper::obtenerVersionCompleta();
            $desarrollador = ConfigHelper::obtener('desarrollado_por', 'SisgonTech');
            $direccionFooter = ConfigHelper::obtener('direccion', 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta');
            $correoContacto = ConfigHelper::obtener('correo_contacto', 'gobernaciondelmeta@meta.gov.co');
            $telefono = ConfigHelper::obtener('telefono', '(57 -608) 6 818503');
            
            // URL de acceso al sistema
            $urlLogin = $base_url . "/index.php";
            
            // Formatear fecha
            $fechaFormateada = date('d/m/Y', strtotime($fechaVencimiento));
            
            // Determinar nivel de urgencia
            if ($diasRestantes <= 7) {
                $colorUrgencia = '#dc3545';
                $nivelUrgencia = 'ALTA';
            } elseif ($diasRestantes <= 15) {
                $colorUrgencia = '#ffc107';
                $nivelUrgencia = 'MEDIA';
            } else {
                $colorUrgencia = '#28a745';
                $nivelUrgencia = 'BAJA';
            }
            
            // Asunto del correo
            $subject = "⚠️ CONTRATO POR VENCER - $numeroContrato - $sistema - $entidad";
            
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
                        max-width: 180px;
                        height: auto;
                        margin-bottom: 10px;
                    }
                    .content {
                        padding: 25px;
                    }
                    .alert-box {
                        background-color: #fff3cd;
                        border: 2px solid $colorUrgencia;
                        border-radius: 5px;
                        padding: 20px;
                        margin: 25px 0;
                        text-align: center;
                    }
                    .alert-days {
                        font-size: 36px;
                        font-weight: bold;
                        color: $colorUrgencia;
                        margin: 15px 0;
                    }
                    .info-box {
                        background-color: #f8f9fa;
                        border: 1px solid #dee2e6;
                        border-radius: 5px;
                        padding: 20px;
                        margin: 20px 0;
                    }
                    .info-item {
                        margin-bottom: 12px;
                        font-size: 15px;
                    }
                    .info-label {
                        font-weight: 600;
                        color: #0066cc;
                        min-width: 180px;
                        display: inline-block;
                    }
                    .section-title {
                        font-size: 16px;
                        color: #333;
                        font-weight: bold;
                        margin-bottom: 15px;
                        padding-bottom: 5px;
                        border-bottom: 1px solid #ddd;
                    }
                    .saludo {
                        font-size: 15px;
                        line-height: 1.6;
                        margin-bottom: 20px;
                    }
                    .action-box {
                        background-color: #e7f3ff;
                        border-left: 4px solid #0066cc;
                        padding: 20px;
                        margin: 25px 0;
                        border-radius: 0 5px 5px 0;
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
                        max-width: 180px;
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
                        .info-label { display: block; margin-bottom: 5px; }
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
                            <p>Le informamos que su <strong>contrato está próximo a vencer</strong> en el sistema <strong>$sistema</strong> de la <strong>$entidad</strong>.</p>
                            <p>A continuación encontrará los detalles y acciones recomendadas.</p>
                        </div>
                        
                        <!-- ALERTA DE VENCIMIENTO -->
                        <div class='alert-box'>
                            <h3 style='color: $colorUrgencia; margin-top: 0;'>⚠️ CONTRATO POR VENCER</h3>
                            <p>Nivel de urgencia: <strong style='color: $colorUrgencia;'>$nivelUrgencia</strong></p>
                            <div class='alert-days'>$diasRestantes DÍAS</div>
                            <p>Su contrato vencerá en <strong>$diasRestantes días</strong></p>
                        </div>
                        
                        <!-- INFORMACIÓN DEL CONTRATO -->
                        <div class='info-box'>
                            <div class='section-title'>INFORMACIÓN DEL CONTRATO</div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Número de contrato:</span>
                                <strong>$numeroContrato</strong>
                            </div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Fecha de vencimiento:</span>
                                <strong style='color: $colorUrgencia;'>$fechaFormateada</strong>
                            </div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Días restantes:</span>
                                <strong>$diasRestantes días</strong>
                            </div>
                            
                            <div class='info-item'>
                                <span class='info-label'>URL de acceso al sistema:</span>
                                <a href='$urlLogin' style='color: #0066cc; word-break: break-all;'>
                                    $urlLogin
                                </a>
                            </div>
                        </div>
                        
                        <!-- ACCIONES RECOMENDADAS -->
                        <div class='action-box'>
                            <div class='section-title'>ACCIONES RECOMENDADAS</div>
                            <ul style='margin: 0 0 0 20px; padding: 0;'>
                                <li style='margin-bottom: 10px;'>Comuníquese con su supervisor o área administrativa</li>
                                <li style='margin-bottom: 10px;'>Inicie el proceso de renovación con anticipación</li>
                                <li style='margin-bottom: 10px;'>Verifique que toda la documentación esté actualizada</li>
                                <li>Actualice su información en el sistema si hay cambios</li>
                            </ul>
                        </div>
                        
                        <!-- SECCIÓN DE ADVERTENCIA -->
                        <div style='background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                            <p style='margin: 0; font-size: 14px; color: #721c24;'>
                                <strong>⚠️ IMPORTANTE:</strong><br>
                                Una vez vencido el contrato, su acceso al sistema será <strong>suspendido automáticamente</strong> 
                                hasta que se regularice su situación contractual.
                            </p>
                        </div>
                        
                        <!-- INFORMACIÓN DE CONTACTO -->
                        <div class='info-box'>
                            <div class='section-title'>INFORMACIÓN DE CONTACTO</div>
                            <div class='info-item'>
                                <span class='info-label'>Teléfono:</span>
                                $telefono
                            </div>
                            <div class='info-item'>
                                <span class='info-label'>Correo de contacto:</span>
                                $correoContacto
                            </div>
                        </div>
                        
                        <p style='font-size: 14px; margin-top: 20px;'>
                            Si ya ha realizado la renovación, puede ignorar este mensaje.
                        </p>
                        
                        <p style='font-size: 14px; margin-top: 20px;'>
                            Este es un mensaje automático, por favor no responder este correo.
                        </p>
                        
                        <p style='font-size: 14px; margin-top: 20px;'>
                            Atentamente,<br>
                            <strong>Equipo de Sistemas</strong><br>
                            $entidad
                        </p>
                    </div>
                    
                    <!-- FOOTER -->
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
            
            // Preparar payload para Brevo
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
            
            // Enviar usando Brevo API
            return self::enviarPayloadBrevo($apiKey, $payload);
            
        } catch (Exception $e) {
            error_log("❌ Excepción al enviar notificación de contrato por vencer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía notificación de contrato vencido
     */
    public static function enviarCorreoContratoVencido($correoDestino, $nombreContratista, $fechaVencimiento, $numeroContrato) {
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
                $fromName = 'Sistema SGEA - Sistema de Gestión y Enrutamiento Administrativo';
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
            
            // Obtener datos para el footer usando ConfigHelper
            $version = ConfigHelper::obtenerVersionCompleta();
            $desarrollador = ConfigHelper::obtener('desarrollado_por', 'SisgonTech');
            $direccionFooter = ConfigHelper::obtener('direccion', 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta');
            $correoContacto = ConfigHelper::obtener('correo_contacto', 'gobernaciondelmeta@meta.gov.co');
            $telefono = ConfigHelper::obtener('telefono', '(57 -608) 6 818503');
            
            // Formatear fechas
            $fechaFormateada = date('d/m/Y', strtotime($fechaVencimiento));
            $fechaActual = date('d/m/Y');
            
            // Asunto del correo
            $subject = "❌ CONTRATO VENCIDO - $numeroContrato - $sistema - $entidad";
            
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
                        max-width: 180px;
                        height: auto;
                        margin-bottom: 10px;
                    }
                    .content {
                        padding: 25px;
                    }
                    .alert-box {
                        background-color: #f8d7da;
                        border: 2px solid #dc3545;
                        border-radius: 5px;
                        padding: 20px;
                        margin: 25px 0;
                        text-align: center;
                    }
                    .alert-title {
                        font-size: 24px;
                        font-weight: bold;
                        color: #721c24;
                        margin-bottom: 10px;
                    }
                    .info-box {
                        background-color: #f8f9fa;
                        border: 1px solid #dee2e6;
                        border-radius: 5px;
                        padding: 20px;
                        margin: 20px 0;
                    }
                    .info-item {
                        margin-bottom: 12px;
                        font-size: 15px;
                    }
                    .info-label {
                        font-weight: 600;
                        color: #0066cc;
                        min-width: 180px;
                        display: inline-block;
                    }
                    .section-title {
                        font-size: 16px;
                        color: #333;
                        font-weight: bold;
                        margin-bottom: 15px;
                        padding-bottom: 5px;
                        border-bottom: 1px solid #ddd;
                    }
                    .saludo {
                        font-size: 15px;
                        line-height: 1.6;
                        margin-bottom: 20px;
                    }
                    .action-box {
                        background-color: #e7f3ff;
                        border-left: 4px solid #0066cc;
                        padding: 20px;
                        margin: 25px 0;
                        border-radius: 0 5px 5px 0;
                    }
                    .consequences-box {
                        background-color: #fff3cd;
                        border: 1px solid #ffeaa7;
                        border-radius: 5px;
                        padding: 15px;
                        margin: 20px 0;
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
                        max-width: 180px;
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
                        .info-label { display: block; margin-bottom: 5px; }
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
                            <p>Le informamos que su <strong>contrato ha vencido</strong> en el sistema <strong>$sistema</strong> de la <strong>$entidad</strong>.</p>
                        </div>
                        
                        <!-- ALERTA DE VENCIMIENTO -->
                        <div class='alert-box'>
                            <div class='alert-title'>❌ CONTRATO VENCIDO</div>
                            <p>Su contrato ha vencido y su acceso al sistema ha sido suspendido.</p>
                        </div>
                        
                        <!-- INFORMACIÓN DEL CONTRATO -->
                        <div class='info-box'>
                            <div class='section-title'>INFORMACIÓN DEL CONTRATO</div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Número de contrato:</span>
                                <strong>$numeroContrato</strong>
                            </div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Fecha de vencimiento:</span>
                                <strong style='color: #dc3545;'>$fechaFormateada</strong>
                            </div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Fecha actual:</span>
                                <strong>$fechaActual</strong>
                            </div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Estado del contrato:</span>
                                <strong style='color: #dc3545;'>VENCIDO</strong>
                            </div>
                            
                            <div class='info-item'>
                                <span class='info-label'>Estado de su cuenta:</span>
                                <strong style='color: #dc3545;'>SUSPENDIDA</strong>
                            </div>
                        </div>
                        
                        <!-- CONSECUENCIAS -->
                        <div class='consequences-box'>
                            <div class='section-title'>CONSECUENCIAS DEL VENCIMIENTO</div>
                            <ul style='margin: 0 0 0 20px; padding: 0;'>
                                <li style='margin-bottom: 10px;'>Su acceso al sistema SGEA ha sido <strong>suspendido automáticamente</strong></li>
                                <li style='margin-bottom: 10px;'>No podrá ingresar al sistema hasta regularizar su situación contractual</li>
                                <li>Para reactivar su acceso, debe renovar su contrato y contactar al administrador</li>
                            </ul>
                        </div>
                        
                        <!-- PASOS A SEGUIR -->
                        <div class='action-box'>
                            <div class='section-title'>PASOS PARA REACTIVAR SU ACCESO</div>
                            <ol style='margin: 0 0 0 20px; padding: 0;'>
                                <li style='margin-bottom: 10px;'>Comuníquese con su supervisor o área administrativa para iniciar la renovación</li>
                                <li style='margin-bottom: 10px;'>Complete el proceso de renovación de contrato</li>
                                <li style='margin-bottom: 10px;'>Una vez renovado el contrato, contacte al administrador del sistema</li>
                                <li>El administrador reactivará su acceso manualmente</li>
                            </ol>
                        </div>
                        
                        <!-- INFORMACIÓN DE CONTACTO -->
                        <div class='info-box'>
                            <div class='section-title'>INFORMACIÓN DE CONTACTO</div>
                            <div class='info-item'>
                                <span class='info-label'>Área Administrativa:</span>
                                administrativa@gobernaciondelmeta.gov.co
                            </div>
                            <div class='info-item'>
                                <span class='info-label'>Teléfono:</span>
                                $telefono
                            </div>
                            <div class='info-item'>
                                <span class='info-label'>Correo de contacto general:</span>
                                $correoContacto
                            </div>
                        </div>
                        
                        <p style='font-size: 14px; margin-top: 20px;'>
                            Este es un mensaje automático del sistema. Si ya ha realizado la renovación, 
                            por favor contacte al administrador del sistema para reactivar su acceso.
                        </p>
                        
                        <p style='font-size: 14px; margin-top: 20px;'>
                            Atentamente,<br>
                            <strong>Equipo de Sistemas</strong><br>
                            $entidad
                        </p>
                    </div>
                    
                    <!-- FOOTER -->
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
            
            // Preparar payload para Brevo
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
            
            // Enviar usando Brevo API
            return self::enviarPayloadBrevo($apiKey, $payload);
            
        } catch (Exception $e) {
            error_log("❌ Excepción al enviar correo de contrato vencido: " . $e->getMessage());
            return false;
        }
    }
}
?>