<?php
session_start();

require_once 'config/database.php';
require_once 'controllers/sesioncontrolador.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$database = new Database();
$db = $database->conectar();
$sesionControlador = new SesionControlador($db);

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar cookie remember_token
if (!isset($_SESSION['usuario_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    try {
        $stmt = $db->prepare("SELECT
                                u.id_usuario,
                                u.correo,
                                u.tipo_usuario,
                                p.nombres,
                                p.apellidos,
                                p.telefono
                              FROM usuario u
                              INNER JOIN persona p ON u.id_persona = p.id_persona
                              INNER JOIN remember_tokens rt ON u.id_usuario = rt.id_usuario
                              WHERE rt.token = :token AND rt.expiracion > NOW()");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['nombres'] = $usuario['nombres'];
            $_SESSION['apellidos'] = $usuario['apellidos'];
            $_SESSION['telefono'] = $usuario['telefono'];
            $_SESSION['correo'] = $usuario['correo'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'] ?? 'usuario';
            
            // NOTA: Por ahora mantenemos simple con solo tipo_usuario
            // Si en el futuro necesitamos permisos granulares, descomentar:
            // cargarPermisos($_SESSION['tipo_usuario']);

            // Redirigir según rol (usar rutas relativas)
            if ($_SESSION['tipo_usuario'] === 'asistente') {
                header("Location: views/menuAsistente.php");
            } else {
                header("Location: views/menu.php");
            }
            exit();
            
        } else {
            // Token inválido o expirado, eliminar cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        // Error en la consulta, eliminar cookie por seguridad
        setcookie('remember_token', '', time() - 3600, '/');
        error_log("Error en remember token: " . $e->getMessage());
    }
}

$has_login_error = false;
$error_message = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {

    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $has_login_error = true;
        $error_message = "Error de seguridad. Por favor, recarga la página e intenta nuevamente.";
    } else {
        // Verificar si es login o recuperación de contraseña
        if (isset($_POST['password'])) {
            // LOGIN NORMAL
            $correo = trim($_POST['email']);
            $password = $_POST['password'];
            $remember = isset($_POST['remember']) && $_POST['remember'] == 'on';

            $usuario = $sesionControlador->login($correo, $password);

            if ($usuario) {
                // Establecer datos de sesión
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['nombres'] = $usuario['nombres'] ?? '';
                $_SESSION['apellidos'] = $usuario['apellidos'] ?? '';
                $_SESSION['telefono'] = $usuario['telefono'] ?? '';
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'] ?? 'usuario';
                
                // NOTA: Por ahora mantenemos simple con solo tipo_usuario
                // Si en el futuro necesitamos permisos granulares, descomentar:
                // cargarPermisos($_SESSION['tipo_usuario']);
                
                // Log de login exitoso
                error_log("LOGIN EXITOSO - Usuario: " . $usuario['correo'] . 
                        " - Rol: " . $_SESSION['tipo_usuario'] . 
                        " - IP: " . $_SERVER['REMOTE_ADDR']);

                // Recordar usuario si seleccionó la opción
                if ($remember) {
                    try {
                        $token = bin2hex(random_bytes(32));
                        $expiracion = date("Y-m-d H:i:s", strtotime("+30 days"));

                        // Primero eliminar tokens antiguos del usuario
                        $stmtDelete = $db->prepare("DELETE FROM remember_tokens WHERE id_usuario = :id_usuario");
                        $stmtDelete->bindParam(':id_usuario', $usuario['id_usuario']);
                        $stmtDelete->execute();

                        // Insertar nuevo token
                        $stmt = $db->prepare("INSERT INTO remember_tokens (id_usuario, token, expiracion)
                                            VALUES (:id_usuario, :token, :expiracion)");
                        $stmt->bindParam(':id_usuario', $usuario['id_usuario']);
                        $stmt->bindParam(':token', $token);
                        $stmt->bindParam(':expiracion', $expiracion);
                        $stmt->execute();

                        // Establecer cookie
                        setcookie('remember_token', $token, [
                            'expires' => time() + (30 * 24 * 60 * 60),
                            'path' => '/',
                            'domain' => $_SERVER['HTTP_HOST'],
                            'secure' => ($protocol === 'https'),
                            'httponly' => true,
                            'samesite' => 'Strict'
                        ]);
                    } catch (PDOException $e) {
                        error_log("Error al crear remember token: " . $e->getMessage());
                        // No interrumpir el login si falla el remember token
                    }
                }

                // Redirigir según rol
                if ($_SESSION['tipo_usuario'] === 'asistente') {
                    header("Location: views/menuAsistente.php");
                } else {
                    header("Location: views/menu.php");
                }
                exit();
                
            } else {
                $has_login_error = true;
                $error_message = "Credenciales incorrectas. Comprueba tu correo y contraseña e inténtalo de nuevo.";
            }
        } else {
            // RECUPERACIÓN DE CONTRASEÑA
            $correoRecuperacion = trim($_POST['email']);
            $mensaje_recuperacion = procesarRecuperacion($db, $correoRecuperacion, $base_url);

            $_SESSION['mensaje_recuperacion'] = $mensaje_recuperacion;

            header("Location: index.php");
            exit();
        }

        // Regenerar token CSRF después de procesar
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Mostrar mensaje de recuperación si existe
if (isset($_SESSION['mensaje_recuperacion'])) {
    $mensaje_recuperacion = $_SESSION['mensaje_recuperacion'];
    unset($_SESSION['mensaje_recuperacion']);
}

// Limpiar tokens expirados aleatoriamente
if (rand(1, 10) === 1) {
    try {
        limpiarTokensExpirados($db);
    } catch (Exception $e) {
        error_log("Error limpiando tokens expirados: " . $e->getMessage());
    }
}

/* 
 * FUNCIÓN COMENTADA - Para uso futuro si necesitamos permisos granulares
 * 
function cargarPermisos($tipoUsuario) {
    switch ($tipoUsuario) {
        case 'administrador':
            $_SESSION['permisos'] = [
                'parametrizacion',
                'gestion_usuarios', 
                'ver_reportes',
                'configurar_sistema',
                'acceso_completo'
            ];
            break;
            
        case 'asistente':
            $_SESSION['permisos'] = [
                'ver_reportes',
                'consultar_datos',
                'gestion_documental'
            ];
            break;
            
        case 'usuario':
        default:
            $_SESSION['permisos'] = [
                'consultar_datos'
            ];
            break;
    }
}
*/

/**
 * Limpiar tokens de recordar expirados
 */
function limpiarTokensExpirados($db) {
    try {
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE expiracion < NOW()");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Error limpiando tokens expirados: " . $e->getMessage());
        return false;
    }
}

/**
 * Procesar solicitud de recuperación de contraseña
 */
function procesarRecuperacion($db, $correoUsuario, $base_url) {
    // Consulta para obtener datos del usuario
    $stmt = $db->prepare("SELECT 
                            u.id_usuario,
                            p.nombres,
                            p.apellidos
                          FROM usuario u
                          INNER JOIN persona p ON u.id_persona = p.id_persona
                          WHERE u.correo = :correo LIMIT 1");
    $stmt->bindParam(':correo', $correoUsuario);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $token = bin2hex(random_bytes(32));
        $expiracion = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Eliminar tokens antiguos del usuario
        $stmtDelete = $db->prepare("DELETE FROM recovery_tokens WHERE id_usuario = :id_usuario");
        $stmtDelete->bindParam(':id_usuario', $usuario['id_usuario']);
        $stmtDelete->execute();

        // Insertar nuevo token
        $stmtToken = $db->prepare("INSERT INTO recovery_tokens (id_usuario, token, expiracion) 
                                   VALUES (:id_usuario, :token, :expiracion)");
        $stmtToken->bindParam(':id_usuario', $usuario['id_usuario']);
        $stmtToken->bindParam(':token', $token);
        $stmtToken->bindParam(':expiracion', $expiracion);

        if ($stmtToken->execute()) {
            $link = "{$base_url}/views/manage/nueva_contraseña.php?token={$token}";
            $nombrePersona = $usuario['nombres'] . ' ' . $usuario['apellidos'];
            $logo_url = $base_url . "/imagenes/logo.png";

            $payload = [
                "sender" => [
                    "name"  => getenv('SMTP_FROM_NAME') ?: "Soporte - Sistema SGEA",
                    "email" => getenv('SMTP_FROM') ?: "988a48002@smtp-brevo.com"
                ],
                "to" => [
                    [
                        "email" => $correoUsuario,
                        "name" => $nombrePersona
                    ]
                ],
                "subject" => "Recuperación de contraseña - Sistema SGEA",
                "htmlContent" => generarEmailRecuperacion($nombrePersona, $link, $logo_url)
            ];

            $apiKey = getenv('BREVO_API_KEY');
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
                return "Se ha enviado un enlace de recuperación a: $correoUsuario";
            } else {
                error_log("Error enviando email de recuperación - Código: $httpCode");
                return "Error al enviar el correo. Por favor, intente más tarde.";
            }
        } else {
            return "Error al generar el enlace de recuperación.";
        }
    } else {
        // Por seguridad, no revelar si el email existe o no
        return "Si el correo está registrado en nuestro sistema, recibirás un enlace de recuperación en unos minutos.";
    }
}

/**
 * Generar contenido HTML del email de recuperación
 */
function generarEmailRecuperacion($nombrePersona, $link, $logo_url) {
    $anioActual = date('Y');
    
    return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperación de Contraseña</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f8f9fa;
                }
                .container {
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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
                .btn-primary {
                    background: #1e8ee9;
                    color: white;
                    padding: 12px 25px;
                    text-decoration: none;
                    border-radius: 4px;
                    display: inline-block;
                    font-weight: bold;
                    font-size: 15px;
                    margin: 20px 0;
                    transition: background 0.3s;
                }
                .btn-primary:hover {
                    background: #1565c0;
                    color: white;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    color: #666;
                    font-size: 13px;
                    border-top: 1px solid #e9ecef;
                }
                .warning-box {
                    background: #f8f9fa;
                    border-left: 3px solid #6c757d;
                    padding: 12px 15px;
                    margin: 20px 0;
                    font-size: 14px;
                }
                .expiry-note {
                    color: #666;
                    font-size: 13px;
                    margin: 15px 0;
                }
                .institutional-text {
                    color: #444;
                    font-size: 14px;
                    line-height: 1.5;
                }
                .link-backup {
                    background: #f8f9fa;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-family: monospace;
                    font-size: 11px;
                    word-break: break-all;
                    margin: 10px 0;
                    display: block;
                }
                @media (max-width: 480px) {
                    .content { padding: 20px; }
                    .btn-primary { 
                        padding: 10px 20px;
                        width: 100%;
                        text-align: center;
                    }
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='color: #333; margin: 0 0 5px 0; font-size: 20px;'>
                        Sistema SGEA
                    </h2>
                    <p style='color: #666; margin: 0 0 15px 0; font-size: 14px;'>
                        Sistema de Gestión y Enrutamiento Administrativo
                    </p>
                    <img src='{$logo_url}' alt='Logo Gobernación' class='logo' style='max-width: 180px; height: auto;'>
                </div>
                
                <div class='content'>
                    <p class='institutional-text'>
                        Señor(a) <strong>{$nombrePersona}</strong>,
                    </p>
                    
                    <p class='institutional-text'>
                        Hemos recibido una solicitud para restablecer su contraseña en el 
                        <strong>Sistema SGEA - Sistema de Gestión y Enrutamiento Administrativo</strong> 
                        de la Gobernación.
                    </p>
                    
                    <p class='institutional-text'>
                        Para crear una nueva contraseña, haga clic en el siguiente enlace:
                    </p>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='{$link}' class='btn-primary'>
                            Restablecer Contraseña
                        </a>
                    </div>
                    
                    <p class='expiry-note'>
                        <strong>Vigencia:</strong> Este enlace tiene una validez de 1 hora.
                    </p>
                    
                    <p class='institutional-text'>
                        <strong>Enlace alternativo:</strong> Si presenta inconvenientes con el botón anterior, 
                        copie y pegue la siguiente dirección en su navegador:
                    </p>
                    
                    <span class='link-backup'>{$link}</span>
                    
                    <div class='warning-box'>
                        <strong>Nota de seguridad:</strong><br>
                        Si usted no solicitó el restablecimiento de contraseña, ignore este mensaje. 
                        Su cuenta permanecerá segura.
                    </div>
                    
                    <p class='institutional-text'>
                        Para asistencia adicional, comuníquese con el área de soporte técnico.
                    </p>
                </div>
                
                <div class='footer'>
                    <div style='margin-bottom: 15px;'>
                        <img src='{$logo_url}' alt='Logo Gobernación' style='max-width: 80px; height: auto; opacity: 0.7;'>
                    </div>
                    <p style='margin: 5px 0;'><strong>Sistema SGEA</strong></p>
                    <p style='margin: 5px 0; font-size: 12px;'>Gobernación - Sistema de Gestión y Enrutamiento Administrativo</p>
                    <p style='margin-top: 15px; font-size: 11px; color: #999;'>
                        Este es un mensaje automático generado por el sistema.<br>
                        Favor no responder a esta dirección de correo.<br>
                        &copy; {$anioActual} Gobernación. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </body>
        </html>
    ";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modulo de medición de desempeño, tareas y compromisos</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/views/styles/login-style.css">
</head>
<body>
    <div class="container">
        <div class="left">
            <h1><span class="titulo-sgea">Modulo de medición de <br>desempeño, tareas y compromisos</span></h1>
            <p>Administre, gestione, mida y haga seguimiento a actividades administrativas corporativas e institucionales. Gestione con solo un click!!!!!</p>
            <div class="icons">
                <i class="fab fa-facebook"></i>
                <i class="fab fa-twitter"></i>
                <i class="fab fa-instagram"></i>
                <a id="openLicenseModal" style="color: white; text-decoration: none; cursor: pointer; margin-left: 15px;" 
                title="Ver información de licencia">
                    <i class="fa-solid fa-id-card"></i> Licencia
                </a>
            </div>
        </div>

        <div class="right">
            <h2>Iniciar Sesión</h2>

            <?php if (isset($mensaje_recuperacion)): ?>
                <div class="alert-success"><?php echo $mensaje_recuperacion; ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="input-box">
                    <i class="fa-solid fa-envelope <?php echo $has_login_error ? 'error-icon' : ''; ?>"></i>
                    <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        autocomplete="email"
                        id="email-field"
                        class="<?php echo $has_login_error ? 'error-border' : ''; ?>"
                    >
                </div>

                <div class="input-box">
                    <i class="fa-solid fa-lock <?php echo $has_login_error ? 'error-icon' : ''; ?>"></i>
                    <input
                        type="password"
                        name="password"
                        placeholder="Contraseña"
                        required
                        autocomplete="current-password"
                        id="password-field"
                        class="<?php echo $has_login_error ? 'error-border' : ''; ?>"
                    >
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="options">
                    <label>
                        <input type="checkbox" name="remember"> Recuérdame
                    </label>
                    <a id="openRecoveryModal">¿Olvidaste tu contraseña?</a>
                </div>

                <button class="btn" type="submit">Ingresar</button>

                <?php if ($has_login_error): ?>
                    <div class="error-message">
                        <strong>Credenciales incorrectas</strong>
                        Comprueba tu correo y contraseña e inténtalo de nuevo.
                    </div>
                <?php endif; ?>

                <div class="signup" style="visibility:hidden;">
                    ¿No tienes cuenta? <a href="<?php echo $base_url; ?>/views/registrarusuario.php">Regístrate</a>
                </div>

                <div class="sisgonTech">
                    <small>Desarrollado por SisgonTech 2026. Version de prueba Runtime</small>
                </div>
            </form>
        </div>
    </div>

    <div id="recoveryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-key"></i> Recuperar Contraseña</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-text">
                <p>Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
            </div>
            <form method="POST" action="" id="recoveryForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="input-box">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Tu correo electrónico" required autocomplete="email">
                </div>
                <button class="btn" type="submit">Enviar Enlace</button>
            </form>
        </div>
    </div>

<!-- Modal de Licencia compacto -->
<div id="licenseModal" class="modal">
    <div class="modal-content license-modal">
        <span class="close">&times;</span>
        <!-- Logo más cerca del título -->
        <div class="license-logo-container">
            <img src="<?php echo $base_url; ?>/imagenes/logo.png" alt="Logo Gobernación" class="license-logo">
        </div>
        
        <!-- Información pegada al logo -->
        <div class="license-details">
            <p><strong>Versión:</strong> 1.0.0 (Runtime)</p>
            <p><strong>Tipo de Licencia:</strong> Evaluación</p>
            <p><strong>Válida hasta:</strong> 31 de Marzo de 2026</p>
            <p><strong>Desarrollado por:</strong> SisgonTech</p>
            <p><strong>Dirección:</strong> Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, en Villavicencio, Meta</p>
            <p><strong>Contacto:</strong> gobernaciondelmeta@meta.gov.co</p>
            <p><strong>Teléfono:</strong> (57 -608) 6 818503</p>
        </div>
    </div>
</div>
<script>
if (window.history && window.history.pushState) {
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function(event) {
        window.history.pushState(null, null, window.location.href);
    };
}
</script>
    <script src="javascript/login-script.js"></script>
</body>
</html>