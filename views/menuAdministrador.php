<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/config_helper.php';
$database = new Database();
$db = $database->conectar();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Verificar que sea administrador (ya sea administrador original o asistente con acceso)
if ($_SESSION['tipo_usuario'] !== 'administrador') {
    if ($_SESSION['tipo_usuario'] === 'asistente') {
        header("Location: menuAsistente.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

$fotoPerfil = '../imagenes/usuarios/imagendefault.png';

try {
    $query = "SELECT p.foto_perfil 
              FROM usuario u 
              INNER JOIN persona p ON u.id_persona = p.id_persona 
              WHERE u.id_usuario = :id_usuario";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $_SESSION['usuario_id']);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado && !empty($resultado['foto_perfil'])) {
        $fotoBD = $resultado['foto_perfil'];
        $fotoPerfil = (strpos($fotoBD, '/') === 0) ? '..' . $fotoBD : $fotoBD;
    }
} catch (Exception $e) {
    error_log("Error cargando foto: " . $e->getMessage());
}

$nombreUsuario = $_SESSION['nombres'] ?? '';
$apellidoUsuario = $_SESSION['apellidos'] ?? '';
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);

if (empty($nombreCompleto)) {
    $nombreCompleto = 'Administrador del Sistema';
}

$correoUsuario = $_SESSION['correo'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Secretaría de Minas y Energía</title>
    <link rel="icon" href="../imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/../imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/admin.css">
    <style>

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Recorta la imagen para llenar el circulo sin deformar */
            border-radius: 50%;
            display: block;
        }
        /* Botón para volver como asistente */
        .return-assistant-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 22px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
            display: <?php echo isset($_SESSION['usuario_original']) ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .return-assistant-btn:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);
        }
        
        .return-assistant-btn i {
            font-size: 18px;
        }
        
        /* Indicador en el sidebar */
        .session-info {
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            border-left: 3px solid #f59e0b;
        }
        
        .session-info strong {
            color: white;
            display: block;
            margin-bottom: 3px;
            font-weight: 600;
        }
        
        .session-info span {
            display: block;
            font-size: 11px;
            margin-top: 5px;
        }
        
        /* Banner informativo en el header */
        .access-info-banner {
            margin-top: 15px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
            display: <?php echo isset($_SESSION['usuario_original']) ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 12px;
        }
        
        .access-info-banner i {
            color: #d97706;
            font-size: 18px;
        }
        
        .access-info-banner .banner-content {
            color: #92400e;
            font-weight: 500;
            flex: 1;
        }
        
        .access-info-banner .banner-link {
            color: #b45309;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
            transition: color 0.3s ease;
            margin-left: 8px;
        }
        
        .access-info-banner .banner-link:hover {
            color: #92400e;
        }
    </style>
</head>
<body>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Logo Institucional -->
            <div class="logo-section">
                <div class="department-name" style="font-size: 25px; font-weight: bold;">Gestión del Sistena</div>
            </div>

            <!-- Perfil del Usuario -->
            <div class="user-profile-sidebar">
                <div class="user-avatar">
                    <img src="<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Foto de Perfil" onerror="this.src='../imagenes/usuarios/imagendefault.png'">
                </div>
                <div class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></div>
                <div class="user-role">Administrador</div>
                <div class="user-email">
                    <?php echo htmlspecialchars($correoUsuario); ?>
                </div>
                
            </div>

            <!-- Menú de Navegación -->
            <div class="nav-section">
            <div class="nav-section">

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="manage/parametrizacion.php" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-sliders-h"></i></span>
                        <span class="nav-text">Parametrización</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="gestion_usuarios.php" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                        <span class="nav-text">Gestión de Usuarios</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="configuracion.php" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-cog"></i></span>
                        <span class="nav-text">Configuración</span>
                    </a>
                </li>

                <!-- Cerrar sesión -->
                <li class="nav-item">
                    <a href="#" class="nav-link logout-link" id="logoutBtn">
                        <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                        <span class="nav-text">Cerrar Sesión</span>
                    </a>
                </li>

            </ul>
        </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content dashboard-main-content">
            <!-- Encabezado -->
            <div class="main-header">
                <h1 class="welcome-title">Panel de Control Administrativo</h1>
                <p class="welcome-subtitle">Gestione todos los aspectos del sistema de la Secretaría de Minas y Energía</p>
            </div>
            <!-- Acciones Rápidas -->
            <div class="quick-actions">
                <div class="section-title">
                    <span>Acciones Rápidas</span>
                </div>
                <div class="actions-grid">
                    <button class="action-btn" onclick="window.location.href='registrarusuario.php'">
                        <i class="fas fa-user-plus"></i>
                        <span>Agregar Usuario</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='administrador/parametrizacion.php'">
                        <i class="fas fa-sliders-h"></i>
                        <span>Parametrización</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
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
        </main>
        
        <!-- Botón flotante para volver como asistente -->
        <?php if (isset($_SESSION['usuario_original'])): ?>
        <button class="return-assistant-btn" onclick="volverComoAsistente()">
            Terminar
        </button>
        <?php endif; ?>
    </div>
    <script>
        function volverComoAsistente() {
            if (confirm('¿Desea volver a su sesión original como asistente?')) {
                const btn = document.querySelector('.return-assistant-btn');
                if (btn) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regresando...';
                    btn.disabled = true;

                    window.location.href = '../ajax/volver_asistente.php';

                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }, 3000);
                } else {
                    window.location.href = '../ajax/volver_asistente.php';
                }
            }
        }
        
        // Cerrar sesión de administrador después de inactividad (1 hora) - solo para asistentes
        <?php if (isset($_SESSION['usuario_original'])): ?>
        let inactivityTimer;
        
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                if (confirm('Su sesión de administrador ha expirado por inactividad. ¿Volver como asistente?')) {
                    window.location.href = '../ajax/volver_asistente.php';
                }
            }, 3600000); // 1 hora = 3,600,000 ms
        }
        
        // Reiniciar timer en eventos de usuario
        ['mousemove', 'keypress', 'click', 'scroll'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer);
        });

        resetInactivityTimer();
        <?php endif; ?>

        window.history.pushState(null, "", window.location.href);

        window.onpopstate = function () {
            window.history.pushState(null, "", window.location.href);
        };

        const USER_CORREO = "<?php echo $_SESSION['correo'] ?? 'No identificado'; ?>";
        const USER_TIPO = "<?php echo $_SESSION['tipo_usuario'] ?? 'No definido'; ?>";
        const USER_NOMBRE_COMPLETO = <?php echo json_encode($nombreCompleto); ?>;
        
    </script>
     <!-- Script para evitar retroceder -->
    <script>
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
    <script src="../javascript/admin.js"></script>
</body>
</html>