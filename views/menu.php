<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'contratista') {
    // Si no es contratista, redirigir según su rol
    if (isset($_SESSION['tipo_usuario'])) {
        if ($_SESSION['tipo_usuario'] === 'asistente') {
            header("Location: menuAsistente.php");
        } else if ($_SESSION['tipo_usuario'] === 'administrador') {
            header("Location: menuAdministrador.php");
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
    <link rel="stylesheet" href="styles/menu.css">
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
                    <div class="user-role">Contratista</div>
                </div>
            </div>
        </header>
        
        <!-- Contenido principal -->
        <main class="app-main">
            <div class="welcome-section">
                <h3>Portal de Servicios Digitales</h3>
                <p>Seleccione uno de los servicios disponibles para acceder a las herramientas y recursos del sistema</p>
            </div>
            
            <!-- Grid de servicios - SOLO 2 SERVICIOS -->
            <div class="services-grid">
                <!-- Servicio 1: Programar actividad -->
                <div class="service-card" id="programar-actividad">
                    <div class="service-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="service-name">Programar Actividad</div>
                    <div class="service-desc">Crear y gestionar nuevas actividades y eventos</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Servicio 2: Dar de alta -->
                <div class="service-card" id="dar-de-alta">
                    <div class="service-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="service-name">Dar de Alta actividad</div>
                    <div class="service-desc">Descargar actividad programada pendiente de ejecución</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
            </div>
        </main>
        
        <button class="logout-btn" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </button>
        
        <footer class="app-footer">
            <div class="footer-left">
                <div class="footer-logo-container">
                    <img src="../imagenes/logo.png" alt="Logo Gobernación del Meta" class="footer-logo">
                    <img src="../imagenes/sisgoTech.png" alt="Logo SisgoTech" class="footer-logo">
                </div>
            </div>
            <div class="footer-right">
                <div class="contact-info">
                    <div>
                        <i class="fas fa-phone-alt"></i>
                        <span>Cel. (57 -608) 6 818503</span>
                    </div>
                    <div>
                        <i class="fas fa-envelope"></i>
                        <span>gobernaciondelmeta@meta.gov.co</span>
                    </div>
                    <div>
                        <i class="fas fa-mobile-alt"></i>
                        <span>+57 (310) 631 0227</span>
                    </div>
                </div>
                <div class="copyright">
                    © <?php echo date('Y'); ?> Gobernación del Meta • Todos los derechos reservados
                </div>
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
    
    <script src="../javascript/menu.js"></script>
    
</body>
</html>