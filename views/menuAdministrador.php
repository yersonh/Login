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
        /* ESTILOS EXISTENTES - NO MODIFICAR */
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
        
        @media (max-width: 768px) {
            .return-assistant-btn {
                margin-top: 8px;
                padding: 12px 18px;
                font-size: 14px;
                bottom: 15px;
                right: 15px;
            }
        }
        @media (max-width: 576px) {
            .return-assistant-btn {
                margin-top: 6px;
                padding: 10px 15px;
                font-size: 13px;
                bottom: 15px;
                right: 15px;
            }
        }

        @media (max-width: 480px) {
            .return-assistant-btn {
                bottom: 10px;
                right: 10px;
                padding: 10px 12px;
                font-size: 12px;
            }
        }

        @media (max-height: 700px) and (max-width: 768px) {
            .return-assistant-btn {
                bottom: 8px;
                right: 8px;
                padding: 8px 10px;
            }
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
        
        /* ESTILOS NUEVOS PARA EL MODAL - OPTIMIZADO PARA MÓVILES */
        .mobile-warning-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.92);
            z-index: 999999;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        
        .mobile-warning-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalAppear 0.4s ease-out;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.97);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .modal-header i {
            font-size: 42px;
            margin-bottom: 12px;
            display: block;
            color: #90caf9;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .modal-body {
            padding: 20px;
            flex-grow: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .modal-body p {
            font-size: 15px;
            line-height: 1.5;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .modal-features {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            border-left: 3px solid #1e88e5;
        }
        
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-item i {
            color: #1e88e5;
            margin-right: 10px;
            font-size: 14px;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .feature-item span {
            font-size: 13px;
            color: #444;
            line-height: 1.4;
        }
        
        .modal-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 12px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-warning i {
            color: #f39c12;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .modal-warning span {
            color: #856404;
            font-weight: 600;
            font-size: 13px;
            line-height: 1.3;
        }
        
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-shrink: 0;
        }
        
        .modal-btn {
            flex: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, #1e88e5, #0d47a1);
            color: white;
        }
        
        .modal-btn-primary:hover, .modal-btn-primary:active {
            background: linear-gradient(135deg, #1565c0, #0a3a7a);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.4);
        }
        
        .modal-btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #dee2e6;
        }
        
        .modal-btn-secondary:hover, .modal-btn-secondary:active {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Optimizaciones para pantallas pequeñas */
        @media (max-width: 480px) {
            .mobile-warning-modal {
                padding: 10px;
            }
            
            .mobile-warning-content {
                max-height: 85vh;
                border-radius: 10px;
            }
            
            .modal-header {
                padding: 16px;
            }
            
            .modal-header i {
                font-size: 36px;
                margin-bottom: 10px;
            }
            
            .modal-header h2 {
                font-size: 18px;
            }
            
            .modal-body {
                padding: 16px;
            }
            
            .modal-body p {
                font-size: 14px;
                margin-bottom: 12px;
            }
            
            .modal-features {
                padding: 12px;
                margin: 12px 0;
            }
            
            .feature-item {
                margin-bottom: 6px;
                padding: 4px 0;
            }
            
            .feature-item i {
                font-size: 13px;
                margin-top: 1px;
            }
            
            .feature-item span {
                font-size: 12px;
            }
            
            .modal-warning {
                padding: 10px;
                margin: 12px 0;
            }
            
            .modal-warning i {
                font-size: 16px;
            }
            
            .modal-warning span {
                font-size: 12px;
            }
            
            .modal-buttons {
                flex-direction: column;
                gap: 10px;
                margin-top: 15px;
            }
            
            .modal-btn {
                padding: 10px 12px;
                font-size: 13px;
            }
        }
        
        /* Para pantallas muy pequeñas */
        @media (max-width: 360px) {
            .modal-header {
                padding: 14px;
            }
            
            .modal-header h2 {
                font-size: 17px;
            }
            
            .modal-body {
                padding: 14px;
            }
            
            .modal-body p {
                font-size: 13px;
            }
        }
        
        /* Para orientación horizontal en móviles */
        @media (max-width: 768px) and (orientation: landscape) {
            .mobile-warning-content {
                max-width: 350px;
                max-height: 85vh;
            }
            
            .modal-body {
                max-height: 50vh;
                overflow-y: auto;
            }
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
                    <a href="administrador/parametrizacion.php" class="nav-link active">
                        <span class="nav-icon"><i class="fas fa-sliders-h"></i></span>
                        <span class="nav-text">Parametrización</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="administrador/gestion_usuarios.php" class="nav-link active">
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
            <i class="fas fa-arrow-left"></i>
            Volver al Menú
        </button>
        <?php endif; ?>
    </div>
    
    <!-- MODAL PARA DISPOSITIVOS MÓVILES - SOLO SE MUESTRA EN MÓVILES -->
    <div id="mobileWarningModal" class="mobile-warning-modal">
        <div class="mobile-warning-content">
            <div class="modal-header">
                <i class="fas fa-desktop"></i>
                <h2>Recomendación de Uso</h2>
            </div>
            <div class="modal-body">
                <p>El <strong>Panel Administrativo</strong> está optimizado para su uso en <strong>computadores de escritorio o laptops</strong>.</p>
                
                <div class="modal-features">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Mejor visualización en pantallas grandes</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Navegación más fácil con mouse y teclado</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Acceso completo a todas las funcionalidades</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Interfaz diseñada para productividad</span>
                    </div>
                </div>
                
                <div class="modal-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Recomendamos usar un computador para la mejor experiencia</span>
                </div>
                
                <div class="modal-buttons">
                    <button class="modal-btn modal-btn-primary" id="continueMobileBtn">
                        <i class="fas fa-mobile-alt"></i>
                        Continuar en Móvil
                    </button>
                    <button class="modal-btn modal-btn-secondary" id="understandBtn">
                        <i class="fas fa-check"></i>
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // FUNCIONES EXISTENTES - NO MODIFICAR
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
        
        // NUEVO CÓDIGO PARA EL MODAL
        document.addEventListener('DOMContentLoaded', function() {
            // Detectar si es dispositivo móvil
            function esDispositivoMovil() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                       (window.innerWidth <= 768);
            }
            
            // Verificar si ya se mostró el modal en esta sesión
            function yaSeMostroModal() {
                return sessionStorage.getItem('mobileWarningShown') === 'true';
            }
            
            // Mostrar modal si es móvil y no se ha mostrado antes
            if (esDispositivoMovil() && !yaSeMostroModal()) {
                setTimeout(function() {
                    const modal = document.getElementById('mobileWarningModal');
                    if (modal) {
                        modal.style.display = 'flex';
                        // Guardar en sesión que ya se mostró
                        sessionStorage.setItem('mobileWarningShown', 'true');
                    }
                }, 1000); // REDUCIDO A 1 SEGUNDO
            }
            
            // Configurar botones del modal
            const continueBtn = document.getElementById('continueMobileBtn');
            const understandBtn = document.getElementById('understandBtn');
            const modal = document.getElementById('mobileWarningModal');
            
            if (continueBtn) {
                continueBtn.addEventListener('click', function() {
                    if (modal) modal.style.display = 'none';
                });
            }
            
            if (understandBtn) {
                understandBtn.addEventListener('click', function() {
                    if (modal) modal.style.display = 'none';
                });
            }
            
            // Cerrar modal al hacer clic fuera
            if (modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            }
            
            // Mejorar la experiencia táctil en móviles
            if (esDispositivoMovil()) {
                // Agregar efecto táctil a los botones del modal
                const modalButtons = document.querySelectorAll('.modal-btn');
                modalButtons.forEach(btn => {
                    btn.addEventListener('touchstart', function() {
                        this.style.transform = 'translateY(-1px)';
                        this.style.opacity = '0.9';
                    });
                    
                    btn.addEventListener('touchend', function() {
                        this.style.transform = '';
                        this.style.opacity = '';
                    });
                });
                
                // Optimizar scroll en el modal
                const modalBody = document.querySelector('.modal-body');
                if (modalBody) {
                    modalBody.addEventListener('touchmove', function(e) {
                        // Permitir scroll solo dentro del modal
                        e.stopPropagation();
                    });
                }
            }
        });
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