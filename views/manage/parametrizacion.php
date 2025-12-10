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
    } else if ($_SESSION['tipo_usuario'] === 'usuario') {
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
    <style>
        :root {
            --primary-color: #004a8d;
            --secondary-color: #003366;
            --accent-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-radius: 12px;
            --shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .app-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            min-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header con indicador de admin */
        .app-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 40px;
            position: relative;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .department-info h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .department-info h2 {
            font-size: 20px;
            font-weight: 400;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-profile {
            text-align: right;
        }
        
        .welcome-user {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .welcome-user i {
            color: #ffd700;
        }
        
        .user-role {
            font-size: 14px;
            opacity: 0.8;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        /* Contenido principal */
        .app-main {
            flex: 1;
            padding: 30px 40px;
        }
        
        .page-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .page-title h1 {
            font-size: 28px;
            color: var(--primary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-button {
            background-color: var(--gray-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .back-button:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }
        
        /* Panel de configuración */
        .config-panel {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .config-panel h2 {
            color: var(--secondary-color);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Formulario de logo */
        .logo-config {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        
        .current-logo {
            flex: 1;
            min-width: 300px;
        }
        
        .logo-preview {
            width: 100%;
            max-width: 300px;
            height: 120px;
            background: white;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .logo-form {
            flex: 2;
            min-width: 300px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 74, 141, 0.25);
        }
        
        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: block;
            padding: 12px 15px;
            background: #e9ecef;
            border: 1px dashed #adb5bd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .file-input-label:hover {
            background: #dee2e6;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background-color: var(--gray-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Formulario de parametrización */
        .param-form-section {
            margin-top: 40px;
        }
        
        .param-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 768px) {
            .param-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .param-group {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .param-group h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            font-size: 18px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-col {
            flex: 1;
        }
        
        .form-col label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 14px;
        }
        
        /* Footer */
        .app-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            padding: 30px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .footer-left {
            display: flex;
            align-items: center;
            gap: 25px;
            flex: 1;
        }
        
        .footer-logo-container {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .footer-logo {
            height: 80px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        
        .developer-info {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .developer-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .footer-right {
            text-align: right;
            flex: 1;
        }
        
        .contact-info {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .contact-info div {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .copyright {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 10px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .logo-config {
                flex-direction: column;
            }
            
            .current-logo, .logo-form {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .app-header, .app-main, .app-footer {
                padding: 20px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .department-info h2 {
                flex-direction: column;
                gap: 5px;
            }
            
            .user-profile {
                text-align: center;
            }
            
            .page-title {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .app-footer {
                flex-direction: column;
                gap: 25px;
                text-align: center;
            }
            
            .footer-left, .footer-right {
                width: 100%;
                justify-content: center;
            }
            
            .footer-left {
                flex-direction: column;
                gap: 20px;
            }
            
            .footer-logo-container {
                flex-direction: column;
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .app-header, .app-main, .app-footer {
                padding: 15px;
            }
            
            .config-panel {
                padding: 20px;
            }
            
            .logo-preview {
                height: 100px;
            }
        }
        
        /* Mensajes de alerta */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            animation: slideDown 0.3s ease;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
        }
        
        .alert i {
            margin-right: 10px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* --- Ajustes SOLO para celulares --- */
@media (max-width: 576px) {

    /* Centrar contenedor completo */
    .app-container {
        margin: 0 auto;
    }

    /* Centrar textos principales */
    .department-info h1,
    .department-info h2,
    .page-title h1 {
        text-align: center;
    }

    /* Centrar logo actual */
    .logo-preview {
        margin: 0 auto;
    }

    /* Centrar botones */
    .form-actions {
        justify-content: center;
        text-align: center;
    }

    /* Asegurar que todo se vea fluido en pantallas pequeñas */
    .logo-config,
    .current-logo,
    .logo-form {
        width: 100%;
        text-align: center;
    }

    /* Ajuste extra para el header */
    .header-content {
        flex-direction: column;
        text-align: center;
    }
}

    </style>
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
                        <strong style="font-size: 25px; font-weight: bold;">
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

                                <!--<select id="tipoLicencia" class="form-control">
                                    <option value="evaluacion" selected>Evaluación</option>
                                    <option value="basica">Básica</option>
                                    <option value="estandar">Estándar</option>
                                    <option value="premium">Premium</option>
                                    <option value="enterprise">Enterprise</option>
                                </select>-->
                            </div>
                            
                            <div class="form-group">
                                <label for="validaHasta">Válida hasta:</label>
                                <input type="date" id="validaHasta" class="form-control" 
                                       value="2026-03-31">
                            </div>
                           <!-- días restantes-->
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