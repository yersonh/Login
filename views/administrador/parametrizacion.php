<?php
session_start();

require_once __DIR__ . '/../../helpers/config_helper.php';
// Solo administradores
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SESSION['tipo_usuario'] !== 'administrador') {
    if ($_SESSION['tipo_usuario'] === 'asistente') {
        header("Location: ../menuAsistente.php");
    } else if ($_SESSION['tipo_usuario'] === 'contratista') {
        header("Location: ../menu.php");
    } else {
        header("Location: ../../index.php");
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
        'ruta_logo' => '../../imagenes/gobernacion.png',
        'entidad' => 'Logo Gobernación del Meta',
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

// Obtener año actual
$anio = date('Y');
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
    <style>
        /* Estilos para el botón de eliminar */
        .btn-action.btn-delete {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: white !important;
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 4px;
        }

        .btn-action.btn-delete:hover {
            background-color: #c82333 !important;
            border-color: #bd2130 !important;
            transform: translateY(-1px);
        }

        /* Modal de confirmación de eliminación */
        #confirmEliminarModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        #confirmEliminarModal .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-warning i {
            color: #ffc107;
        }

        .text-danger {
            color: #dc3545 !important;
            font-weight: 600;
        }

        /* Animación para remover fila */
        .fila-eliminando {
            opacity: 0;
            transform: translateX(-20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
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
        <!-- Botón modificado para abrir modal -->
        <button class="back-button" id="btnVolverMenu">
            <i class="fas fa-arrow-left"></i> Volver al Menú
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
                         src="<?php echo htmlspecialchars($configuracion['ruta_logo'] ?? '../../imagenes/gobernacion.png'); ?>" 
                         alt="<?php echo htmlspecialchars($configuracion['entidad'] ?? 'Logo actual'); ?>" 
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
                        <label for="logoAltText">Nombre entidad:</label>
                        <input type="text" id="logoAltText" class="form-control" 
                               value="<?php echo htmlspecialchars($configuracion['entidad'] ?? 'Logo Gobernación del Meta'); ?>" 
                               maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="logoNit">NIT:</label>
                        <input type="text" id="logoNit" class="form-control" 
                               value="<?php echo htmlspecialchars($configuracion['nit'] ?? '892000148-8'); ?>" 
                               maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="logoLink">Website:</label>
                        <input type="url" id="logoLink" class="form-control" 
                               value="<?php echo htmlspecialchars($configuracion['enlace_web'] ?? 'https://www.meta.gov.co'); ?>" 
                               placeholder="https://ejemplo.com">
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="saveLogoBtn">
                            <i class="fas fa-save"></i> Guardar Cambios del Logo
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
            </div>
        </form>
    </div>
    
    <!-- ========== NUEVAS SECCIONES CRUD ========== -->
    
    <!-- Panel de Municipios -->
    <div class="config-panel">
        <h2><i class="fas fa-city"></i> Gestión de Municipios</h2>
        
        <div class="crud-section">
            <div class="crud-actions">
                <button type="button" class="btn btn-primary" id="addMunicipioBtn">
                    <i class="fas fa-plus"></i> Agregar Municipio
                </button>
                <div class="search-box">
                    <input type="text" id="searchMunicipio" class="form-control" 
                           placeholder="Buscar municipio...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sitios</th>
                            <th>Código DANE</th>
                            <th>Departamento</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="municipiosTable">
                        <!-- Datos cargados por JavaScript -->
                        <tr class="loading-row">
                            <td colspan="5">Cargando municipios...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Panel de Áreas -->
    <div class="config-panel">
        <h2><i class="fas fa-sitemap"></i> Gestión de Áreas</h2>
        
        <div class="crud-section">
            <div class="crud-actions">
                <button type="button" class="btn btn-primary" id="addAreaBtn">
                    <i class="fas fa-plus"></i> Agregar Área
                </button>
                <div class="search-box">
                    <input type="text" id="searchArea" class="form-control" 
                           placeholder="Buscar área...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Área</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="areasTable">
                        <!-- Datos cargados por JavaScript -->
                        <tr class="loading-row">
                            <td colspan="5">Cargando áreas...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Panel de Tipos de Vinculación -->
    <div class="config-panel">
        <h2><i class="fas fa-user-tie"></i> Gestión de Tipos de Vinculación</h2>
        
        <div class="crud-section">
            <div class="crud-actions">
                <button type="button" class="btn btn-primary" id="addVinculacionBtn">
                    <i class="fas fa-plus"></i> Agregar Tipo
                </button>
                <div class="search-box">
                    <input type="text" id="searchVinculacion" class="form-control" 
                           placeholder="Buscar tipo...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tipo de Vinculación</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="vinculacionesTable">
                        <!-- Datos cargados por JavaScript -->
                        <tr class="loading-row">
                            <td colspan="5">Cargando tipos de vinculación...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal para formularios CRUD -->
    <div id="crudModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"></h3>
                <button type="button" class="modal-close" onclick="closeCrudModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="crudForm">
                    <input type="hidden" id="recordId">
                    <input type="hidden" id="recordType">
                    
                    <div id="municipioFields" class="form-fields">
                        <div class="form-group">
                            <label for="nombreMunicipio">Sitio:</label>
                            <input type="text" id="nombreMunicipio" class="form-control" 
                                   placeholder="Ej: Villavicencio" maxlength="100" required>
                        </div>
                        <div class="form-group">
                            <label for="codigoDane">Código DANE:</label>
                            <input type="text" id="codigoDane" class="form-control" 
                                   placeholder="Ej: 50001" maxlength="10" required>
                        </div>

                        <div class="form-group">
                            <label for="departamentoMunicipio">Departamento:</label>
                            <input type="text" id="departamentoMunicipio" class="form-control" 
                                value="Meta">
                        </div>
                    </div>
                    
                    <div id="areaFields" class="form-fields">
                        <div class="form-group">
                            <label for="nombreArea">Nombre del Área:</label>
                            <input type="text" id="nombreArea" class="form-control" 
                                   placeholder="Ej: Secretaría de Salud" maxlength="100" required>
                        </div>
                        <div class="form-group">
                            <label for="codigoArea">Código:</label>
                            <input type="text" id="codigoArea" class="form-control" 
                                   placeholder="Ej: SS-001" maxlength="20" required>
                        </div>
                        <div class="form-group">
                            <label for="descripcionArea">Descripción:</label>
                            <textarea id="descripcionArea" class="form-control" rows="3" 
                                      placeholder="Descripción del área..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="estadoArea">Estado:</label>
                            <select id="estadoArea" class="form-control" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="vinculacionFields" class="form-fields">
                        <div class="form-group">
                            <label for="nombreVinculacion">Tipo de Vinculación:</label>
                            <input type="text" id="nombreVinculacion" class="form-control" 
                                   placeholder="Ej: Contratista" maxlength="100" required>
                        </div>
                        <div class="form-group">
                            <label for="codigoVinculacion">Código:</label>
                            <input type="text" id="codigoVinculacion" class="form-control" 
                                   placeholder="Ej: CON-001" maxlength="20" required>
                        </div>
                        <div class="form-group">
                            <label for="descripcionVinculacion">Descripción:</label>
                            <textarea id="descripcionVinculacion" class="form-control" rows="3" 
                                      placeholder="Descripción del tipo de vinculación..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="estadoVinculacion">Estado:</label>
                            <select id="estadoVinculacion" class="form-control" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCrudModal()">
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="saveCrudBtn">
                    Guardar
                </button>
            </div>
        </div>
    </div>
    
</main>

<!-- MODAL DE RESUMEN AL VOLVER - ACTUALIZADO PARA TIEMPO REAL -->
<div id="resumenModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle"></i> Resumen de Configuración</h3>
            <button type="button" class="modal-close" onclick="closeResumenModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="resumen-container">
                <!-- Logo y Encabezado -->
                <div class="resumen-header">
                    <img id="modalLogo" 
                         src="<?php echo htmlspecialchars($configuracion['ruta_logo'] ?? '../../imagenes/gobernacion.png'); ?>" 
                         alt="Logo"
                         onerror="this.onerror=null; this.src='../../imagenes/gobernacion.png'">
                </div>
                
                <!-- Datos Parametrizados en dos renglones -->
                <div class="resumen-datos">
                    <div class="resumen-linea">
                        <p id="modalLinea1"></p>
                    </div>
                    
                    <div class="resumen-linea">
                        <p id="modalLinea2"></p>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="resumen-extra">
                        <div class="resumen-item">
                            <span class="resumen-label">Válido hasta:</span>
                            <span class="resumen-valor" id="modalValidaHasta"></span>
                        </div>
                        
                        <div class="resumen-item">
                            <span class="resumen-label">Días restantes:</span>
                            <!-- Mantenemos la estructura original pero con id para JS -->
                            <span class="resumen-valor" id="modalDiasRestantes">
                                <?php echo htmlspecialchars($diasRestantes); ?>
                            </span>
                        </div>
                        
                        <div class="resumen-item">
                            <span class="resumen-label">Estado:</span>
                            <span class="resumen-valor text-success" id="modalEstado">
                                <i class="fas fa-check-circle"></i> Configuración Guardada
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Mensaje del usuario -->
                <div class="resumen-usuario">
                    <p>
                        <span>Señor(a) </span>
                        <strong id="modalUsuario"><?php echo htmlspecialchars($nombreCompleto); ?></strong>
                        <span>, la parametrización del sistema ha sido completada exitosamente.</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeResumenModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="confirmVolverBtn">
                <i class="fas fa-check"></i> Confirmar y Volver al Menú
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN -->
<div id="confirmationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-question-circle"></i> Confirmar Cambios</h3>
            <button type="button" class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <span>Señor(ar) </span>
            <strong style="font-size: 18px; font-weight: bold;">
                    <?php echo htmlspecialchars($nombreCompleto); ?>
            </strong>
            <span>, usted está a punto de realizar los siguientes cambios en la parametrización del sistema:</span>
            <p id="modalMessage">¿Está seguro de guardar los cambios?</p>
            <div class="modal-details" id="modalDetails" style="display: none;">
                <h4>Cambios a realizar:</h4>
                <ul id="changesList"></ul>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="confirmSaveBtn">
                <i class="fas fa-check"></i> Sí, Guardar Cambios
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ACTIVAR/DESACTIVAR MUNICIPIOS -->
<div id="confirmEstadoModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-question-circle"></i> Confirmar Cambio de Estado</h3>
            <button type="button" class="modal-close" onclick="closeEstadoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <span>Señor(ar) </span>
            <strong style="font-size: 18px; font-weight: bold;" id="usuarioNombre">
                <?php echo htmlspecialchars($nombreCompleto); ?>
            </strong>
            <span>, </span>
            <p id="estadoMensaje">¿Está seguro de cambiar el estado de este municipio?</p>
            <div class="modal-details">
                <h4>Detalles del municipio:</h4>
                <ul id="municipioDetails"></ul>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEstadoModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="confirmEstadoBtn">
                <i class="fas fa-check"></i> Sí, Confirmar
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ACTIVAR/DESACTIVAR ÁREAS -->
<div id="confirmEstadoAreaModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-question-circle"></i> Confirmar Cambio de Estado</h3>
            <button type="button" class="modal-close" onclick="closeEstadoAreaModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <span>Señor(ar) </span>
            <strong style="font-size: 18px; font-weight: bold;" id="usuarioNombre">
                <?php echo htmlspecialchars($nombreCompleto); ?>
            </strong>
            <span>, </span>
            <p id="estadoAreaMensaje">¿Está seguro de cambiar el estado de esta área?</p>
            <div class="modal-details">
                <h4>Detalles del área:</h4>
                <ul id="areaDetails"></ul>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEstadoAreaModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="confirmEstadoAreaBtn">
                <i class="fas fa-check"></i> Sí, Confirmar
            </button>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN PARA ACTIVAR/DESACTIVAR Tipo vinculacion -->
<div id="confirmEstadoVinculacionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exchange-alt"></i> Confirmar Cambio de Estado</h3>
            <button type="button" class="modal-close" onclick="closeEstadoVinculacionModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <span>Señor(a) </span>
            <strong style="font-size: 18px; font-weight: bold;" id="usuarioNombreVinculacion">
                <?php echo htmlspecialchars($nombreCompleto); ?>
            </strong>
            <span>, </span>
            <p id="estadoVinculacionMensaje">¿Está seguro de cambiar el estado de este tipo de vinculación?</p>
            <div class="modal-details">
                <h4>Detalles del tipo de vinculación:</h4>
                <ul id="vinculacionDetails">
                    <li><strong>Tipo:</strong> <span id="detailNombre"></span></li>
                    <li><strong>Código:</strong> <span id="detailCodigo"></span></li>
                    <li><strong>Descripción:</strong> <span id="detailDescripcion"></span></li>
                    <li><strong>Estado Actual:</strong> <span id="detailEstadoActual" class="estado-badge"></span></li>
                    <li><strong>Nuevo Estado:</strong> <span id="detailNuevoEstado" class="estado-badge"></span></li>
                </ul>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEstadoVinculacionModal()">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button type="button" class="btn btn-primary" id="confirmEstadoVinculacionBtn">
                <i class="fas fa-check"></i> Sí, Confirmar
            </button>
        </div>
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
    </div>
    
    <!-- Incluir el archivo JavaScript externo -->
    <script src="../../javascript/parametrizar.js"></script>
    
    <!-- Script para el modal de resumen en tiempo real -->
    <script>
    // Función para calcular días restantes en tiempo real
    function calcularDiasRestantes() {
    const validaHastaInput = document.getElementById('validaHasta');
    const diasRestantesInput = document.getElementById('diasRestantes');
    
    if (!validaHastaInput || !diasRestantesInput) return;
    
    const fechaValida = new Date(validaHastaInput.value);
    const hoy = new Date();
    
    // Resetear la hora para comparar solo fechas
    hoy.setHours(0, 0, 0, 0);
    fechaValida.setHours(0, 0, 0, 0);
    
    const diferenciaMs = fechaValida - hoy;
    const dias = Math.ceil(diferenciaMs / (1000 * 60 * 60 * 24));
    
    if (dias < 0) {
        diasRestantesInput.value = '0 días (Expirada)';
    } else if (dias === 0) {
        diasRestantesInput.value = '0 días';
    } else {
        diasRestantesInput.value = `${dias} días`;
    }
}

    // Función para actualizar el modal con datos en tiempo real
    function actualizarModalResumen() {
        // Obtener los valores actuales de los campos del formulario
        const version = document.getElementById('version')?.value || '1.0.0';
        const tipoLicencia = document.getElementById('tipoLicencia')?.value || 'Evaluación';
        const desarrolladoPor = document.getElementById('desarrolladoPor')?.value || 'SisgonTech';
        const validaHasta = document.getElementById('validaHasta')?.value || '2026-03-31';
        const direccion = document.getElementById('direccion')?.value || 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta';
        const contacto = document.getElementById('contacto')?.value || 'gobernaciondelmeta@meta.gov.co';
        const telefono = document.getElementById('telefono')?.value || '(57 -608) 6 818503';
        const diasRestantes = document.getElementById('diasRestantes')?.value || '90 días';
        const logoUrl = document.getElementById('currentLogo')?.src || '../../imagenes/gobernacion.png';
        const entidad = document.getElementById('logoAltText')?.value || 'Logo Gobernación del Meta';
        
        const anio = new Date().getFullYear();
        
        // Calcular fecha formateada
        let fechaFormateada = '31/03/2026';
        if (validaHasta) {
            const fecha = new Date(validaHasta);
            fechaFormateada = fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
        
        // Actualizar logo
        const modalLogo = document.getElementById('modalLogo');
        if (modalLogo) {
            modalLogo.src = logoUrl;
            modalLogo.alt = entidad;
        }
        
        // Actualizar primera línea
        const modalLinea1 = document.getElementById('modalLinea1');
        if (modalLinea1) {
            modalLinea1.innerHTML = `© ${anio} ${version}® desarrollado por <strong>${desarrolladoPor}</strong> - Tipo de Licencia: ${tipoLicencia}`;
        }
        
        // Actualizar segunda línea
        const modalLinea2 = document.getElementById('modalLinea2');
        if (modalLinea2) {
            modalLinea2.innerHTML = `${direccion} - Asesores e-Governance Solutions para Entidades Públicas ${anio}® By: Ing. Rubén Darío González García ${telefono}. Contacto: <strong>${contacto}</strong>`;
        }
        
        // Actualizar información adicional
        const modalValidaHasta = document.getElementById('modalValidaHasta');
        if (modalValidaHasta) {
            modalValidaHasta.textContent = fechaFormateada;
        }

const modalDiasRestantes = document.getElementById('modalDiasRestantes');
if (modalDiasRestantes) {
    // Actualizar el texto
    modalDiasRestantes.textContent = diasRestantes;
    
    // Remover todas las clases de color primero
    modalDiasRestantes.classList.remove('text-danger', 'text-success', 'text-warning');
    
    // Extraer solo el número de días (eliminar " días" del texto)
    const diasTexto = diasRestantes.toString();
    
    // Verificar si contiene "Expirada" o si es 0 días
    if (diasTexto.includes('Expirada')) {
        modalDiasRestantes.classList.add('text-danger');
    } else {
        // Extraer el número de días
        const match = diasTexto.match(/(\d+)/);
        if (match) {
            const numDias = parseInt(match[1]);
            if (numDias <= 0) {
                modalDiasRestantes.classList.add('text-danger');
            } else if (numDias <= 30) {
                // Amarillo para menos de 30 días
                modalDiasRestantes.classList.add('text-warning');
            } else {
                // Verde para más de 30 días
                modalDiasRestantes.classList.add('text-success');
            }
        } else {
            // Si no puede extraer número, usar verde por defecto
            modalDiasRestantes.classList.add('text-success');
        }
    }
}
}

    // Modificar la función para abrir el modal para que primero actualice los datos
    function openResumenModal() {
        // 1. Primero actualizar los datos del modal
        actualizarModalResumen();
        
        // 2. Luego mostrar el modal
        document.getElementById('resumenModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    // Función para cerrar el modal de resumen
    function closeResumenModal() {
        document.getElementById('resumenModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Evento para el botón "Volver al Menú"
    document.getElementById('btnVolverMenu').addEventListener('click', openResumenModal);

    // Evento para el botón "Confirmar y Volver al Menú"
    document.getElementById('confirmVolverBtn').addEventListener('click', function() {
        // Cerrar el modal
        closeResumenModal();
        // Redirigir al menú del administrador
        window.location.href = '../menuAdministrador.php';
    });

    // Cerrar modal si se hace clic fuera del contenido
    document.getElementById('resumenModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeResumenModal();
        }
    });

    // Cerrar modal con la tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('resumenModal').style.display === 'block') {
            closeResumenModal();
        }
    });

    // Ejecutar cuando cambie la fecha para actualizar días restantes
    document.getElementById('validaHasta')?.addEventListener('change', calcularDiasRestantes);

    // Ejecutar al cargar la página
    window.addEventListener('load', calcularDiasRestantes);

    // Actualizar logo preview y modal cuando se seleccione un nuevo logo
    document.getElementById('newLogo')?.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                // Actualizar preview
                document.getElementById('currentLogo').src = event.target.result;
                // El logo en el modal se actualizará cuando se abra el modal
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });

    const inputsConfiguracion = ['version', 'tipoLicencia', 'validaHasta', 'desarrolladoPor', 'direccion', 'contacto', 'telefono'];
    inputsConfiguracion.forEach(inputId => {
        document.getElementById(inputId)?.addEventListener('input', function() {
            // Recalcular días restantes si cambia la fecha
            if (inputId === 'validaHasta') {
                calcularDiasRestantes();
            }
        });
    });
    </script>

    <!-- SCRIPT PARA ELIMINACIÓN DE REGISTROS (PAPELERA) -->
    <script>
    // Modal de confirmación de eliminación
    const modalEliminarHTML = `
        <div id="confirmEliminarModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3><i class="fas fa-trash-alt"></i> <span id="eliminarTitulo"></span></h3>
                    <button type="button" class="modal-close" onclick="cerrarModalEliminar()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                    </div>
                    <p id="eliminarMensaje"></p>
                    <div class="modal-details">
                        <ul id="eliminarDetalles"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="cerrarModalEliminar()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" onclick="confirmarEliminacion()">
                        <i class="fas fa-trash-alt"></i> Sí, Eliminar
                    </button>
                </div>
            </div>
        </div>
    `;

    // Variables para la eliminación
    let registroEliminarId = null;
    let registroEliminarTipo = null;
    let registroEliminarNombre = null;
    let registroEliminarCodigo = null;

    // Insertar modal al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        document.body.insertAdjacentHTML('beforeend', modalEliminarHTML);
        configurarModalEliminar();
        
        // Esperar a que se carguen las tablas para agregar botones de eliminar
        setTimeout(() => {
            observarCambiosTablas();
        }, 1500);
    });

    // Configurar eventos del modal de eliminación
    function configurarModalEliminar() {
        const modal = document.getElementById('confirmEliminarModal');
        if (!modal) return;

        // Cerrar al hacer clic fuera
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalEliminar();
            }
        });

        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                cerrarModalEliminar();
            }
        });
    }

    // Mostrar modal de confirmación de eliminación
    function mostrarModalEliminar(id, tipo, nombre, codigo) {
        registroEliminarId = id;
        registroEliminarTipo = tipo;
        registroEliminarNombre = nombre;
        registroEliminarCodigo = codigo;

        // Configurar textos según el tipo
        let titulo, mensaje, detallesHTML;
        
        switch(tipo) {
            case 'municipio':
                titulo = 'Eliminar Municipio Permanentemente';
                mensaje = `¿Está seguro de que desea eliminar PERMANENTEMENTE el municipio "${nombre}"?`;
                detallesHTML = `
                    <li><strong>Municipio:</strong> ${nombre}</li>
                    <li><strong>Código DANE:</strong> ${codigo || '--'}</li>
                    <li><strong>Acción:</strong> <span class="text-danger">ELIMINACIÓN PERMANENTE DE LA BASE DE DATOS</span></li>
                    <li><strong>Advertencia:</strong> Esta acción no se puede deshacer y el registro será eliminado completamente.</li>
                `;
                break;
            case 'area':
                titulo = 'Eliminar Área Permanentemente';
                mensaje = `¿Está seguro de que desea eliminar PERMANENTEMENTE el área "${nombre}"?`;
                detallesHTML = `
                    <li><strong>Área:</strong> ${nombre}</li>
                    <li><strong>Código:</strong> ${codigo || '--'}</li>
                    <li><strong>Acción:</strong> <span class="text-danger">ELIMINACIÓN PERMANENTE DE LA BASE DE DATOS</span></li>
                    <li><strong>Advertencia:</strong> Esta acción no se puede deshacer y el registro será eliminado completamente.</li>
                `;
                break;
            case 'vinculacion':
                titulo = 'Eliminar Tipo de Vinculación Permanentemente';
                mensaje = `¿Está seguro de que desea eliminar PERMANENTEMENTE el tipo de vinculación "${nombre}"?`;
                detallesHTML = `
                    <li><strong>Tipo:</strong> ${nombre}</li>
                    <li><strong>Código:</strong> ${codigo || '--'}</li>
                    <li><strong>Acción:</strong> <span class="text-danger">ELIMINACIÓN PERMANENTE DE LA BASE DE DATOS</span></li>
                    <li><strong>Advertencia:</strong> Esta acción no se puede deshacer y el registro será eliminado completamente.</li>
                `;
                break;
        }

        // Actualizar modal
        document.getElementById('eliminarTitulo').textContent = titulo;
        document.getElementById('eliminarMensaje').textContent = mensaje;
        document.getElementById('eliminarDetalles').innerHTML = detallesHTML;

        // Mostrar modal
        document.getElementById('confirmEliminarModal').style.display = 'flex';
    }

    // Remover fila con animación
    function removerFila(id, tipo) {
        let tablaId;
        switch(tipo) {
            case 'municipio': tablaId = 'municipiosTable'; break;
            case 'area': tablaId = 'areasTable'; break;
            case 'vinculacion': tablaId = 'vinculacionesTable'; break;
        }

        const tablaBody = document.getElementById(tablaId);
        if (!tablaBody) return;

        const filas = tablaBody.querySelectorAll('tr');
        filas.forEach(fila => {
            if (!fila.classList.contains('loading-row') && 
                !fila.classList.contains('empty-row') && 
                !fila.classList.contains('error-row')) {
                
                // Buscar el botón que contiene este ID
                const btnEliminar = fila.querySelector('.btn-delete');
                if (btnEliminar && btnEliminar.dataset.id == id) {
                    // Aplicar animación
                    fila.classList.add('fila-eliminando');
                    
                    // Remover después de la animación
                    setTimeout(() => {
                        fila.remove();
                        
                        // Verificar si la tabla quedó vacía
                        if (tablaBody.querySelectorAll('tr').length === 0) {
                            const emptyRow = document.createElement('tr');
                            emptyRow.className = 'empty-row';
                            emptyRow.innerHTML = `<td colspan="5">No hay registros disponibles</td>`;
                            tablaBody.appendChild(emptyRow);
                        }
                    }, 300);
                }
            }
        });
    }



    // Obtener ID de una fila
    function obtenerIdDeFila(fila, tipo) {
        // Intentar desde botón de editar
        const btnEditar = fila.querySelector('.btn-edit');
        if (btnEditar && btnEditar.onclick) {
            const onclickStr = btnEditar.onclick.toString();
            const match = onclickStr.match(/\d+/);
            if (match) return match[0];
        }

        // Intentar desde botón de estado
        const btnEstado = fila.querySelector('.btn-activate, .btn-deactivate');
        if (btnEstado && btnEstado.onclick) {
            const onclickStr = btnEstado.onclick.toString();
            const match = onclickStr.match(/\d+/);
            if (match) return match[0];
        }

        return null;
    }

    // Observar cambios en las tablas
    function observarCambiosTablas() {
        const tablas = ['municipiosTable', 'areasTable', 'vinculacionesTable'];
        
        tablas.forEach(tablaId => {
            const tablaBody = document.getElementById(tablaId);
            if (tablaBody) {
                const observer = new MutationObserver(() => {
                    // Determinar tipo
                    let tipo = '';
                    if (tablaId.includes('municipio')) tipo = 'municipio';
                    else if (tablaId.includes('area')) tipo = 'area';
                    else if (tablaId.includes('vinculacion')) tipo = 'vinculacion';
                    
                    if (tipo) {
                        agregarBotonEliminarATabla(tablaId, tipo);
                    }
                });
                
                observer.observe(tablaBody, { childList: true, subtree: true });
            }
        });
    }

    // Mostrar alerta
    function mostrarAlerta(tipo, mensaje) {
        let alertaDiv = document.getElementById(tipo === 'success' ? 'successAlert' : 'errorAlert');
        if (alertaDiv) {
            alertaDiv.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${mensaje}`;
            alertaDiv.style.display = 'block';
            
            setTimeout(() => {
                alertaDiv.style.display = 'none';
            }, tipo === 'success' ? 4000 : 5000);
        }
    }

    // Exponer funciones al scope global
    window.mostrarModalEliminar = mostrarModalEliminar;
    window.cerrarModalEliminar = cerrarModalEliminar;
    window.confirmarEliminacion = confirmarEliminacion;
    </script>
</body>
</html>