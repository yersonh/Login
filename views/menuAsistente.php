<?php
session_start();

require_once __DIR__ . '/../helpers/config_helper.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    // Si no es asistente, redirigir según su rol
    if (isset($_SESSION['tipo_usuario'])) {
        if ($_SESSION['tipo_usuario'] === 'administrador') {
            header("Location: menu.php");
        } else if ($_SESSION['tipo_usuario'] === 'contratista') {
            header("Location: menu.php");
        } else {
            // Rol desconocido
            header("Location: ../index.php");
        }
    } else {
        header("Location: ../index.php");
    }
    exit();
}

$nombreUsuario = isset($_SESSION['nombres']) ? $_SESSION['nombres'] : '';
$apellidoUsuario = isset($_SESSION['apellidos']) ? $_SESSION['apellidos'] : '';

$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);

if (empty($nombreCompleto)) {
    $nombreCompleto = 'Usuario del Sistema';
}

// Definir $base_url para usar en JavaScript si es necesario
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Servicios - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Enlace al archivo CSS modularizado -->
    <link rel="stylesheet" href="styles/asistente.css">

</head>
<body>
    
    <div class="app-container">
        <header class="app-header">
            <div class="header-content">
                <div class="department-info">
                    <h1>GOBERNACIÓN DEL META</h1>
                    <h2>Secretaría de Minas y Energía</h2>
                </div>
                <div class="user-profile">
                    <!-- Mensaje personalizado de bienvenida con PHP -->
                    <div class="welcome-user">
                        <i class="fas fa-user-circle"></i>
                        <span>Bienvenido(a) </span>
                        <strong style="font-size: 25px; font-weight: bold;">
                            <?php echo htmlspecialchars($nombreCompleto); ?>
                        </strong>
                    </div>
                    <div class="user-role">Asistente</div>
                    
                </div>
            </div>
        </header>
        
        <!-- Contenido principal -->
        <main class="app-main">
            <div class="welcome-section">
                <h3>Portal de Servicios Digitales</h3>
                <p>Seleccione uno de los servicios disponibles para acceder a las herramientas y recursos del sistema</p>
            </div>
            
            <!-- Grid de servicios -->
            <div class="services-grid">
                <!-- Servicio 1 CPS -->
                <div class="service-card CPS-card" id="CPS-card";>
                    <div class="service-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="service-name">Gestión CPS</div>
                    <div class="service-desc">Sistema de Control de Procesos y Seguimiento</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Servicio 2 Documentos -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="service-name">Gestión Documental</div>
                    <div class="service-desc">Repositorio digital de archivos</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 3 Correo -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="service-name">Correo Institucional</div>
                    <div class="service-desc">Correo electrónico corporativo</div>
                    <div class="service-status status-unavailable">Disponible</div>
                </div>
                
                <!-- Servicio 4 Drive SME -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="service-name">Almacenamiento virtual-Drives</div>
                    <div class="service-desc">Almacenamiento en la nube</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 5 APP RAI -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="service-name">APP RAI</div>
                    <div class="service-desc">Aplicación móvil para Reportes</div>
                    <div class="service-status status-unavailable">Disponible</div>
                </div>
                
                <!-- Servicio 6 Reuniones Virtuales -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="service-name">Reuniones Virtuales</div>
                    <div class="service-desc">Videoconferencias colaborativas</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 7 Agenda Digital -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="service-name">Agenda Digital</div>
                    <div class="service-desc">Calendarios y eventos</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 8 Sistema de Mapas -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="service-name">Sistema de Mapas</div>
                    <div class="service-desc">Información geográfica</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 9 Gestor de Tareas -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="service-name">Gestor de Tareas</div>
                    <div class="service-desc">Seguimiento de actividades</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 10: Gestion del sistema -->
                <div class="service-card admin-card" id="admin-card">
                    <div class="admin-only-badge">ADMIN</div>
                    <div class="service-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div class="service-name">Gestión del Sistema</div>
                    <div class="service-desc">Configuración del sistema y parámetros</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
            </div>
        </main>
                    <button class="logout-btn" id="logoutBtn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar sesión</span>
                    </button>
                    
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
    </div>
    
    <!-- MODAL PARA INGRESAR CLAVE -->
    <div class="modal-overlay" id="modalClave">
        <div class="modal-clave">
            <div class="modal-header">
                <h3>Acceso restringido</h3>
                <p>Verificación de seguridad requerida</p>
            </div>
            <div class="modal-body">
                <p>Ingrese la clave autorizada para parametrizar:</p>
                <div class="input-group">
                    <label for="inputClave">Clave de autorización</label>
                    <div class="clave-container">
                        <input type="password" id="inputClave" class="clave-input" placeholder="Digite la clave..." maxlength="20" autocomplete="off">
                        <button type="button" class="clave-eye" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="error-message" id="errorMessage"></div>
                <div class="modal-buttons">
                    <button class="btn-modal btn-ingresar" id="btnIngresarClave">
                        Ingresar
                    </button>
                    <button class="btn-modal btn-cancelar" id="btnCancelarClave">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
   <!-- Incluye BASE_URL si es necesario para JavaScript -->
    <script>
        const USER_CORREO = "<?php echo $_SESSION['correo'] ?? 'No identificado'; ?>";
        const USER_TIPO = "<?php echo $_SESSION['tipo_usuario'] ?? 'No definido'; ?>";
        const USER_NOMBRE_COMPLETO = <?php echo json_encode($nombreCompleto); ?>;
    </script>
    
    <script src="../javascript/asistente.js"></script>
    
    <!-- Script para evitar retroceder -->
    <script>
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
    
</body>
</html>