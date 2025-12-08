<?php
session_start();

// VERIFICACIÓN CRÍTICA: Solo administradores pueden registrar usuarios
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    // Redirigir al menú principal si no es administrador
    header("Location: /menuAdministrador.php");
    exit();
}

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com data:; img-src 'self' data: https:; connect-src 'self'; frame-src 'none'; object-src 'none';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/sesioncontrolador.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$database = new Database();
$db = $database->conectar();
$controller = new SesionControlador($db);

// Variable para mensajes
$mensaje = '';
$tipo_mensaje = ''; // 'success' o 'error'
$valores_formulario = [
    'nombres' => '',
    'apellidos' => '',
    'correo' => '',
    'telefono' => '',
    'tipo_usuario' => 'usuario'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = "Token de seguridad inválido";
        $tipo_mensaje = 'error';
    } else {
        $nombres = trim(htmlspecialchars($_POST['nombres'] ?? ''));
        $apellidos = trim(htmlspecialchars($_POST['apellidos'] ?? ''));
        $correo = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
        $telefono = preg_replace('/[^0-9]/', '', $_POST['telefono'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $tipo_usuario = $_POST['tipo_usuario'] ?? 'usuario';

        // Guardar valores para el formulario
        $valores_formulario = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'correo' => $_POST['correo'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'tipo_usuario' => $tipo_usuario
        ];

        // Validaciones
        $errores = [];

        if (empty($nombres) || strlen($nombres) > 50) {
            $errores[] = "Nombre inválido";
        }

        if (empty($apellidos) || strlen($apellidos) > 50) {
            $errores[] = "Apellido inválido";
        }

        if (!$correo || strlen($correo) > 100) {
            $errores[] = "Correo electrónico inválido";
        }

        if (empty($telefono) || strlen($telefono) < 7 || strlen($telefono) > 15) {
            $errores[] = "Teléfono inválido";
        }

        if (empty($password) || strlen($password) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres";
        }

        if (!preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password)) {
            $errores[] = "La contraseña debe contener mayúsculas, minúsculas y números";
        }

        if ($password !== $confirm_password) {
            $errores[] = "Las contraseñas no coinciden";
        }

        // Validar tipo de usuario
        $tipos_permitidos = ['usuario', 'asistente', 'administrador'];
        if (!in_array($tipo_usuario, $tipos_permitidos)) {
            $errores[] = "Tipo de usuario no válido";
        }

        if (empty($errores)) {
            // Registrar usuario con tipo específico
            $resultado = $controller->registrar(
                $nombres,
                $apellidos,
                $correo,
                $telefono,
                $password,
                $tipo_usuario // Agregar el tipo de usuario
            );

            if ($resultado) {
                $mensaje = "Usuario registrado exitosamente";
                $tipo_mensaje = 'success';
                // Limpiar formulario después de éxito
                $valores_formulario = [
                    'nombres' => '',
                    'apellidos' => '',
                    'correo' => '',
                    'telefono' => '',
                    'tipo_usuario' => 'usuario'
                ];
            } else {
                $mensaje = "Error al registrar usuario. El correo podría ya estar registrado.";
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = implode("<br>", $errores);
            $tipo_mensaje = 'error';
        }
    }

    // Regenerar token CSRF después de procesar
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            background: rgba(59, 57, 57, 0.85);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 30px 25px;
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h2 {
            margin-bottom: 20px;
            color: #fff;
            font-size: 24px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .admin-notice {
            background: rgba(0, 123, 255, 0.2);
            border: 1px solid #007bff;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #cce7ff;
        }

        .admin-notice i {
            color: #007bff;
            margin-right: 8px;
        }

        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #ddd;
            font-size: 14px;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #555;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 16px;
            transition: all 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #007bff;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
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
            color: #ff6b6b;
            font-size: 13px;
            margin-top: 5px;
            background: rgba(255, 107, 107, 0.1);
            padding: 5px 10px;
            border-radius: 4px;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 13px;
            font-weight: 500;
        }

        .strength-weak { color: #ff4444; }
        .strength-medium { color: #ffaa00; }
        .strength-strong { color: #44ff44; }

        button {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            padding: 15px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
        }

        button:hover {
            background: linear-gradient(135deg, #0056b3, #003d82);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .volver-link {
            color: #4dc3ff;
            text-decoration: none;
            display: block;
            margin-top: 20px;
            font-size: 14px;
            transition: color 0.3s;
        }

        .volver-link:hover {
            color: #80d4ff;
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert-error {
            background: rgba(255, 68, 68, 0.2);
            border: 1px solid #ff4444;
            color: #ffcccc;
        }

        .alert-success {
            background: rgba(68, 255, 68, 0.2);
            border: 1px solid #44ff44;
            color: #ccffcc;
        }

        .user-info {
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            font-size: 13px;
            color: #aaa;
            border-left: 3px solid #007bff;
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
                border-radius: 12px;
                margin-top: 20px;
            }

            h2 {
                font-size: 22px;
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
                font-size: 16px;
            }

            .volver-link {
                margin-top: 15px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2><i class="fas fa-user-plus"></i> Registrar Nuevo Usuario</h2>
        
        <!-- Notificación de administrador -->
        <div class="admin-notice">
            <i class="fas fa-shield-alt"></i> 
            Modo administrador: Registrando nuevo usuario para el sistema
        </div>

        <!-- Mostrar mensajes del servidor -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>" id="server-message" style="display: block;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div id="alert-message" class="alert"></div>

        <form method="POST" id="registroForm">
            <!-- Token CSRF -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="nombres"><i class="fas fa-user"></i> Nombres:</label>
                <input type="text" id="nombres" name="nombres" placeholder="Ingresa los nombres"
                    maxlength="50" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" required
                    value="<?php echo htmlspecialchars($valores_formulario['nombres']); ?>">
            </div>

            <div class="form-group">
                <label for="apellidos"><i class="fas fa-user"></i> Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" placeholder="Ingresa los apellidos"
                    maxlength="50" pattern="[A-Za-záéíóúÁÉÍÓÚñÑ\s]+" required
                    value="<?php echo htmlspecialchars($valores_formulario['apellidos']); ?>">
            </div>

            <div class="form-group">
                <label for="correo"><i class="fas fa-envelope"></i> Correo electrónico:</label>
                <div class="input-group">
                    <input type="email" id="correo" name="correo" placeholder="ejemplo@correo.com"
                        maxlength="100" required
                        value="<?php echo htmlspecialchars($valores_formulario['correo']); ?>">
                    <span id="correo-alerta" class="icono-alerta" title="Este correo ya está registrado"></span>
                </div>
                <small id="mensaje-error" class="mensaje-error"></small>
            </div>

            <div class="form-group">
                <label for="telefono"><i class="fas fa-phone"></i> Teléfono:</label>
                <input type="tel" id="telefono" name="telefono" placeholder="Ingresa el teléfono"
                    pattern="[0-9]{7,15}" maxlength="15" required
                    value="<?php echo htmlspecialchars($valores_formulario['telefono']); ?>">
                <small style="color: #aaa; font-size: 12px;">Solo números, 7-15 dígitos</small>
            </div>

            <div class="form-group">
                <label for="tipo_usuario"><i class="fas fa-user-tag"></i> Tipo de Usuario:</label>
                <select id="tipo_usuario" name="tipo_usuario" required>
                    <option value="usuario" <?php echo $valores_formulario['tipo_usuario'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                    <option value="asistente" <?php echo $valores_formulario['tipo_usuario'] === 'asistente' ? 'selected' : ''; ?>>Asistente</option>
                    <option value="administrador" <?php echo $valores_formulario['tipo_usuario'] === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña:</label>
                <input type="password" id="password" name="password"
                    placeholder="Mínimo 8 caracteres con mayúsculas, minúsculas y números"
                    minlength="8" required>
                <div id="password-strength" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="Repite la contraseña"
                    minlength="8" required>
                <small id="password-match-error" class="mensaje-error">Las contraseñas no coinciden</small>
            </div>

            <!-- Información del administrador que está registrando -->
            <div class="user-info">
                <i class="fas fa-user-shield"></i> 
                Registrando como: <strong><?php echo $_SESSION['nombres'] ?? 'Administrador'; ?></strong>
            </div>

            <button type="submit" name="registrar" id="btnRegistrar">
                <i class="fas fa-user-plus"></i> Registrar Usuario
            </button>
        </form>

        <a href="/menuAdministrador.php" class="volver-link">
            <i class="fas fa-arrow-left"></i> Volver al Menú Principal
        </a>
    </div>

    <script>
        // Mostrar/ocultar alerta
        function mostrarAlerta(mensaje, tipo) {
            const alerta = document.getElementById('alert-message');
            alerta.textContent = mensaje;
            alerta.className = 'alert ' + (tipo === 'error' ? 'alert-error' : 'alert-success');
            alerta.style.display = 'block';

            // Ocultar después de 5 segundos
            setTimeout(() => {
                alerta.style.display = 'none';
            }, 5000);
        }

        // Ocultar mensaje del servidor después de 5 segundos
        const serverMessage = document.getElementById('server-message');
        if (serverMessage) {
            setTimeout(() => {
                serverMessage.style.display = 'none';
            }, 5000);
        }

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
        });

        // Verificar coincidencia de contraseñas
        document.getElementById("confirm_password").addEventListener("input", verificarCoincidenciaContraseñas);

        function verificarCoincidenciaContraseñas() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const errorElement = document.getElementById("password-match-error");
            const btnRegistrar = document.getElementById("btnRegistrar");

            if (confirmPassword.length === 0) {
                errorElement.style.display = "none";
                return;
            }

            if (password !== confirmPassword) {
                errorElement.style.display = "block";
                btnRegistrar.disabled = true;
            } else {
                errorElement.style.display = "none";
                // Solo habilitar si no hay otros errores
                const correoError = document.getElementById("correo-alerta").style.display !== "inline";
                if (correoError) {
                    btnRegistrar.disabled = false;
                }
            }
        }

        // Verificar correo (necesita actualizar la ruta del endpoint)
        document.getElementById("correo").addEventListener("blur", function() {
            verificarCorreo(this.value);
        });

        let timeoutId;
        document.getElementById("correo").addEventListener("input", function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                verificarCorreo(this.value);
            }, 1000);
        });

        function verificarCorreo(correo) {
            const alerta = document.getElementById("correo-alerta");
            const mensajeError = document.getElementById("mensaje-error");
            const btnRegistrar = document.getElementById("btnRegistrar");

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (correo.trim() === "") {
                alerta.style.display = "none";
                mensajeError.style.display = "none";
                btnRegistrar.disabled = false;
                return;
            }

            if (!emailRegex.test(correo)) {
                alerta.style.display = "inline";
                mensajeError.style.display = "block";
                mensajeError.textContent = "Formato de correo inválido";
                btnRegistrar.disabled = true;
                return;
            }

            // IMPORTANTE: Actualiza esta ruta según tu estructura de archivos
            fetch("/manage/verificar_correo.php", {
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
                    btnRegistrar.disabled = true;
                } else {
                    alerta.style.display = "none";
                    mensajeError.style.display = "none";
                    // Verificar si las contraseñas coinciden antes de habilitar
                    const password = document.getElementById("password").value;
                    const confirmPassword = document.getElementById("confirm_password").value;
                    if (password === confirmPassword || confirmPassword.length === 0) {
                        btnRegistrar.disabled = false;
                    }
                }
            })
            .catch(err => {
                console.error("Error en la verificación:", err);
                alerta.style.display = "none";
                mensajeError.style.display = "none";
                btnRegistrar.disabled = false;
            });
        }

        // Validar formulario antes de enviar
        document.getElementById("registroForm").addEventListener("submit", function(e) {
            const correo = document.getElementById("correo").value;
            const alerta = document.getElementById("correo-alerta");
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;

            if (alerta.style.display === "inline") {
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

            // Validar campos requeridos
            const campos = ['nombres', 'apellidos', 'telefono', 'password', 'confirm_password'];
            for (let campo of campos) {
                if (!document.getElementById(campo).value.trim()) {
                    e.preventDefault();
                    mostrarAlerta("Por favor, completa todos los campos.", "error");
                    return false;
                }
            }

            return true;
        });

        // Formatear teléfono (solo números)
        document.getElementById("telefono").addEventListener("input", function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>