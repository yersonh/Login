<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    if (isset($_SESSION['tipo_usuario'])) {
        if ($_SESSION['tipo_usuario'] === 'administrador') {
            header("Location: ../menuAdministrador.php");
        } else if ($_SESSION['tipo_usuario'] === 'usuario') {
            header("Location: ../menu.php");
        } else {
            header("Location: ../index.php");
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión AOM Contratistas - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Enlace al archivo CSS modularizado -->
    <link rel="stylesheet" href="../styles/contratistas.css">

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
                <h3>Menú AOM Contratistas</h3>
                <p>Selecciona una opción:</p>
            </div>
            
            <!-- Grid de servicios - 5 opciones en 2 filas (3 en primera, 2 en segunda) -->
            <div class="services-grid">
                <!-- Opción 1: Agregar nuevo contratista -->
                <div class="service-card" id="agregar-contratista">
                    <div class="service-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="service-name">Agregar Nuevo Contratista</div>
                    <div class="service-desc">Registrar nuevo contratista en el sistema</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Opción 2: Sitios Asignados -->
                <div class="service-card" id="sitios-asignados" onclick ="window.location.href='SitiosAsignados.php'">
                    <div class="service-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="service-name">Sitios Asignados</div>
                    <div class="service-desc">Visualización de sitios asignados</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Opción 3: Parametrizar obligaciones -->
                <div class="service-card" id="parametrizar-obligaciones">
                    <div class="service-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="service-name">Parametrizar Obligaciones</div>
                    <div class="service-desc">Configurar obligaciones contractuales</div>
                    <div class="service-status status-unavailable">Disponible</div>
                </div>
                
                <!-- Opción 4: Dashboard estadístico -->
                <div class="service-card" id="dashboard-estadistico">
                    <div class="service-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="service-name">Dashboard Estadístico</div>
                    <div class="service-desc">Visualización de métricas y estadísticas</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Opción 5: Visor de registrados -->
                <div class="service-card" id="visor-registrados">
                    <div class="service-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="service-name">Visor de Registrados</div>
                    <div class="service-desc">Consulta y visualización de contratistas registrados</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
            </div>
        </main>
        
        <button class="volver-btn" id="volverBtn" onclick="window.location.href='../menuAsistente.php'";>
            <i class="fas fa-arrow-left"></i>
            <span>Volver al Menú</span>
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
    
    <!-- Incluye BASE_URL si es necesario para JavaScript -->
    <script>
        const USER_CORREO = "<?php echo $_SESSION['correo'] ?? 'No identificado'; ?>";
        const USER_TIPO = "<?php echo $_SESSION['tipo_usuario'] ?? 'No definido'; ?>";
        const USER_NOMBRE_COMPLETO = <?php echo json_encode($nombreCompleto); ?>;
    </script>
    
    <script src="/../../javascript/contratistas.js"></script>
    
    <!-- Script para evitar retroceder -->
    <script>
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
    
</body>
</html>