<?php
require_once __DIR__ . '/../../config/database.php';

$database = new Database();
$db = $database->conectar();

// Variable para mensajes
$mensaje = "";
$tipoMensaje = "";
$mostrarFormulario = false;
$token_valido = "";
$tokenData = null; // Inicializar

// Validación token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $mensaje = "Token no proporcionado o inválido.";
    $tipoMensaje = "error";
} else {
    $token_valido = trim($_GET['token']);
    
    // VERIFICACIÓN CORREGIDA
    if (!preg_match('/^[a-f0-9]{64}$/i', $token_valido)) {
        $mensaje = "Token con formato inválido.";
        $tipoMensaje = "error";
    } else {
        try {
            // CONSULTA CORREGIDA - usa id_recovery en lugar de id
            $stmt = $db->prepare("
                SELECT rt.*, u.correo
                FROM recovery_tokens rt
                JOIN usuario u ON rt.id_usuario = u.id_usuario
                WHERE rt.token = :token 
                  AND rt.expiracion > NOW() 
                  AND rt.usado = FALSE
                LIMIT 1
            ");
            $stmt->bindParam(':token', $token_valido);
            $stmt->execute();
            $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$tokenData) {
                $mensaje = "El enlace de recuperación ha expirado o ya fue utilizado.";
                $tipoMensaje = "error";
            } else {
                $mostrarFormulario = true;
            }
        } catch (Exception $e) {
            error_log("Error en recuperación: " . $e->getMessage());
            $mensaje = "Error interno del sistema. Por favor intenta más tarde.";
            $tipoMensaje = "error";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mostrarFormulario && $tokenData) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token_post = $_POST['token'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $mensaje = "Por favor completa todos los campos.";
        $tipoMensaje = "error";
    } elseif (strlen($password) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        $tipoMensaje = "error";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $mensaje = "La contraseña debe contener al menos una letra mayúscula.";
        $tipoMensaje = "error";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $mensaje = "La contraseña debe contener al menos una letra minúscula.";
        $tipoMensaje = "error";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $mensaje = "La contraseña debe contener al menos un número.";
        $tipoMensaje = "error";
    } elseif ($password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipoMensaje = "error";
    } else {
        try {
            $nuevaContrasena = password_hash($password, PASSWORD_DEFAULT);

            $db->beginTransaction();

            $stmtUpdate = $db->prepare("
                UPDATE usuario 
                SET contrasena = :contrasena 
                WHERE id_usuario = :id_usuario
            ");
            $stmtUpdate->bindParam(':contrasena', $nuevaContrasena);
            $stmtUpdate->bindParam(':id_usuario', $tokenData['id_usuario']);
            $stmtUpdate->execute();

            $stmtUsed = $db->prepare("
                UPDATE recovery_tokens 
                SET usado = TRUE 
                WHERE id_recovery = :id_recovery
            ");
            $stmtUsed->bindParam(':id_recovery', $tokenData['id_recovery']);

            $db->commit();

            $mensaje = "Contraseña cambiada correctamente. Serás redirigido al inicio de sesión en 3 segundos...";
            $tipoMensaje = "success";
            $mostrarFormulario = false;
            
            header("refresh:3;url=/index.php");
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            $mensaje = "Error al procesar la solicitud. Por favor intenta nuevamente.";
            $tipoMensaje = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;

            /* 🔹 Imagen de fondo */
            background-image: url("/imagenes/hola.jpg");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #0056b3;
        }

        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }

        .password-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .password-requirements li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($mensaje): ?>
            <div class="message <?php echo $tipoMensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
            <?php if ($tipoMensaje === 'success'): ?>
                <p style="text-align: center; color: #666;">
                    Redirigiendo al inicio de sesión...
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($mostrarFormulario && !$mensaje): ?>
            <h2>Restablecer Contraseña</h2>
            
            <div class="password-requirements">
                <p><strong>Requisitos:</strong></p>
                <ul>
                    <li>• Mínimo 8 caracteres</li>
                    <li>• Al menos una mayúscula</li>
                    <li>• Al menos una minúscula</li>
                    <li>• Al menos un número</li>
                    <li>• Ambas contraseñas deben coincidir</li>
                </ul>
            </div>

            <form method="POST" action="" id="passwordForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token_valido); ?>">
                
                <div class="form-group">
                    <label for="password">Nueva contraseña:</label>
                    <input type="password" name="password" id="password" required minlength="8">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar contraseña:</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>

                <button type="submit" id="submitBtn">Cambiar contraseña</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($mostrarFormulario && !$mensaje): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const form = document.getElementById('passwordForm');
            
            if (passwordInput && confirmInput && form) {
                passwordInput.addEventListener('input', validatePassword);
                confirmInput.addEventListener('input', validateConfirmPassword);
                
                form.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirm = confirmInput.value;
                    
                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Las contraseñas no coinciden. Por favor verifica.');
                        confirmInput.focus();
                    }
                });
            }

            function validatePassword() {
                const password = passwordInput.value;
                
                if (password.length >= 8) {
                    passwordInput.style.borderColor = '#28a745';
                } else {
                    passwordInput.style.borderColor = '#dc3545';
                }
            }

            function validateConfirmPassword() {
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                
                if (confirm && password !== confirm) {
                    confirmInput.style.borderColor = '#dc3545';
                } else if (confirm) {
                    confirmInput.style.borderColor = '#28a745';
                } else {
                    confirmInput.style.borderColor = '#ddd';
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>