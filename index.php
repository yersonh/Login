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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['usuario_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    try {
        $stmt = $db->prepare("SELECT
                                u.id_usuario,
                                u.correo,
                                u.tipo_usuario, // ← AÑADIR ESTA LÍNEA
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
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'] ?? 'usuario'; // ← AÑADIR ESTA LÍNEA

            header("Location: /views/menu.php");
            exit();
            
        } else {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

$has_login_error = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {

        if (isset($_POST['password'])) {

            $correo = trim($_POST['email']);
            $password = $_POST['password'];
            $remember = isset($_POST['remember']) && $_POST['remember'] == 'on';

            $usuario = $sesionControlador->login($correo, $password);

            if ($usuario) {
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['nombres'] = $usuario['nombres'] ?? '';
    $_SESSION['apellidos'] = $usuario['apellidos'] ?? '';
    $_SESSION['telefono'] = $usuario['telefono'] ?? '';
    $_SESSION['correo'] = $usuario['correo'];
    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'] ?? 'usuario'; // ← AÑADIR ESTA LÍNEA

    if ($remember) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiracion = date("Y-m-d H:i:s", strtotime("+30 days"));

            $stmt = $db->prepare("INSERT INTO remember_tokens (id_usuario, token, expiracion)
                                VALUES (:id_usuario, :token, :expiracion)");
            $stmt->bindParam(':id_usuario', $usuario['id_usuario']);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expiracion', $expiracion);
            $stmt->execute();

            setcookie('remember_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => ($protocol === 'https'),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        } catch (PDOException $e) {
        }
    }

    header("Location: views/menu.php");
    exit();
    
} else {
    $has_login_error = true;
    $error_message = "Credenciales incorrectas. Comprueba tu correo y contraseña e inténtalo de nuevo.";
}
        } else {
            $correoRecuperacion = trim($_POST['email']);
            $mensaje_recuperacion = procesarRecuperacion($db, $correoRecuperacion, $base_url);

            $_SESSION['mensaje_recuperacion'] = $mensaje_recuperacion;

            header("Location: index.php");
            exit();
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

if (isset($_SESSION['mensaje_recuperacion'])) {
    $mensaje_recuperacion = $_SESSION['mensaje_recuperacion'];
    unset($_SESSION['mensaje_recuperacion']);
}

function limpiarTokensExpirados($db) {
    try {
        $stmt = $db->prepare("DELETE FROM remember_tokens WHERE expiracion < NOW()");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

if (rand(1, 10) === 1) {
    try {
        limpiarTokensExpirados($db);
    } catch (Exception $e) {
    }
}

function procesarRecuperacion($db, $correoUsuario, $base_url) {
    // Modifica la consulta para incluir datos de persona
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

        $stmtToken = $db->prepare("INSERT INTO recovery_tokens (id_usuario, token, expiracion) VALUES (:id_usuario, :token, :expiracion)");
        $stmtToken->bindParam(':id_usuario', $usuario['id_usuario']);
        $stmtToken->bindParam(':token', $token);
        $stmtToken->bindParam(':expiracion', $expiracion);

        if ($stmtToken->execute()) {
            $link = "{$base_url}/views/manage/nueva_contraseña.php?token={$token}";
            
            // Obtener el nombre de la persona
            $nombrePersona = $usuario['nombres'] . ' ' . $usuario['apellidos'];
            $nombreSistema = "Sistema SGEA";
            $nombreCompletoSistema = "Sistema SGEA - Sistema de Gestión y Enrutamiento Administrativo";
            
            // URL del logo
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
                "htmlContent" => "
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
                            <!-- ENCABEZADO CON LOGO INSTITUCIONAL -->
                            <div class='header'>
                                <h2 style='color: #333; margin: 0 0 5px 0; font-size: 20px;'>
                                    Sistema SGEA
                                </h2>
                                <p style='color: #666; margin: 0 0 15px 0; font-size: 14px;'>
                                    Sistema de Gestión y Enrutamiento Administrativo
                                </p>
                                
                                <img src='{$logo_url}' 
                                     alt='Logo Gobernación' 
                                     class='logo'
                                     style='max-width: 180px; height: auto;'>
                            </div>
                            
                            <!-- CONTENIDO PRINCIPAL -->
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
                                
                                <!-- BOTÓN DE ACCIÓN -->
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
                                
                                <!-- AVISO DE SEGURIDAD -->
                                <div class='warning-box'>
                                    <strong>Nota de seguridad:</strong><br>
                                    Si usted no solicitó el restablecimiento de contraseña, ignore este mensaje. 
                                    Su cuenta permanecerá segura.
                                </div>
                                
                                <p class='institutional-text'>
                                    Para asistencia adicional, comuníquese con el área de soporte técnico.
                                </p>
                            </div>
                            
                            <!-- PIE DE PÁGINA INSTITUCIONAL -->
                            <div class='footer'>
                                <div style='margin-bottom: 15px;'>
                                    <img src='{$logo_url}' 
                                         alt='Logo Gobernación' 
                                         style='max-width: 80px; height: auto; opacity: 0.7;'>
                                </div>
                                <p style='margin: 5px 0;'><strong>Sistema SGEA</strong></p>
                                <p style='margin: 5px 0; font-size: 12px;'>Gobernación - Sistema de Gestión y Enrutamiento Administrativo</p>
                                <p style='margin-top: 15px; font-size: 11px; color: #999;'>
                                    Este es un mensaje automático generado por el sistema.<br>
                                    Favor no responder a esta dirección de correo.<br>
                                    &copy; " . date('Y') . " Gobernación. Todos los derechos reservados.
                                </p>
                            </div>
                        </div>
                    </body>
                    </html>
                "
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
                return "Error al enviar el correo (Código: $httpCode).";
            }
        } else {
            return "Error al generar el enlace de recuperación.";
        }
    } else {
        return "Si el correo está registrado en nuestro sistema, recibirás un enlace de recuperación en unos minutos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema SGEA Sistema de Gestión y Enrutamiento Adminsitrativo</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: url("<?php echo $base_url; ?>/imagenes/login3.jpg") no-repeat center center/cover;
        padding: 20px;
    }

    .container {
        width: 100%;
        max-width: 1000px;
        height: auto;
        min-height: 500px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.6);
    }

    .left {
        background: rgba(59, 57, 57, 0.8);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: flex-start; /* Cambiado: empieza desde arriba */
        padding: 60px 40px 40px 40px; /* Más padding arriba */
        min-height: 500px;
    }

    .left h1 {
        font-size: clamp(1.6rem, 2.5vw, 2rem);
        margin: 40px 0; /* Más margen arriba y abajo */
        text-align: center;
        font-weight: 500;
        line-height: 1.3;
    }

    .left p {
        margin: 0 0 30px 0; /* Sin margen arriba, más abajo */
        color: #ddd;
        line-height: 1.5;
        text-align: center;
        font-size: clamp(0.9rem, 2vw, 1.1rem);
    }

    .icons {
        text-align: center;
        margin-top: auto; /* Empuja los íconos hacia abajo */
    }

    .icons i {
        margin: 0 10px;
        cursor: pointer;
        font-size: 1.5rem;
        transition: color 0.3s;
    }

    .icons i:hover {
        color: #1e8ee9;
    }

    .right {
        background: rgba(40, 38, 38, 0.85);
        backdrop-filter: blur(10px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 40px;
        color: white;
    }

    .right h2 {
        text-align: center;
        margin-bottom: 30px;
        font-size: clamp(1.5rem, 3vw, 1.8rem);
    }

    .input-box {
        position: relative;
        margin-bottom: 25px;
    }

    .input-box input {
        width: 100%;
        padding: 14px 40px;
        border: none;
        border-bottom: 2px solid #fff;
        background: transparent;
        outline: none;
        color: white;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    .input-box input.error-border {
        border-bottom-color: #ff6b6b !important;
    }

    .input-box input:focus {
        border-bottom-color: #1e8ee9;
    }

    .input-box input::placeholder {
        color: #ccc;
    }

    .input-box i {
        position: absolute;
        top: 50%;
        left: 10px;
        transform: translateY(-50%);
        color: white;
        z-index: 2;
    }

    .input-box i.error-icon {
        color: #ff6b6b;
    }

    .toggle-password {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #ccc;
        cursor: pointer;
        font-size: 16px;
        z-index: 2;
        padding: 5px;
        transition: color 0.3s;
    }

    .toggle-password:hover {
        color: #1e8ee9;
    }

    .options {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        margin-bottom: 25px;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .options label {
        display: flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }

    .options a {
        color: #1e8ee9;
        text-decoration: none;
        transition: color 0.3s;
        cursor: pointer;
    }

    .options a:hover {
        text-decoration: underline;
    }

    .btn {
        background: #1e8ee9;
        border: none;
        padding: 14px;
        width: 100%;
        color: white;
        font-size: 16px;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.3s;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .btn:hover {
        background: #1865c2;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(30, 142, 233, 0.3);
    }

    .btn:active {
        transform: translateY(0);
    }

    /* Mensaje de error profesional debajo del botón */
    .error-message {
    text-align: center;
    color: #ff6b6b;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.4;
}

.error-message strong {
    display: block;
    margin-bottom: 5px;
    font-size: 15px;
}

    .signup {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
    }

    .signup a {
        color: #1e8ee9;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.3s;
    }

    .signup a:hover {
        text-decoration: underline;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.9);
        color: white;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        border-left: 4px solid #4CAF50;
        display: <?php echo isset($mensaje_recuperacion) ? 'block' : 'none'; ?>;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background: rgba(40, 38, 38, 0.95);
        margin: 15% auto;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 400px;
        color: white;
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        margin: 0;
        color: #1e8ee9;
    }

    .close {
        color: #aaa;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.3s;
    }

    .close:hover {
        color: white;
    }

    .modal-text {
        margin-bottom: 20px;
        color: #ddd;
        line-height: 1.5;
    }

    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus {
        -webkit-text-fill-color: white !important;
        -webkit-box-shadow: 0 0 0px 1000px transparent inset !important;
        transition: background-color 5000s ease-in-out 0s !important;
    }

    @media (max-width: 768px) {
        body {
            padding: 15px;
            height: auto;
            min-height: 100vh;
            align-items: flex-start;
            padding-top: 40px;
        }

        .container {
            grid-template-columns: 1fr;
            height: auto;
            margin: 0;
        }

        .left, .right {
            padding: 40px 25px;
        }

        .left {
            order: 2;
            padding: 40px 25px;
        }

        .right {
            order: 1;
        }

        .left h1 {
            margin: 30px 0;
        }

        .options {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .modal-content {
            margin: 20%;
            width: 95%;
        }
    }

    @media (max-width: 480px) {
        .left, .right {
            padding: 30px 20px;
        }

        .left {
            padding: 30px 20px;
        }

        .input-box input {
            padding: 12px 35px;
            font-size: 16px;
        }

        .btn {
            padding: 12px;
        }

        .modal-content {
            padding: 20px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="left">
            <h1>Sistemas SGEA Sistema y enrutamiento administrativo</h1>
            <p>Administre, gestione, mida y haga seguimiento a actividades administrativas corporativas e institucionales. Gestione con solo un click!!!!!</p>
            <div class="icons">
                <i class="fab fa-facebook"></i>
                <i class="fab fa-twitter"></i>
                <i class="fab fa-instagram"></i>
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

                <div class="signup">
                    ¿No tienes cuenta? <a href="<?php echo $base_url; ?>/views/registrarusuario.php">Regístrate</a>
                </div>

                <div class="sisgonTech">
                    <small>Desarrollado por SisgonTech 2026. Version Runtime</small>
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

    <script>
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const email = document.querySelector('#loginForm input[name="email"]').value;
        const password = document.querySelector('#loginForm input[name="password"]').value;

        if (!email || !password) {
            e.preventDefault();
            alert('Por favor, completa todos los campos.');
            return false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Por favor, ingresa un email válido.');
            return false;
        }

        return true;
    });

    const modal = document.getElementById('recoveryModal');
    const openBtn = document.getElementById('openRecoveryModal');
    const closeBtn = document.querySelector('.close');

    openBtn.addEventListener('click', function() {
        modal.style.display = 'block';
    });

    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    document.getElementById('recoveryForm').addEventListener('submit', function(e) {
        const email = document.querySelector('#recoveryForm input[name="email"]').value;

        if (!email) {
            e.preventDefault();
            alert('Por favor, ingresa tu correo electrónico.');
            return false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Por favor, ingresa un email válido.');
            return false;
        }

        return true;
    });

    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password-field');
        const toggleIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fa-solid fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fa-solid fa-eye';
            }
        });

        const loginForm = document.getElementById('loginForm');
        let isSubmitting = false;

        loginForm.addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            isSubmitting = true;
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ingresando...';

            setTimeout(() => {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 5000);
        });

        const emailInput = document.querySelector('input[name="email"]');
        const rememberCheckbox = document.querySelector('input[name="remember"]');

        const savedEmail = localStorage.getItem('remembered_email');
        if (savedEmail && emailInput.value === '') {
            emailInput.value = savedEmail;
            rememberCheckbox.checked = true;
        }

        rememberCheckbox.addEventListener('change', function() {
            if (this.checked && emailInput.value) {
                localStorage.setItem('remembered_email', emailInput.value);
            } else {
                localStorage.removeItem('remembered_email');
            }
        });

        emailInput.addEventListener('input', function() {
            if (this.value.length > 3) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(this.value)) {
                    passwordField.focus();
                }
            }
        });
    });

    document.getElementById('recoveryForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }, 5000);
    });
    </script>
</body>
</html>