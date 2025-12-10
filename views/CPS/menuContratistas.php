<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    if (isset($_SESSION['tipo_usuario'])) {
        if ($_SESSION['tipo_usuario'] === 'administrador') {
            header("Location: menu.php");
        } else if ($_SESSION['tipo_usuario'] === 'usuario') {
            header("Location: menu.php");
        } else {
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
                
                <!-- Opción 2: Modificar datos contratista -->
                <div class="service-card" id="modificar-contratista">
                    <div class="service-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="service-name">Modificar Datos Contratista</div>
                    <div class="service-desc">Actualizar información de contratistas existentes</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Opción 3: Parametrizar obligaciones -->
                <div class="service-card" id="parametrizar-obligaciones">
                    <div class="service-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="service-name">Parametrizar Obligaciones</div>
                    <div class="service-desc">Configurar obligaciones contractuales</div>
                    <div class="service-status status-available">Disponible</div>
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
        
        <button class="volver-btn" id="volverBtn">
            <i class="fas fa-arrow-left"></i>
            <span>Volver</span>
        </button>
        
        <footer class="app-footer">
            <div class="footer-left">
                <div class="footer-logo-container">
                    <img src="../../imagenes/logo.png" alt="Logo Gobernación del Meta" class="footer-logo">
                    <div class="developer-info">
                        <img src="../../imagenes/sisgoTech.png" alt="Logo SisgoTech" class="footer-logo">
                    </div>
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