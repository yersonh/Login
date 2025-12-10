<?php
session_start();

// Si ya se confirmó el logout, destruir sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Si se desea destruir la sesión completamente, borra también la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finalmente, destruir la sesión
    session_destroy();
    
    // Responder con JSON para AJAX o redirigir
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
            'redirect' => '../index.php'
        ]);
        exit;
    } else {
        header('Location: ../index.php');
        exit;
    }
}

// Obtener nombre del usuario para mostrar en el modal
$nombreUsuario = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrar Sesión - Portal de Servicios</title>
    <link rel="icon" href="../imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos para la página de logout */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: #212529;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* Estilos del modal (los mismos que tenías) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        
        .modal-clave {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #004a8d 0%, #003366 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .modal-header h3 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        .modal-header p {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .modal-body {
            padding: 40px 35px 35px;
            position: relative;
        }
        
        .modal-body::before {
            content: '🔐';
            position: absolute;
            top: -22px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 28px;
            background: white;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid #004a8d;
            z-index: 3;
        }
        
        .modal-body p {
            text-align: center;
            color: #4a5568;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.6;
            font-weight: 500;
        }
        
        .modal-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .btn-modal {
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.7s;
        }
        
        .btn-modal:hover::before {
            left: 100%;
        }
        
        .btn-ingresar {
            background: linear-gradient(135deg, #004a8d 0%, #003366 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 74, 141, 0.2);
        }
        
        .btn-ingresar:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 74, 141, 0.3);
        }
        
        .btn-cancelar {
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }
        
        .btn-cancelar:hover {
            background: #e2e8f0;
            border-color: #cbd5e0;
            transform: translateY(-2px);
        }
        
        /* Estilos específicos para logout */
        .logout-content {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logout-icon {
            font-size: 48px;
            color: #004a8d;
            margin-bottom: 15px;
        }
        
        .user-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .logout-message {
            margin: 10px 0 5px;
            color: #495057;
        }
        
        .logout-submessage {
            font-size: 14px;
            color: #6c757d;
        }
        
        /* Spinner para cuando se procesa */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Modal de confirmación de logout -->
    <div class="modal-overlay active">
        <div class="modal-clave">
            <div class="modal-header">
                <h3>¿Cerrar sesión?</h3>
                <p>Confirmación requerida</p>
            </div>
            <div class="modal-body">
                <div class="logout-content">
                    <i class="fas fa-sign-out-alt logout-icon"></i>
                    <div class="user-name"><?php echo htmlspecialchars($nombreUsuario); ?></div>
                    
                    <p class="logout-message">¿Confirma cerrar la sesión actual?</p> 
                    <p class="logout-submessage">Será redirigido a la página de inicio de sesión.</p>
                </div>
                
                <form id="logoutForm" method="POST" action="">
                    <input type="hidden" name="confirm_logout" value="1">
                    <div class="modal-buttons">
                        <button type="submit" class="btn-modal btn-ingresar" id="confirmLogoutBtn">
                            <span id="btnText">Sí, cerrar sesión</span>
                        </button>
                        <a href="javascript:history.back()" class="btn-modal btn-cancelar" id="cancelLogout">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForm = document.getElementById('logoutForm');
            const confirmBtn = document.getElementById('confirmLogoutBtn');
            const btnText = document.getElementById('btnText');
            
            // Manejar el envío del formulario
            logoutForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Cambiar estado del botón
                confirmBtn.disabled = true;
                btnText.innerHTML = '<span class="spinner"></span> Cerrando sesión...';
                
                // Enviar formulario vía AJAX
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams(new FormData(logoutForm))
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Cambiar a éxito
                        btnText.innerHTML = '<i class="fas fa-check"></i> Sesión cerrada';
                        confirmBtn.style.background = 'linear-gradient(135deg, #28a745 0%, #218838 100%)';
                        
                        // Redirigir después de 1 segundo
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        // Mostrar error
                        alert('Error: ' + (data.message || 'No se pudo cerrar sesión'));
                        confirmBtn.disabled = false;
                        btnText.textContent = 'Sí, cerrar sesión';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión. Intente nuevamente.');
                    confirmBtn.disabled = false;
                    btnText.textContent = 'Sí, cerrar sesión';
                    
                    // Si falla AJAX, enviar formulario normal
                    setTimeout(() => {
                        logoutForm.submit();
                    }, 2000);
                });
            });
            
            // Manejar la tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    window.history.back();
                }
            });
            
            // Enfocar el botón de confirmar por seguridad
            confirmBtn.focus();
        });
    </script>
</body>
</html>