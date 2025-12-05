<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema SGEA</title>
    <link rel="icon" href="<?php echo $base_url; ?>/imagenes/logo.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            border-radius: 10px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #1e8ee9;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 i {
            color: #1e8ee9;
        }
        
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #5a6c7d;
            font-size: 14px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            margin-top: 15px;
        }
        
        .user-info .badge {
            background: #1e8ee9;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 35px 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-align: center;
            border: 1px solid #e0e0e0;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1e8ee9, #1565c0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            font-size: 32px;
            color: white;
        }
        
        .card-title {
            color: #2c3e50;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .card-description {
            color: #5a6c7d;
            font-size: 15px;
            line-height: 1.5;
            margin-bottom: 30px;
            max-width: 300px;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #1e8ee9, #1565c0);
            color: white;
            border: none;
            padding: 14px 35px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            min-width: 200px;
            justify-content: center;
        }
        
        .btn-admin:hover {
            background: linear-gradient(135deg, #1565c0, #0d4d8c);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(30, 142, 233, 0.3);
        }
        
        .btn-admin:active {
            transform: translateY(0);
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .system-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .system-info h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #5a6c7d;
            font-weight: 500;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            margin-left: 15px;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .dashboard {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .card {
                padding: 25px 20px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Encabezado -->
        <div class="header">
            <h1>
                <i class="fas fa-cogs"></i>
                Panel de Administración - Sistema SGEA
            </h1>
            <p style="color: #5a6c7d; font-size: 15px;">
                Sistema de Gestión y Enrutamiento Administrativo - Gobernación
            </p>
            
            <div class="user-info">
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['nombres'] . ' ' . $_SESSION['apellidos']); ?></strong>
                    <span style="color: #7f8c8d; margin: 0 10px;">|</span>
                    <?php echo htmlspecialchars($_SESSION['correo']); ?>
                </div>
                <div>
                    <span class="badge">
                        <i class="fas fa-user-shield"></i> ADMINISTRADOR
                    </span>
                    <button class="logout-btn" onclick="window.location.href='logout.php'">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Panel de Botones Principales -->
        <div class="dashboard">
            <!-- Botón 1: Parametrizar Sistema -->
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <h2 class="card-title">Parametrizar Sistema</h2>
                <p class="card-description">
                    Configure los parámetros generales del sistema, ajustes de seguridad, 
                    plantillas de documentos y configuración institucional.
                </p>
                <button class="btn-admin" onclick="window.location.href='/manage/parametrizar_sistema.php'">
                    <i class="fas fa-cog"></i> Acceder a Parametrización
                </button>
            </div>
            
            <!-- Botón 2: Gestionar Contratistas -->
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h2 class="card-title">Gestionar Contratistas</h2>
                <p class="card-description">
                    Administre la información de contratistas, registre nuevos, 
                    actualice datos y gestione el historial de contrataciones.
                </p>
                <button class="btn-admin" onclick="window.location.href='/manage/gestion_contratistas.php'">
                    <i class="fas fa-users-cog"></i> Gestionar Contratistas
                </button>
            </div>
        </div>
        
        <!-- Información del Sistema -->
        <div class="system-info">
            <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Usuario ID:</span>
                    <span class="info-value">#<?php echo htmlspecialchars($_SESSION['usuario_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Último Acceso:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Versión Sistema:</span>
                    <span class="info-value">SGEA v1.0</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sesión Activa:</span>
                    <span class="info-value" style="color: #27ae60;">
                        <i class="fas fa-check-circle"></i> Activa
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Pie de Página -->
        <div class="footer">
            <p>
                <i class="fas fa-shield-alt"></i> Sistema SGEA - Gobernación
                <br>
                &copy; <?php echo date('Y'); ?> Sistema de Gestión y Enrutamiento Administrativo. Todos los derechos reservados.
            </p>
            <p style="font-size: 12px; margin-top: 10px; color: #95a5a6;">
                <i class="fas fa-lock"></i> Sesión segura | 
                <i class="fas fa-user-check"></i> Autenticado como administrador
            </p>
        </div>
    </div>

    <script>
        // Animación suave para los botones
        document.querySelectorAll('.btn-admin').forEach(button => {
            button.addEventListener('click', function(e) {
                // Agregar efecto de carga
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
                this.disabled = true;
                
                // Restaurar después de 1.5 segundos si no se redirige
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                }, 1500);
            });
        });
        
        // Actualizar hora en tiempo real
        function actualizarHora() {
            const ahora = new Date();
            const opciones = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('hora-actual').textContent = 
                ahora.toLocaleDateString('es-ES', opciones);
        }
        
        // Actualizar cada segundo
        setInterval(actualizarHora, 1000);
        actualizarHora();
        
        // Confirmar cierre de sesión
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            if (!confirm('¿Está seguro que desea cerrar sesión?')) {
                e.preventDefault();
            }
        });
        
        // Detectar inactividad (opcional)
        let tiempoInactividad = 0;
        const tiempoMaximoInactividad = 1800; // 30 minutos en segundos
        
        function resetearInactividad() {
            tiempoInactividad = 0;
        }
        
        function verificarInactividad() {
            tiempoInactividad++;
            if (tiempoInactividad >= tiempoMaximoInactividad) {
                if (confirm('Su sesión está por expirar por inactividad. ¿Desea continuar?')) {
                    resetearInactividad();
                } else {
                    window.location.href = 'logout.php?timeout=1';
                }
            }
        }
        
        // Eventos que resetean la inactividad
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evento => {
            document.addEventListener(evento, resetearInactividad);
        });
        
        // Verificar inactividad cada minuto
        setInterval(verificarInactividad, 60000);
    </script>
</body>
</html>