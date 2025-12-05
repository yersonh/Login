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

            header("Location: https://www.eltiempo.com/");
            exit();
            
        } else {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    } catch (PDOException $e) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

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

                header("Location: https://www.eltiempo.com/");
                exit();
                
            } else {
                // Mensaje profesional tipo Google/Instagram
                $error_message = "<div class='error-message'>
                                    <i class='fas fa-exclamation-circle'></i>
                                    <div>
                                        <strong>Credenciales incorrectas</strong>
                                        <p>El correo electrónico o la contraseña que ingresaste no son correctos. Por favor, verifica e intenta de nuevo.</p>
                                    </div>
                                  </div>";
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
    $stmt = $db->prepare("SELECT * FROM usuario WHERE correo = :correo LIMIT 1");
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

            $payload = [
                "sender" => [
                    "name"  => getenv('SMTP_FROM_NAME') ?: "Soporte - Ojo en la Vía",
                    "email" => getenv('SMTP_FROM') ?: "988a48002@smtp-brevo.com"
                ],
                "to" => [
                    ["email" => $correoUsuario]
                ],
                "subject" => "Recuperación de contraseña - Ojo en la Vía",
                "htmlContent" => "
                    <h2>Recuperación de Contraseña</h2>
                    <p>Hola,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña en <strong>Sistema SGEA Sistema de Gestión y Enrutamiento Adminsitrativo.</p>
                    <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                    <p>
                        <a href='{$link}'
                            style='background: #1e8ee9; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                            Restablecer Contraseña
                        </a>
                    </p>
                    <p><strong>Este enlace expirará en 1 hora.</strong></p>
                    <p>Si no solicitaste este cambio, ignora este mensaje.</p>
                    <br>
                    <p>Saludos,<br>El equipo de Sistema SGEA Sistema de Gestión y Enrutamiento Adminsitrativo</p>
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
                return "<div class='success-message'>
                            <i class='fas fa-check-circle'></i>
                            <div>
                                <strong>Correo enviado</strong>
                                <p>Hemos enviado un enlace de recuperación a <strong>$correoUsuario</strong>. Revisa tu bandeja de entrada.</p>
                            </div>
                        </div>";
            } else {
                return "<div class='error-message'>
                            <i class='fas fa-exclamation-circle'></i>
                            <div>
                                <strong>Error al enviar</strong>
                                <p>No pudimos enviar el correo en este momento. Por favor, intenta nuevamente más tarde.</p>
                            </div>
                        </div>";
            }
        } else {
            return "<div class='error-message'>
                        <i class='fas fa-exclamation-circle'></i>
                        <div>
                            <strong>Error del sistema</strong>
                            <p>Ocurrió un error al generar el enlace de recuperación. Intenta nuevamente.</p>
                        </div>
                    </div>";
        }
    } else {
        return "<div class='error-message'>
                    <i class='fas fa-exclamation-circle'></i>
                    <div>
                        <strong>Correo no encontrado</strong>
                        <p>El correo <strong>$correoUsuario</strong> no está registrado en nuestro sistema.</p>
                    </div>
                </div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema SGEA Sistema de Gestión y Enrutamiento Adminsitrativo</title>
    <link rel="icon" href="/imagenes/image.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/image.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    body {
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: url("<?php echo $base_url; ?>/imagenes/login3.jpg") no-repeat center center/cover;
        padding: 20px;
        background-color: #f5f5f7;
    }

    .container {
        width: 100%;
        max-width: 1000px;
        height: auto;
        min-height: 500px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
        background: white;
    }

    /* Lado izquierdo */
    .left {
        background: linear-gradient(135deg, #1e8ee9 0%, #1865c2 100%);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 50px;
        position: relative;
        overflow: hidden;
    }

    .left::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 30px 30px;
        opacity: 0.1;
    }

    .left h1 {
        font-size: 2.2rem;
        margin-bottom: 20px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }

    .left p {
        margin-bottom: 30px;
        color: rgba(255,255,255,0.9);
        line-height: 1.6;
        font-size: 1.05rem;
        position: relative;
        z-index: 1;
    }

    .features {
        margin-top: 30px;
        position: relative;
        z-index: 1;
    }

    .feature {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        color: rgba(255,255,255,0.9);
    }

    .feature i {
        margin-right: 12px;
        font-size: 1.1rem;
        color: rgba(255,255,255,0.8);
    }

    /* Lado derecho */
    .right {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 50px;
        background: white;
    }

    .right h2 {
        text-align: center;
        margin-bottom: 40px;
        font-size: 1.8rem;
        color: #1a1a1a;
        font-weight: 600;
    }

    /* Mensajes de error/success - Estilo profesional */
    .error-message {
        background: #fee;
        border: 1px solid #ffcdd2;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 25px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: slideIn 0.3s ease;
    }

    .error-message i {
        color: #f44336;
        font-size: 1.2rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .error-message strong {
        color: #d32f2f;
        font-size: 0.95rem;
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }

    .error-message p {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.4;
        margin: 0;
    }

    .success-message {
        background: #f0f9ff;
        border: 1px solid #bbdefb;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 25px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: slideIn 0.3s ease;
    }

    .success-message i {
        color: #4CAF50;
        font-size: 1.2rem;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .success-message strong {
        color: #2e7d32;
        font-size: 0.95rem;
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }

    .success-message p {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.4;
        margin: 0;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Formulario */
    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .input-with-icon {
        position: relative;
    }

    .input-with-icon i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
        font-size: 1.1rem;
        z-index: 2;
    }

    .input-with-icon input {
        width: 100%;
        padding: 14px 16px 14px 48px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: #fafafa;
        font-size: 1rem;
        color: #333;
        transition: all 0.2s ease;
        outline: none;
    }

    .input-with-icon input:focus {
        border-color: #1e8ee9;
        background: white;
        box-shadow: 0 0 0 3px rgba(30, 142, 233, 0.1);
    }

    .input-with-icon input.error {
        border-color: #f44336;
        background: #fffafa;
    }

    .input-with-icon input.success {
        border-color: #4CAF50;
    }

    .password-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 1.1rem;
        z-index: 2;
        padding: 5px;
        transition: color 0.2s;
    }

    .password-toggle:hover {
        color: #1e8ee9;
    }

    /* Opciones */
    .form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        font-size: 0.9rem;
    }

    .remember-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #555;
        cursor: pointer;
    }

    .remember-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #1e8ee9;
        cursor: pointer;
    }

    .forgot-link {
        color: #1e8ee9;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
        cursor: pointer;
    }

    .forgot-link:hover {
        color: #1865c2;
        text-decoration: underline;
    }

    /* Botón */
    .submit-btn {
        background: #1e8ee9;
        border: none;
        padding: 16px;
        width: 100%;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        border-radius: 10px;
        transition: all 0.2s ease;
        margin-bottom: 25px;
    }

    .submit-btn:hover {
        background: #1865c2;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 142, 233, 0.3);
    }

    .submit-btn:active {
        transform: translateY(0);
    }

    .submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* Registro */
    .signup-link {
        text-align: center;
        color: #666;
        font-size: 0.95rem;
    }

    .signup-link a {
        color: #1e8ee9;
        text-decoration: none;
        font-weight: 600;
        margin-left: 5px;
        transition: color 0.2s;
    }

    .signup-link a:hover {
        color: #1865c2;
        text-decoration: underline;
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background: white;
        margin: 15% auto;
        padding: 40px;
        border-radius: 20px;
        width: 90%;
        max-width: 450px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        margin-bottom: 25px;
    }

    .modal-header h3 {
        color: #1a1a1a;
        font-size: 1.4rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-close {
        position: absolute;
        right: 25px;
        top: 25px;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #999;
        cursor: pointer;
        transition: color 0.2s;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .modal-close:hover {
        color: #333;
        background: #f5f5f5;
    }

    .modal-text {
        color: #666;
        line-height: 1.5;
        margin-bottom: 25px;
        font-size: 0.95rem;
    }

    /* Responsive */
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
            padding: 40px 30px;
        }

        .left {
            order: 2;
        }

        .right {
            order: 1;
        }

        .form-options {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .modal-content {
            margin: 20% auto;
            padding: 30px;
        }
    }

    @media (max-width: 480px) {
        .left, .right {
            padding: 30px 25px;
        }

        .left h1 {
            font-size: 1.8rem;
        }

        .right h2 {
            font-size: 1.5rem;
        }

        .modal-content {
            padding: 25px;
            margin: 10% auto;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Lado izquierdo -->
        <div class="left">
            <h1>Bienvenido a SGEA</h1>
            <p>Administre, gestione, mida y haga seguimiento a actividades administrativas corporativas e institucionales. Gestione con solo un click!</p>
            <div class="features">
                <div class="feature">
                    <i class="fas fa-shield-alt"></i>
                    <span>Seguridad garantizada</span>
                </div>
                <div class="feature">
                    <i class="fas fa-rocket"></i>
                    <span>Acceso rápido y eficiente</span>
                </div>
                <div class="feature">
                    <i class="fas fa-headset"></i>
                    <span>Soporte 24/7</span>
                </div>
            </div>
        </div>

        <!-- Lado derecho -->
        <div class="right">
            <h2>Iniciar Sesión</h2>

            <?php if (isset($error_message)) echo $error_message; ?>
            <?php if (isset($mensaje_recuperacion)) echo $mensaje_recuperacion; ?>

            <form method="POST" action="" id="loginForm" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            placeholder="ejemplo@correo.com"
                            required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            placeholder="Ingresa tu contraseña"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-checkbox">
                        <input type="checkbox" name="remember">
                        <span>Recordarme</span>
                    </label>
                    <a class="forgot-link" id="openRecoveryModal">¿Olvidaste tu contraseña?</a>
                </div>

                <button class="submit-btn" type="submit">Iniciar Sesión</button>

                <div class="signup-link">
                    ¿No tienes cuenta? 
                    <a href="<?php echo $base_url; ?>/views/registrarusuario.php">Crear una cuenta</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de recuperación -->
    <div id="recoveryModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" id="closeModal">&times;</button>
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Recuperar Contraseña</h3>
            </div>
            <div class="modal-text">
                <p>Ingresa tu correo electrónico registrado y te enviaremos un enlace para restablecer tu contraseña.</p>
            </div>
            <form method="POST" action="" id="recoveryForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Correo electrónico" required autocomplete="email">
                    </div>
                </div>
                <button class="submit-btn" type="submit">Enviar enlace de recuperación</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        const toggleIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        });

        const modal = document.getElementById('recoveryModal');
        const openBtn = document.getElementById('openRecoveryModal');
        const closeBtn = document.getElementById('closeModal');

        openBtn.addEventListener('click', () => modal.style.display = 'block');
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', (e) => e.target === modal && (modal.style.display = 'none'));

        const loginForm = document.getElementById('loginForm');
        const submitBtn = loginForm.querySelector('.submit-btn');

        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
        });
    });
    </script>
</body>
</html>