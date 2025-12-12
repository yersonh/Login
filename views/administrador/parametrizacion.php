<?php
session_start();

// Solo administradores
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['tipo_usuario'] !== 'administrador') {
    if ($_SESSION['tipo_usuario'] === 'asistente') {
        header("Location: menuAsistente.php");
    } else if ($_SESSION['tipo_usuario'] === 'contratista') {
        header("Location: menu.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

// 2. Obtener datos del usuario
$nombreUsuario = $_SESSION['nombres'] ?? '';
$apellidoUsuario = $_SESSION['apellidos'] ?? '';
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
if (empty($nombreCompleto)) {
    $nombreCompleto = 'Usuario del Sistema';
}

$tipoUsuario = $_SESSION['tipo_usuario'] ?? '';
$correoUsuario = $_SESSION['correo'] ?? '';

// 3. Obtener datos de configuración desde la base de datos
require_once __DIR__ . '/../../controllers/ConfiguracionControlador.php';
$controladorConfig = new ConfiguracionControlador();
$configuracion = $controladorConfig->obtenerDatos();

// Si no hay datos, usar valores por defecto
if (empty($configuracion)) {
    $configuracion = [
        'version_sistema' => '1.0.0',
        'tipo_licencia' => 'Evaluación',
        'valida_hasta' => '2026-03-31',
        'desarrollado_por' => 'SisgonTech',
        'direccion' => 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta',
        'correo_contacto' => 'gobernaciondelmeta@meta.gov.co',
        'telefono' => '(57 -608) 6 818503',
        'ruta_logo' => '../../imagenes/logo.png',
        'texto_alternativo' => 'Logo Gobernación del Meta',
        'enlace_web' => 'https://www.meta.gov.co'
    ];
}

// Calcular días restantes si existe fecha de validez
$diasRestantes = '90 días'; // Valor por defecto
if (!empty($configuracion['valida_hasta'])) {
    $hoy = new DateTime();
    $validaHasta = new DateTime($configuracion['valida_hasta']);
    if ($validaHasta > $hoy) {
        $diferencia = $hoy->diff($validaHasta);
        $diasRestantes = $diferencia->days . ' días';
    } else {
        $diasRestantes = '0 días (Expirada)';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parametrización - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/parametrizar.css">
</head>
<body>
    <div class="app-container">
        <!-- Cabecera con indicador de admin -->
        <header class="app-header">
            <div class="header-content">
                <div class="department-info">
                    <h1>GOBERNACIÓN DEL META</h1>
                    <h2>
                        Secretaría de Minas y Energía
                    </h2>
                </div>
                <div class="user-profile">
                    <div class="welcome-user">
                        <i class="fas fa-user-circle"></i>
                        <span>Bienvenido(a)</span>
                        <strong>
                            <?php echo htmlspecialchars($nombreCompleto); ?>
                        </strong>
                    </div>
                    <div class="user-role">
                        <i class="fas fa-user-shield"></i> Administrador
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Contenido principal -->
        <main class="app-main">
            <div class="page-title">
                <h1><i class="fas fa-sliders-h"></i> Panel de Parametrización</h1>
                <button class="back-button" onclick="window.location.href='../menuAdministrador.php'">
                    <i class="fas fa-arrow-left"></i> Volver
                </button>
            </div>
            
            <!-- Mensajes de alerta -->
            <div id="successAlert" class="alert alert-success" style="display: none;">
                <i class="fas fa-check-circle"></i> Los cambios se han guardado correctamente.
            </div>
            
            <div id="errorAlert" class="alert alert-error" style="display: none;">
                <i class="fas fa-exclamation-circle"></i> Ha ocurrido un error. Por favor, intente nuevamente.
            </div>
            
            <!-- Panel de configuración del logo -->
            <div class="config-panel">
                <h2><i class="fas fa-image"></i> Configuración del Logo</h2>
                
                <div class="logo-config">
                    <div class="current-logo">
                        <h3>Logo Actual</h3>
                        <div class="logo-preview">
                            <img id="currentLogo" 
                                 src="<?php echo htmlspecialchars($configuracion['ruta_logo'] ?? '../../imagenes/logo.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($configuracion['texto_alternativo'] ?? 'Logo actual'); ?>" 
                                 onerror="this.src='https://via.placeholder.com/300x120/004a8d/ffffff?text=LOGO+ACTUAL'">
                        </div>
                        <p class="logo-info">Tamaño recomendado: 300x120 px (Formato: PNG, JPG o SVG)</p>
                    </div>
                    
                    <div class="logo-form">
                        <h3>Cambiar Logo</h3>
                        <form id="logoForm" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="newLogo">Seleccionar nuevo archivo:</label>
                                <div class="file-input-container">
                                    <input type="file" id="newLogo" name="newLogo" class="file-input" 
                                           accept=".png,.jpg,.jpeg,.svg,.gif">
                                    <label for="newLogo" class="file-input-label">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span id="fileName">Haga clic para seleccionar un archivo</span>
                                    </label>
                                </div>
                                <small class="form-text">Formatos aceptados: PNG, JPG, JPEG, SVG, GIF (Máx. 2MB)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="logoAltText">Texto alternativo (SEO):</label>
                                <input type="text" id="logoAltText" class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['texto_alternativo'] ?? 'Logo Gobernación del Meta'); ?>" 
                                       maxlength="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="logoLink">Enlace al hacer clic:</label>
                                <input type="url" id="logoLink" class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['enlace_web'] ?? 'https://www.meta.gov.co'); ?>" 
                                       placeholder="https://ejemplo.com">
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary" id="saveLogoBtn">
                                    <i class="fas fa-save"></i> Guardar Cambios del Logo
                                </button>
                                <button type="button" class="btn btn-secondary" id="restoreLogoBtn">
                                    <i class="fas fa-undo"></i> Restaurar Logo Predeterminado
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Formulario de Parametrización -->
            <div class="config-panel param-form-section">
                <h2><i class="fas fa-cogs"></i> Configuración del Sistema</h2>
                
                <form id="paramForm">
                    <div class="param-grid">
                        <!-- Grupo 1: Información Básica -->
                        <div class="param-group">
                            <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                            <div class="form-group">
                                <label for="version">Versión:</label>
                                <input type="text" id="version" class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['version_sistema'] ?? '1.0.0'); ?>" 
                                       placeholder="Ej: 1.0.0">
                            </div>
                            
                            <div class="form-group">
                                <label for="tipoLicencia">Tipo de Licencia:</label>
                                <input type="text" id="tipoLicencia" class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['tipo_licencia'] ?? 'Evaluación'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="validaHasta">Válida hasta:</label>
                                <input type="date" id="validaHasta" class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['valida_hasta'] ?? '2026-03-31'); ?>">
                            </div>
                           
                            <div class="form-group">
                                <label for="diasRestantes">Días restantes de evaluación:</label>
                                <input type="text" id="diasRestantes" class="form-control" 
                                       value="<?php echo htmlspecialchars($diasRestantes); ?>" readonly>
                            </div>
                        </div>
                        
                        <!-- Grupo 2: Información del Desarrollador -->
                        <div class="param-group">
                            <h3><i class="fas fa-code"></i> Información del Desarrollador</h3>
                            <div class="form-group">
                                <label for="desarrolladoPor">Desarrollado por:</label>
                                <input type="text" id="desarrolladoPor" class="form-control" 
                                       value="<?php echo htmlspecialchars($configuracion['desarrollado_por'] ?? 'SisgonTech'); ?>" 
                                       placeholder="Nombre del desarrollador">
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion">Dirección:</label>
                                <textarea id="direccion" class="form-control" rows="3" 
                                          placeholder="Dirección completa"><?php echo htmlspecialchars($configuracion['direccion'] ?? 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta'); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <label for="contacto">Contacto:</label>
                                    <input type="email" id="contacto" class="form-control" 
                                           value="<?php echo htmlspecialchars($configuracion['correo_contacto'] ?? 'gobernaciondelmeta@meta.gov.co'); ?>" 
                                           placeholder="correo@ejemplo.com">
                                </div>
                                
                                <div class="form-col">
                                    <label for="telefono">Teléfono:</label>
                                    <input type="tel" id="telefono" class="form-control" 
                                           value="<?php echo htmlspecialchars($configuracion['telefono'] ?? '(57 -608) 6 818503'); ?>" 
                                           placeholder="(XXX) XXX-XXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="saveConfigBtn">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetConfigBtn">
                            <i class="fas fa-redo"></i> Restaurar Valores Predeterminados
                        </button>
                    </div>
                </form>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="app-footer">
            <div class="footer-left">
                <div class="footer-logo-container">
                    <img src="<?php echo htmlspecialchars($configuracion['ruta_logo'] ?? '../../imagenes/logo.png'); ?>" 
                         alt="<?php echo htmlspecialchars($configuracion['texto_alternativo'] ?? 'Logo Gobernación del Meta'); ?>" 
                         class="footer-logo" 
                         onerror="this.src='https://via.placeholder.com/200x80/004a8d/ffffff?text=Gobernación+del+Meta'">
                    <div class="developer-info">
                        <div class="developer-name">
                            <?php echo htmlspecialchars($configuracion['desarrollado_por'] ?? 'SisgonTech'); ?>
                        </div>
                        <div>Sistema de Gestión para Gobernación del Meta</div>
                    </div>
                </div>
            </div>
            <div class="footer-right">
                <div class="contact-info">
                    <div>
                        <i class="fas fa-phone-alt"></i>
                        <span>Cel. <?php echo htmlspecialchars($configuracion['telefono'] ?? '(57 -608) 6 818503'); ?></span>
                    </div>
                    <div>
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($configuracion['correo_contacto'] ?? 'gobernaciondelmeta@meta.gov.co'); ?></span>
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
    
    <!-- Incluir el archivo JavaScript externo -->
    <script src="../../js/parametrizar.js"></script>
</body>
</html>