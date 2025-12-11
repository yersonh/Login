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
    } else if ($_SESSION['tipo_usuario'] === 'con') {
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
                <button class="back-button" onclick="window.location.href='../menu.php'">
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
                            <img id="currentLogo" src="../../imagenes/logo.png" alt="Logo actual" 
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
                                       value="Logo Gobernación del Meta" maxlength="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="logoLink">Enlace al hacer clic:</label>
                                <input type="url" id="logoLink" class="form-control" 
                                       value="https://www.meta.gov.co" placeholder="https://ejemplo.com">
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary" onclick="uploadLogo()">
                                    <i class="fas fa-save"></i> Guardar Cambios del Logo
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="restoreDefaultLogo()">
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
                                       value="1.0.0" placeholder="Ej: 1.0.0">
                            </div>
                            
                            <div class="form-group">
                                <label for="tipoLicencia">Tipo de Licencia:</label>
                                <input type="text" id="tipoLicencia" class="form-control" 
                                value="Evaluación">
                            </div>
                            
                            <div class="form-group">
                                <label for="validaHasta">Válida hasta:</label>
                                <input type="date" id="validaHasta" class="form-control" 
                                       value="2026-03-31">
                            </div>
                           
                            <div class="form-group">
                                <label for="diasRestantes">Días restantes de evaluación:</label>
                                <input type="text" id="diasRestantes" class="form-control" 
                                    value="90 días" readonly>
                            </div>
                        </div>
                        
                        <!-- Grupo 2: Información del Desarrollador -->
                        <div class="param-group">
                            <h3><i class="fas fa-code"></i> Información del Desarrollador</h3>
                            <div class="form-group">
                                <label for="desarrolladoPor">Desarrollado por:</label>
                                <input type="text" id="desarrolladoPor" class="form-control" 
                                       value="SisgonTech" placeholder="Nombre del desarrollador">
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion">Dirección:</label>
                                <textarea id="direccion" class="form-control" rows="3" 
                                          placeholder="Dirección completa">Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta</textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <label for="contacto">Contacto:</label>
                                    <input type="email" id="contacto" class="form-control" 
                                           value="gobernaciondelmeta@meta.gov.co" placeholder="correo@ejemplo.com">
                                </div>
                                
                                <div class="form-col">
                                    <label for="telefono">Teléfono:</label>
                                    <input type="tel" id="telefono" class="form-control" 
                                           value="(57 -608) 6 818503" placeholder="(XXX) XXX-XXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" onclick="saveParameters()">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetParameters()">
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
                    <img src="../../imagenes/logo.png" alt="Logo Gobernación del Meta" class="footer-logo" 
                         onerror="this.src='https://via.placeholder.com/200x80/004a8d/ffffff?text=Gobernación+del+Meta'">
                    <div class="developer-info">
                        <div class="developer-name">SisgonTech</div>
                        <div>Sistema de Gestión para Gobernación del Meta</div>
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
    
    <script>
        // Mantener el mismo script que ya tienes...
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar nombre del archivo seleccionado
            const fileInput = document.getElementById('newLogo');
            const fileNameSpan = document.getElementById('fileName');
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    fileNameSpan.textContent = this.files[0].name;
                    
                    // Vista previa de la imagen
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Crear vista previa
                        const preview = document.createElement('div');
                        preview.innerHTML = `
                            <div style="margin-top: 15px; border: 2px solid #28a745; border-radius: 8px; padding: 10px;">
                                <p style="color: #28a745; font-weight: bold; margin-bottom: 10px;">
                                    <i class="fas fa-eye"></i> Vista previa del nuevo logo:
                                </p>
                                <img src="${e.target.result}" alt="Vista previa" 
                                     style="max-width: 100%; max-height: 100px; object-fit: contain;">
                            </div>
                        `;
                        
                        // Eliminar vista previa anterior si existe
                        const oldPreview = document.querySelector('.logo-preview-new');
                        if (oldPreview) {
                            oldPreview.remove();
                        }
                        
                        preview.className = 'logo-preview-new';
                        document.querySelector('.logo-form').appendChild(preview);
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    fileNameSpan.textContent = 'Haga clic para seleccionar un archivo';
                }
            });
            
            // Validar tamaño del archivo
            fileInput.addEventListener('change', function() {
                if (this.files[0] && this.files[0].size > 2 * 1024 * 1024) {
                    showError('El archivo es demasiado grande. El tamaño máximo es 2MB.');
                    this.value = '';
                    fileNameSpan.textContent = 'Haga clic para seleccionar un archivo';
                }
            });
        });
        
        function uploadLogo() {
            const fileInput = document.getElementById('newLogo');
            const altText = document.getElementById('logoAltText').value;
            const logoLink = document.getElementById('logoLink').value;
            
            if (!fileInput.files[0] && !altText && !logoLink) {
                showError('No hay cambios para guardar.');
                return;
            }
            
            // Simulación de carga (en un caso real, aquí iría una petición AJAX)
            showSuccess('Guardando logo...');
            
            setTimeout(() => {
                // Actualizar vista previa
                if (fileInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('currentLogo').src = e.target.result;
                        document.querySelector('.footer-logo').src = e.target.result;
                        
                        // Actualizar todos los logos en el sistema
                        updateAllLogos(e.target.result);
                    };
                    reader.readAsDataURL(fileInput.files[0]);
                }
                
                showSuccess('Logo actualizado correctamente.');
            }, 1500);
        }
        
        function restoreDefaultLogo() {
            if (confirm('¿Está seguro de restaurar el logo predeterminado? Se perderán los cambios no guardados.')) {
                const defaultLogo = '../../imagenes/logo-default.png';
                
                showSuccess('Restaurando logo predeterminado...');
                
                setTimeout(() => {
                    document.getElementById('currentLogo').src = defaultLogo;
                    document.querySelector('.footer-logo').src = defaultLogo;
                    document.getElementById('logoAltText').value = 'Logo Gobernación del Meta';
                    document.getElementById('logoLink').value = 'https://www.meta.gov.co';
                    document.getElementById('newLogo').value = '';
                    document.getElementById('fileName').textContent = 'Haga clic para seleccionar un archivo';
                    
                    // Eliminar vista previa
                    const preview = document.querySelector('.logo-preview-new');
                    if (preview) {
                        preview.remove();
                    }
                    
                    showSuccess('Logo predeterminado restaurado correctamente.');
                }, 1000);
            }
        }
        
        function saveParameters() {
            // Obtener valores del formulario
            const version = document.getElementById('version').value;
            const tipoLicencia = document.getElementById('tipoLicencia').value;
            const validaHasta = document.getElementById('validaHasta').value;
            const desarrolladoPor = document.getElementById('desarrolladoPor').value;
            const direccion = document.getElementById('direccion').value;
            const contacto = document.getElementById('contacto').value;
            const telefono = document.getElementById('telefono').value;
            
            // Validaciones básicas
            if (!version || !desarrolladoPor || !contacto || !telefono) {
                showError('Por favor complete todos los campos requeridos.');
                return;
            }
            
            // Validar fecha
            if (!validaHasta) {
                showError('Por favor seleccione una fecha de validez.');
                return;
            }
            
            // Simulación de guardado
            showSuccess('Guardando configuración del sistema...');
            
            setTimeout(() => {
                // Aquí normalmente se enviaría una petición AJAX al servidor
                console.log('Datos a guardar:', {
                    version,
                    tipoLicencia,
                    validaHasta,
                    desarrolladoPor,
                    direccion,
                    contacto,
                    telefono
                });
                
                showSuccess('Configuración guardada correctamente.');
                
                // Actualizar información visible en la página si es necesario
                updateDisplayedInfo();
            }, 1500);
        }
        
        function resetParameters() {
            if (confirm('¿Está seguro de restaurar todos los valores predeterminados? Se perderán todos los cambios no guardados.')) {
                showSuccess('Restaurando valores predeterminados...');
                
                setTimeout(() => {
                    // Restaurar valores predeterminados
                    document.getElementById('version').value = '1.0.0';
                    document.getElementById('tipoLicencia').value = 'evaluacion';
                    document.getElementById('validaHasta').value = '2026-03-31';
                    document.getElementById('desarrolladoPor').value = 'SisgonTech';
                    document.getElementById('direccion').value = 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta';
                    document.getElementById('contacto').value = 'gobernaciondelmeta@meta.gov.co';
                    document.getElementById('telefono').value = '(57 -608) 6 818503';
                    
                    showSuccess('Valores predeterminados restaurados correctamente.');
                }, 1000);
            }
        }
        
        function updateAllLogos(newLogoUrl) {
            // Actualizar todos los logos en la página
            const allLogos = document.querySelectorAll('img[src*="logo"]');
            allLogos.forEach(logo => {
                if (logo.id !== 'currentLogo' && !logo.src.includes('placeholder')) {
                    logo.src = newLogoUrl;
                }
            });
        }
        
        function updateDisplayedInfo() {
            // Actualizar información mostrada en la página si es necesario
            const desarrolladoPor = document.getElementById('desarrolladoPor').value;
            const contacto = document.getElementById('contacto').value;
            const telefono = document.getElementById('telefono').value;
            
            // Actualizar footer si es necesario
            const footerContact = document.querySelectorAll('.contact-info div span');
            if (footerContact.length >= 3) {
                footerContact[1].textContent = contacto; // Correo
                footerContact[0].textContent = telefono; // Teléfono principal
            }
            
            // Actualizar info del desarrollador
            const devName = document.querySelector('.developer-name');
            if (devName) {
                devName.textContent = desarrolladoPor;
            }
        }
        
        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
        
        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>