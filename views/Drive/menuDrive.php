<?php
session_start();

require_once __DIR__ . '/../../helpers/config_helper.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    // Si no es asistente, redirigir según su rol
    if (isset($_SESSION['tipo_usuario'])) {
        if ($_SESSION['tipo_usuario'] === 'administrador') {
            header("Location: ../menu.php");
        } else if ($_SESSION['tipo_usuario'] === 'contratista') {
            header("Location: ../menu.php");
        } else {
            // Rol desconocido
            header("Location: ../../index.php");
        }
    } else {
        header("Location: ../../index.php");
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
    <link rel="stylesheet" href="../styles/menuDrive.css">
    <style>
        /* Estilos para los logos de Google Drive y OneDrive */
        .service-logo {
            width: 70px;
            height: 70px;
            margin-bottom: 10px;
            object-fit: contain;
        }
        
        .google-drive-logo {
            background: white;
            border-radius: 8px;
            padding: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .onedrive-logo {
            background: #0078D4;
            border-radius: 8px;
            padding: 8px;
        }
        
        /* Animación para el hover */
        .service-card:hover .service-logo {
            transform: scale(1.1);
            transition: transform 0.3s ease;
        }
    </style>
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
                <!-- Servicio 1: Google Drive -->
                <a href="https://drive.google.com" target="_blank" class="service-card-link">
                    <div class="service-card" id="google-drive">
                        <div class="service-icon">
                            <!-- Logo de Google Drive -->
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/12/Google_Drive_icon_%282020%29.svg/1024px-Google_Drive_icon_%282020%29.svg.png" 
                                 alt="Google Drive" 
                                 class="service-logo google-drive-logo">
                        </div>
                        <div class="service-name">Google Drive</div>
                        <div class="service-desc">Almacenamiento en la nube y colaboración de documentos</div>
                        <div class="service-status status-available">Disponible</div>
                    </div>
                </a>
                
                <!-- Servicio 2: Microsoft OneDrive -->
                <a href="https://onedrive.live.com" target="_blank" class="service-card-link">
                    <div class="service-card" id="onedrive">
                        <div class="service-icon">
                            <!-- Logo de Microsoft OneDrive -->
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f1/Microsoft_Office_OneDrive_%282019%E2%80%93present%29.svg/1024px-Microsoft_Office_OneDrive_%282019%E2%80%93present%29.svg.png" 
                                 alt="Microsoft OneDrive" 
                                 class="service-logo onedrive-logo">
                        </div>
                        <div class="service-name">Microsoft OneDrive</div>
                        <div class="service-desc">Almacenamiento en la nube de Microsoft</div>
                        <div class="service-status status-available">Disponible</div>
                    </div>
                </a>
            </div>
        </main>
        
        <button class="logout-btn" id="logoutBtn" onclick="window.location.href='../menuAsistente.php'">
            <i class="fas fa-arrow-left"></i>
            <span>Volver al menú</span>
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
        
        // Redirección directa al hacer clic en las tarjetas (backup por si el enlace falla)
        document.addEventListener('DOMContentLoaded', function() {
            // Google Drive
            document.getElementById('google-drive').addEventListener('click', function() {
                window.open('https://drive.google.com', '_blank');
            });
            
            // OneDrive
            document.getElementById('onedrive').addEventListener('click', function() {
                window.open('https://onedrive.live.com', '_blank');
            });
        });
    </script>
    
    <script src="../javascript/menu.js"></script>
    
</body>
</html>