<?php
session_start();

// Cabeceras de seguridad
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com data:; img-src 'self' data: https:; connect-src 'self'; frame-src 'none'; object-src 'none';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/sesioncontrolador.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$database = new Database();
$db = $database->conectar();

$controller = new SesionControlador($db);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {

    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token de seguridad inválido");
    }

    // Recoger y sanitizar datos
    $cedula = trim(htmlspecialchars($_POST['cedula'] ?? ''));
    $correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validaciones
    if (empty($cedula) || !preg_match('/^[0-9]{5,15}$/', $cedula)) {
        $mensaje = "Cédula inválida. Debe contener solo números (5-15 dígitos)";
    } else if (!$correo || strlen($correo) > 100) {
        $mensaje = "Correo electrónico inválido";
    } else if (empty($password) || strlen($password) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres";
    } else if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $mensaje = "La contraseña debe contener mayúsculas, minúsculas y números";
    } else if ($password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden";
    }

    // Registrar usuario si no hay errores
    if (empty($mensaje)) {
        // Solo pasamos cédula, correo y password
        // Los demás datos (nombres, apellidos) se buscan en la BD por cédula
        $resultado = $controller->registrar($cedula, $correo, $password);

        if ($resultado) {
            echo "<script>
                alert('Solicitud de usuario enviada correctamente.\\\\n\\\\nSu cuenta está pendiente de aprobación por el administrador.\\\\n\\\\nSe le notificará por correo cuando sea activada.');
                window.location.href = '../index.php';
            </script>";
            exit;
        } else {
            $mensaje = "Error al registrar usuario.\\n\\nPosibles causas:\\n• Cédula no encontrada en el sistema\\n• Ya tiene una cuenta de usuario asociada\\n• El correo electrónico ya está registrado\\n\\nVerifique sus datos o contacte al administrador.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registro seguro de usuario - Sistema SGEA Sistema de Gestión y Enrutamiento Adminsitrativo">
    <link rel="shortcut icon" href="../imagenes/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <title>Registrar Usuario - Sistema SGEA Sistema de Gestión y Enrutamiento Adminsitrativo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: url("../imagenes/login3.jpg") no-repeat center center/cover;
            color: #fff;
            text-align: center;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .form-box {
            background: rgba(59, 57, 57, 0.5);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 30px 25px;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        h2 {
            margin-bottom: 20px;
            color: #fff;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #444;
            border-radius: 5px;
            background: #333;
            color: #fff;
            font-size: 16px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
        }

        .input-group {
            position: relative;
        }

        .icono-alerta {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #ffcc00;
            display: none;
            cursor: pointer;
        }

        .mensaje-error {
            display: none;
            color: #ff5555;
            font-size: 12px;
            margin-top: 5px;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }

        .strength-weak { color: #ff4444; }
        .strength-medium { color: #ffaa00; }
        .strength-strong { color: #44ff44; }

        button {
            background: #007bff;
            color: #fff;
            padding: 14px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        button:disabled {
            background: #555;
            cursor: not-allowed;
        }

        .volver-link {
            color: #0af;
            text-decoration: none;
            display: block;
            margin-top: 20px;
            font-size: 14px;
        }

        .volver-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: none;
        }

        .alert-error {
            background: #ff4444;
            color: white;
        }

        .alert-success {
            background: #44ff44;
            color: black;
        }

        /* Media Queries para Responsive */
        @media (max-width: 480px) {
            body {
                padding: 15px;
                justify-content: flex-start;
                padding-top: 30px;
            }

            .form-box {
                padding: 20px 15px;
                border-radius: 10px;
            }

            h2 {
                font-size: 20px;
                margin-bottom: 15px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            input, select {
                padding: 14px;
                font-size: 16px;
            }

            button {
                padding: 16px 20px;
            }

            .volver-link {
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Crear Cuenta de Usuario</h2>

        <?php if (!empty($mensaje)): ?>
            <div id="alert-message" class="alert alert-error" style="display: block;">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php else: ?>
            <div id="alert-message" class="alert"></div>
        <?php endif; ?>

        <form method="POST" id="registroForm">
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="cedula">Cédula registrada *:</label>
                <input type="text" id="cedula" name="cedula" 
                       placeholder="Ingresa tu número de cédula"
                       pattern="[0-9]{5,15}" 
                       required>
                <small style="color: #ccc;">Debe coincidir con la cédula con la que fue registrado como contratista</small>
            </div>

            <div class="form-group">
                <label for="correo">Correo electrónico para acceso *:</label>
                <div class="input-group">
                    <input type="email" id="correo" name="correo" placeholder="ejemplo@correo.com"
                        maxlength="100" required>
                    <span id="correo-alerta" class="icono-alerta" title="Este correo ya está registrado"></span>
                </div>
                <small id="mensaje-error" class="mensaje-error"></small>
            </div>

            <div class="form-group">
                <label for="password">Contraseña *:</label>
                <input type="password" id="password" name="password"
                    placeholder="Mínimo 8 caracteres con mayúsculas, minúsculas y números"
                    minlength="8" required>
                <div id="password-strength" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña *:</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="Repite tu contraseña"
                    minlength="8" required>
                <small id="password-match-error" class="mensaje-error">Las contraseñas no coinciden</small>
            </div>

            <div class="form-group" style="background: rgba(0, 123, 255, 0.1); padding: 10px; border-radius: 5px; border: 1px solid #007bff;">
                <p style="font-size: 13px; color: #b3d7ff; margin: 0;">
                    <i class="fas fa-info-circle"></i> 
                    Nota: Su cuenta quedará en estado <strong>pendiente</strong> hasta que sea aprobada por el administrador.
                </p>
            </div>

            <button type="submit" name="registrar" id="btnRegistrar">Enviar Solicitud</button>
        </form>

        <a href="menuAdministrador.php" class="volver-link">Volver al inicio</a>
    </div>

    <script>
        // Mostrar/ocultar alerta
        function mostrarAlerta(mensaje, tipo) {
            const alerta = document.getElementById('alert-message');
            alerta.textContent = mensaje;
            alerta.className = 'alert ' + (tipo === 'error' ? 'alert-error' : 'alert-success');
            alerta.style.display = 'block';

            setTimeout(() => {
                alerta.style.display = 'none';
            }, 5000);
        }

        // Validar formato de cédula
        document.getElementById("cedula").addEventListener("input", function(e) {
            // Solo permitir números
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Habilitar/deshabilitar botón según validación
            validarFormulario();
        });

        // Verificar fortaleza de contraseña
        document.getElementById("password").addEventListener("input", function(e) {
            const password = this.value;
            const strengthElement = document.getElementById("password-strength");

            if (password.length === 0) {
                strengthElement.textContent = "";
                return;
            }

            let strength = 0;
            let feedback = "";

            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            if (strength <= 2) {
                feedback = "Débil";
                strengthElement.className = "password-strength strength-weak";
            } else if (strength <= 4) {
                feedback = "Media";
                strengthElement.className = "password-strength strength-medium";
            } else {
                feedback = "Fuerte";
                strengthElement.className = "password-strength strength-strong";
            }

            strengthElement.textContent = `Fortaleza: ${feedback}`;
            
            // Verificar coincidencia de contraseñas
            verificarCoincidenciaContraseñas();
            validarFormulario();
        });

        // Verificar coincidencia de contraseñas
        document.getElementById("confirm_password").addEventListener("input", function(e) {
            verificarCoincidenciaContraseñas();
            validarFormulario();
        });

        function verificarCoincidenciaContraseñas() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const errorElement = document.getElementById("password-match-error");

            if (confirmPassword.length === 0) {
                errorElement.style.display = "none";
                return;
            }

            if (password !== confirmPassword) {
                errorElement.style.display = "block";
            } else {
                errorElement.style.display = "none";
            }
        }

        // Verificar correo en tiempo real
        document.getElementById("correo").addEventListener("blur", function() {
            verificarCorreo(this.value);
        });

        let timeoutId;
        document.getElementById("correo").addEventListener("input", function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                verificarCorreo(this.value);
            }, 1000);
            validarFormulario();
        });

        function verificarCorreo(correo) {
            const alerta = document.getElementById("correo-alerta");
            const mensajeError = document.getElementById("mensaje-error");

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (correo.trim() === "") {
                alerta.style.display = "none";
                mensajeError.style.display = "none";
                return;
            }

            if (!emailRegex.test(correo)) {
                alerta.style.display = "inline";
                mensajeError.style.display = "block";
                mensajeError.textContent = "Formato de correo inválido";
                return;
            }

            // Verificar si el correo existe
            fetch("manage/verificar_correoManage.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "correo=" + encodeURIComponent(correo)
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return res.json();
            })
            .then(data => {
                if (data.existe) {
                    alerta.style.display = "inline";
                    mensajeError.style.display = "block";
                    mensajeError.textContent = "Este correo ya está registrado. Intenta con otro.";
                } else {
                    alerta.style.display = "none";
                    mensajeError.style.display = "none";
                }
            })
            .catch(err => {
                console.error("Error en la verificación:", err);
                alerta.style.display = "none";
                mensajeError.style.display = "none";
            });
        }

        // Función para validar todo el formulario
        function validarFormulario() {
            const cedula = document.getElementById("cedula").value;
            const correo = document.getElementById("correo").value;
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const correoAlerta = document.getElementById("correo-alerta");
            const btnRegistrar = document.getElementById("btnRegistrar");
            
            // Validar cédula (5-15 dígitos)
            const cedulaValida = /^[0-9]{5,15}$/.test(cedula);
            
            // Validar correo básico
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const correoValido = emailRegex.test(correo);
            const correoDisponible = correoAlerta.style.display !== "inline";
            
            // Validar contraseñas
            const passwordValida = password.length >= 8 && 
                                  /[A-Z]/.test(password) && 
                                  /[a-z]/.test(password) && 
                                  /[0-9]/.test(password);
            const passwordsCoinciden = password === confirmPassword || confirmPassword.length === 0;
            
            // Habilitar botón si todo es válido
            if (cedulaValida && correoValido && correoDisponible && passwordValida && passwordsCoinciden) {
                btnRegistrar.disabled = false;
            } else {
                btnRegistrar.disabled = true;
            }
        }

        // Validar formulario antes de enviar
        document.getElementById("registroForm").addEventListener("submit", function(e) {
            const cedula = document.getElementById("cedula").value;
            const correo = document.getElementById("correo").value;
            const correoAlerta = document.getElementById("correo-alerta");
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;

            // Validar cédula
            if (!/^[0-9]{5,15}$/.test(cedula)) {
                e.preventDefault();
                mostrarAlerta("La cédula debe contener solo números (5-15 dígitos).", "error");
                return false;
            }

            // Validar correo
            if (correoAlerta.style.display === "inline") {
                e.preventDefault();
                mostrarAlerta("Por favor, usa un correo electrónico que no esté registrado.", "error");
                return false;
            }

            // Validar fortaleza de contraseña
            if (password.length < 8) {
                e.preventDefault();
                mostrarAlerta("La contraseña debe tener al menos 8 caracteres.", "error");
                return false;
            }

            if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/.test(password)) {
                e.preventDefault();
                mostrarAlerta("La contraseña debe contener mayúsculas, minúsculas y números.", "error");
                return false;
            }

            // Validar que las contraseñas coincidan
            if (password !== confirmPassword) {
                e.preventDefault();
                mostrarAlerta("Las contraseñas no coinciden.", "error");
                return false;
            }

            // Validar campos obligatorios
            const campos = ['cedula', 'correo', 'password', 'confirm_password'];
            for (let campo of campos) {
                if (!document.getElementById(campo).value.trim()) {
                    e.preventDefault();
                    mostrarAlerta("Por favor, completa todos los campos obligatorios.", "error");
                    return false;
                }
            }

            return true;
        });

        // Inicializar validación
        document.addEventListener("DOMContentLoaded", function() {
            validarFormulario();
        });
    </script>
</body>
</html>