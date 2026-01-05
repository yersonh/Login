<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ContratistaModel.php';
require_once __DIR__ . '/../../models/AreaModel.php';
require_once __DIR__ . '/../../models/TipoVinculacionModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    $redireccion = match($_SESSION['tipo_usuario'] ?? '') {
        'usuario' => '../menu.php',
        default => '../../index.php'
    };
    header("Location: $redireccion");
    exit();
}

$nombreUsuario = htmlspecialchars($_SESSION['nombres'] ?? '');
$apellidoUsuario = htmlspecialchars($_SESSION['apellidos'] ?? '');
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
$nombreCompleto = empty($nombreCompleto) ? 'Usuario del Sistema' : $nombreCompleto;

// Obtener datos desde la base de datos usando los modelos
try {
    $database = new Database();
    $db = $database->conectar();
    
    // Obtener contratistas
    $contratistaModel = new ContratistaModel($db);
    $contratistas = $contratistaModel->obtenerTodosContratistas();
    
    // Obtener áreas desde el modelo AreaModel
    $areaModel = new AreaModel();
    $todasLasAreas = $areaModel->obtenerAreasActivas(); // Solo áreas activas
    
    // Obtener tipos de vinculación desde el modelo TipoVinculacionModel
    $tipoVinculacionModel = new TipoVinculacionModel();
    $todosLosTiposVinculacion = $tipoVinculacionModel->obtenerTiposActivos(); // Solo tipos activos
    
    // Obtener municipios desde el modelo MunicipioModel
    $municipioModel = new MunicipioModel();
    $todosLosMunicipios = $municipioModel->obtenerMunicipiosActivos(); // Solo municipios activos
    
    // Preparar arrays para los filtros
    $areasUnicas = [];
    foreach ($todasLasAreas as $area) {
        if (isset($area['nombre']) && !empty($area['nombre'])) {
            $areasUnicas[] = $area['nombre'];
        }
    }
    sort($areasUnicas);
    
    $tiposVinculacionUnicos = [];
    foreach ($todosLosTiposVinculacion as $tipo) {
        if (isset($tipo['nombre']) && !empty($tipo['nombre'])) {
            $tiposVinculacionUnicos[] = $tipo['nombre'];
        }
    }
    sort($tiposVinculacionUnicos);
    
    $municipiosUnicos = [];
    foreach ($todosLosMunicipios as $municipio) {
        if (isset($municipio['nombre']) && !empty($municipio['nombre'])) {
            $municipiosUnicos[] = $municipio['nombre'];
        }
    }
    sort($municipiosUnicos);
    
} catch (Exception $e) {
    error_log("Error al cargar datos: " . $e->getMessage());
    $contratistas = [];
    $areasUnicas = [];
    $tiposVinculacionUnicos = [];
    $municipiosUnicos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Contratistas - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/visor_registrados.css">
    <style>
        /* Estilos optimizados */
        .document-icons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .doc-icon {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            font-size: 0.85rem;
        }
        
        .doc-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .doc-icon.has-file {
            opacity: 1;
        }
        
        .doc-icon.no-file {
            opacity: 0.3;
            cursor: default;
        }
        
        .doc-icon.cv {
            background-color: #dc3545;
            color: white;
        }
        
        .doc-icon.contrato {
            background-color: #007bff;
            color: white;
        }
        
        .doc-icon.acta {
            background-color: #28a745;
            color: white;
        }
        
        .doc-icon.rp {
            background-color: #fd7e14;
            color: white;
        }
        
        .tooltip-text {
            visibility: hidden;
            width: 150px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .doc-icon:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        .compact-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            font-size: 0.85rem;
        }
        
        .info-line {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .info-line i {
            width: 16px;
            color: #6c757d;
        }
        
        .status-badges {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: nowrap;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-view { 
            background-color: #17a2b8; 
            color: white; 
        }
        
        .btn-view:hover { 
            background-color: #138496; 
            transform: translateY(-2px); 
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }
        
        .btn-edit { 
            background-color: #ffc107; 
            color: #212529; 
        }
        
        .btn-edit:hover { 
            background-color: #e0a800; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }
        
        /* Contenedor de municipios */
        .municipios-container {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .municipio-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8rem;
            padding: 2px 4px;
            border-radius: 3px;
            background-color: #f8f9fa;
        }
        
        .municipio-item.principal {
            background-color: #e7f3ff;
            font-weight: 500;
        }
        
        .municipio-icon {
            color: #007bff;
        }
        
        /* Responsive para tabla */
        @media (max-width: 1200px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .contratistas-table {
                min-width: 1000px;
            }
        }
        
        /* Filtros mejorados */
        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            min-width: 180px;
        }
        
        /* Tabla más compacta */
        .contratistas-table {
            font-size: 0.9rem;
        }
        
        .contratistas-table th {
            padding: 12px 8px;
            font-weight: 600;
        }
        
        .contratistas-table td {
            padding: 10px 8px;
            vertical-align: top;
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
        
        <main class="app-main">
            <div class="welcome-section">
                <h3>Visor de Contratistas Registrados</h3>
                <p>Consulta y visualización de todos los contratistas del sistema</p>
            </div>
            
            <!-- Estadísticas simplificadas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($contratistas); ?></div>
                        <div class="stat-label">Total Contratistas</div>
                    </div>
                </div>
                
                <?php 
                $contratosVigentes = array_filter($contratistas, function($c) {
                    if (!isset($c['fecha_final'])) return false;
                    try {
                        $fechaFinal = new DateTime($c['fecha_final']);
                        $hoy = new DateTime();
                        return $fechaFinal > $hoy;
                    } catch (Exception $e) {
                        return false;
                    }
                });
                
                $conMultiplesMunicipios = array_filter($contratistas, function($c) {
                    return !empty($c['municipio_secundario']) || !empty($c['municipio_terciario']);
                });
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($contratosVigentes); ?></div>
                        <div class="stat-label">Contratos Vigentes</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count($conMultiplesMunicipios); ?></div>
                        <div class="stat-label">Múltiples Municipios</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php 
                            $contratosVencidos = array_filter($contratistas, function($c) {
                                if (!isset($c['fecha_final'])) return false;
                                try {
                                    $fechaFinal = new DateTime($c['fecha_final']);
                                    $hoy = new DateTime();
                                    return $fechaFinal < $hoy;
                                } catch (Exception $e) {
                                    return false;
                                }
                            });
                            echo count($contratosVencidos);
                            ?>
                        </div>
                        <div class="stat-label">Contratos Vencidos</div>
                    </div>
                </div>
            </div>
            
            <!-- Herramientas de búsqueda -->
            <div class="tools-section">
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="searchInput" 
                               class="search-input" 
                               placeholder="Buscar por nombre, cédula, contrato, municipio...">
                    </div>
                    <div class="search-actions">
                        <button id="clearFiltersBtn" class="btn-refresh" title="Limpiar todos los filtros y búsquedas">
                            <i class="fas fa-broom"></i> Limpiar
                        </button>
                        <button id="refreshBtn" class="btn-refresh" title="Recargar datos desde el servidor">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
                
                <div class="filter-container">
                    <div class="filter-group">
                        <select id="filterStatus" class="filter-select">
                            <option value="">Todos los estados</option>
                            <option value="vigente">Contratos Vigentes</option>
                            <option value="vencido">Contratos Vencidos</option>
                        </select>
                        
                        <select id="filterArea" class="filter-select">
                            <option value="">Todas las áreas</option>
                            <?php foreach ($areasUnicas as $area): ?>
                            <option value="<?php echo htmlspecialchars($area); ?>">
                                <?php echo htmlspecialchars($area); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Filtro: Tipo de Vinculación -->
                        <select id="filterVinculacion" class="filter-select">
                            <option value="">Todos los tipos de vinculación</option>
                            <?php foreach ($tiposVinculacionUnicos as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo); ?>">
                                <?php echo htmlspecialchars($tipo); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Filtro: Municipio Principal -->
                        <select id="filterMunicipio" class="filter-select">
                            <option value="">Todos los municipios</option>
                            <?php foreach ($municipiosUnicos as $municipio): ?>
                            <option value="<?php echo htmlspecialchars($municipio); ?>">
                                <?php echo htmlspecialchars($municipio); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Tabla optimizada -->
            <div class="table-container">
                <div class="table-header">
                    <h4>Listado de Contratistas</h4>
                    <div class="table-count">
                        Mostrando <span id="rowCount"><?php echo count($contratistas); ?></span> registros
                        <?php if (count($contratistas) > 0): ?>
                            <span class="text-muted" style="margin-left: 10px; font-size: 0.9rem;">
                                <i class="fas fa-mouse-pointer"></i>
                                Haz clic en los iconos de documentos para descargar
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="contratistas-table" id="contratistasTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Contratista</th>
                                <th>Información</th>
                                <th>Contrato</th>
                                <th>Ubicación</th>
                                <th class="text-center">Documentos</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contratistas)): ?>
                                <tr class="empty-row" data-is-original="true">
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h5>No hay contratistas registrados</h5>
                                            <p>Comienza agregando un nuevo contratista desde el menú principal.</p>
                                            <a href="agregar_contratista.php" class="btn btn-primary" style="margin-top: 15px;">
                                                <i class="fas fa-plus"></i> Agregar Contratista
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contratistas as $index => $contratista): 
                                    // Fechas formateadas
                                    $fechaContrato = isset($contratista['fecha_contrato']) ? date('d/m/Y', strtotime($contratista['fecha_contrato'])) : 'N/A';
                                    $fechaInicio = isset($contratista['fecha_inicio']) ? date('d/m/Y', strtotime($contratista['fecha_inicio'])) : 'N/A';
                                    $fechaFinal = isset($contratista['fecha_final']) ? date('d/m/Y', strtotime($contratista['fecha_final'])) : 'N/A';
                                    
                                    // Determinar estado del contrato
                                    $estadoContrato = 'indefinido';
                                    if (isset($contratista['fecha_final'])) {
                                        try {
                                            $fechaFin = new DateTime($contratista['fecha_final']);
                                            $hoy = new DateTime();
                                            $estadoContrato = $fechaFin > $hoy ? 'vigente' : 'vencido';
                                        } catch (Exception $e) {
                                            $estadoContrato = 'indefinido';
                                        }
                                    }
                                    
                                    // Verificar documentos
                                    $tieneCV = !empty($contratista['cv_nombre_original']);
                                    $tieneContrato = !empty($contratista['contrato_nombre_original']);
                                    $tieneActa = !empty($contratista['acta_inicio_nombre_original']);
                                    $tieneRP = !empty($contratista['rp_nombre_original']);
                                    
                                    // Municipios
                                    $municipios = [];
                                    if (!empty($contratista['municipio_principal'])) {
                                        $municipios[] = ['nombre' => $contratista['municipio_principal'], 'tipo' => 'principal'];
                                    }
                                    if (!empty($contratista['municipio_secundario'])) {
                                        $municipios[] = ['nombre' => $contratista['municipio_secundario'], 'tipo' => 'secundario'];
                                    }
                                    if (!empty($contratista['municipio_terciario'])) {
                                        $municipios[] = ['nombre' => $contratista['municipio_terciario'], 'tipo' => 'terciario'];
                                    }
                                ?>
                                    <tr class="contratista-row" 
                                        data-estado-contrato="<?php echo $estadoContrato; ?>"
                                        data-area="<?php echo htmlspecialchars($contratista['area'] ?? ''); ?>"
                                        data-vinculacion="<?php echo htmlspecialchars($contratista['tipo_vinculacion'] ?? ''); ?>"
                                        data-municipio-principal="<?php echo htmlspecialchars($contratista['municipio_principal'] ?? ''); ?>"
                                        data-municipio-secundario="<?php echo htmlspecialchars($contratista['municipio_secundario'] ?? ''); ?>"
                                        data-municipio-terciario="<?php echo htmlspecialchars($contratista['municipio_terciario'] ?? ''); ?>"
                                        data-has-cv="<?php echo $tieneCV ? '1' : '0'; ?>"
                                        data-has-contrato="<?php echo $tieneContrato ? '1' : '0'; ?>"
                                        data-has-acta="<?php echo $tieneActa ? '1' : '0'; ?>"
                                        data-has-rp="<?php echo $tieneRP ? '1' : '0'; ?>">
                                        
                                        <!-- Número -->
                                        <td class="text-center"><?php echo $index + 1; ?></td>
                                        
                                        <!-- Contratista -->
                                        <td>
                                            <div class="compact-info">
                                                <strong><?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?></strong>
                                                <div class="info-line">
                                                    <i class="fas fa-id-card"></i>
                                                    <span><?php echo htmlspecialchars($contratista['cedula'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="info-line">
                                                    <i class="fas fa-phone"></i>
                                                    <span><?php echo htmlspecialchars($contratista['telefono'] ?? 'N/A'); ?></span>
                                                </div>
                                                <?php if (!empty($contratista['correo_personal'])): ?>
                                                <div class="info-line">
                                                    <i class="fas fa-envelope"></i>
                                                    <span style="font-size: 0.8rem;"><?php echo htmlspecialchars($contratista['correo_personal']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Información -->
                                        <td>
                                            <div class="compact-info">
                                                <?php if (!empty($contratista['tipo_vinculacion'])): ?>
                                                <div class="info-line">
                                                    <i class="fas fa-briefcase"></i>
                                                    <span><?php echo htmlspecialchars($contratista['tipo_vinculacion']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div class="info-line">
                                                    <i class="fas fa-sitemap"></i>
                                                    <span><?php echo htmlspecialchars($contratista['area'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="info-line">
                                                    <i class="fas fa-calendar-plus"></i>
                                                    <span>Reg: <?php echo isset($contratista['created_at']) ? date('d/m/Y', strtotime($contratista['created_at'])) : 'N/A'; ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Contrato -->
                                        <td>
                                            <div class="compact-info">
                                                <div class="info-line">
                                                    <i class="fas fa-file-invoice"></i>
                                                    <span><strong><?php echo htmlspecialchars($contratista['numero_contrato'] ?? 'N/A'); ?></strong></span>
                                                </div>
                                                <div class="info-line">
                                                    <i class="fas fa-play-circle"></i>
                                                    <span>Inicio: <?php echo $fechaInicio; ?></span>
                                                </div>
                                                <div class="info-line">
                                                    <i class="fas fa-flag-checkered"></i>
                                                    <span>Final: <?php echo $fechaFinal; ?></span>
                                                </div>
                                                <?php if (!empty($contratista['duracion_contrato'])): ?>
                                                <div class="info-line">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo htmlspecialchars($contratista['duracion_contrato']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if (!empty($contratista['numero_registro_presupuestal'])): ?>
                                                <div class="info-line">
                                                    <i class="fas fa-file-invoice-dollar"></i>
                                                    <span>RP: <?php echo htmlspecialchars($contratista['numero_registro_presupuestal']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Ubicación -->
                                        <td>
                                            <div class="municipios-container">
                                                <?php foreach ($municipios as $municipio): ?>
                                                <div class="municipio-item <?php echo $municipio['tipo']; ?>">
                                                    <i class="fas fa-map-marker-alt municipio-icon"></i>
                                                    <span><?php echo htmlspecialchars($municipio['nombre']); ?></span>
                                                    <?php if ($municipio['tipo'] === 'principal'): ?>
                                                    <small class="badge badge-success" style="margin-left: auto; padding: 1px 4px; font-size: 0.7rem;">
                                                        Principal
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endforeach; ?>
                                                <?php if (count($municipios) === 0): ?>
                                                <span class="text-muted" style="font-size: 0.85rem;">
                                                    <i class="fas fa-map-marker-alt"></i> Sin ubicación
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Documentos -->
                                        <td class="text-center">
                                            <div class="document-icons">
                                                <div class="doc-icon cv <?php echo $tieneCV ? 'has-file' : 'no-file'; ?>" 
                                                     onclick="<?php echo $tieneCV ? 'descargarCV(' . $contratista['id_detalle'] . ')' : ''; ?>"
                                                     title="<?php echo $tieneCV ? 'CV: ' . htmlspecialchars($contratista['cv_nombre_original']) : 'Sin CV'; ?>">
                                                    <i class="fas fa-user-graduate"></i>
                                                </div>
                                                
                                                <div class="doc-icon contrato <?php echo $tieneContrato ? 'has-file' : 'no-file'; ?>" 
                                                     onclick="<?php echo $tieneContrato ? 'descargarContrato(' . $contratista['id_detalle'] . ')' : ''; ?>"
                                                     title="<?php echo $tieneContrato ? 'Contrato PDF' : 'Sin contrato'; ?>">
                                                    <i class="fas fa-file-contract"></i>
                                                </div>
                                                
                                                <div class="doc-icon acta <?php echo $tieneActa ? 'has-file' : 'no-file'; ?>" 
                                                     onclick="<?php echo $tieneActa ? 'descargarActa(' . $contratista['id_detalle'] . ')' : ''; ?>"
                                                     title="<?php echo $tieneActa ? 'Acta de inicio' : 'Sin acta'; ?>">
                                                    <i class="fas fa-file-signature"></i>
                                                </div>
                                                
                                                <div class="doc-icon rp <?php echo $tieneRP ? 'has-file' : 'no-file'; ?>" 
                                                     onclick="<?php echo $tieneRP ? 'descargarRP(' . $contratista['id_detalle'] . ')' : ''; ?>"
                                                     title="<?php echo $tieneRP ? 'Registro presupuestal' : 'Sin RP'; ?>">
                                                    <i class="fas fa-file-invoice-dollar"></i>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <!-- Estado -->
                                        <td class="text-center">
                                            <div class="status-badges">
                                                <?php if ($estadoContrato === 'vigente'): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check-circle"></i> Vigente
                                                    </span>
                                                <?php elseif ($estadoContrato === 'vencido'): ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-circle"></i> Vencido
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-question-circle"></i> Sin fecha
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Acciones simplificadas -->
                                        <td class="text-center">
                                            <div class="action-buttons">
                                                <button class="btn-action btn-view" 
                                                        onclick="verDetalle('<?php echo $contratista['id_detalle'] ?? 0; ?>')"
                                                        title="Ver detalles completos">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button class="btn-action btn-edit" 
                                                        onclick="editarContratista('<?php echo $contratista['id_detalle'] ?? 0; ?>')"
                                                        title="Editar contratista">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Paginación -->
            <?php if (count($contratistas) > 50): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Mostrando 50 de <?php echo count($contratistas); ?> registros
                </div>
                <div class="pagination-controls">
                    <button class="pagination-btn" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="pagination-btn active">1</button>
                    <button class="pagination-btn">2</button>
                    <button class="pagination-btn">3</button>
                    <button class="pagination-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </main>
        
        <button class="volver-btn" id="volverBtn">
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
    
    <!-- Scripts -->
    <script>
        // Función para filtrar tabla - OPTIMIZADA PARA BUSCAR EN TODOS LOS MUNICIPIOS
        function filtrarTabla() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const filterStatus = document.getElementById('filterStatus').value;
            const filterArea = document.getElementById('filterArea').value.toLowerCase();
            const filterVinculacion = document.getElementById('filterVinculacion').value.toLowerCase();
            const filterMunicipio = document.getElementById('filterMunicipio').value.toLowerCase();
            
            const tbody = document.querySelector('#contratistasTable tbody');
            const allRows = tbody.querySelectorAll('.contratista-row');
            let visibleCount = 0;
            
            // Variable para almacenar si hay filtros activos
            const hayFiltros = searchTerm || filterStatus || filterArea || filterVinculacion || filterMunicipio;
            
            if (!hayFiltros) {
                // Si no hay filtros, mostrar todas las filas
                allRows.forEach(row => {
                    row.style.display = '';
                    visibleCount++;
                });
            } else {
                // Aplicar filtros
                allRows.forEach(row => {
                    const estadoContrato = row.getAttribute('data-estado-contrato');
                    const area = row.getAttribute('data-area').toLowerCase();
                    const vinculacion = row.getAttribute('data-vinculacion').toLowerCase();
                    const municipioPrincipal = row.getAttribute('data-municipio-principal')?.toLowerCase() || '';
                    const municipioSecundario = row.getAttribute('data-municipio-secundario')?.toLowerCase() || '';
                    const municipioTerciario = row.getAttribute('data-municipio-terciario')?.toLowerCase() || '';
                    
                    // Verificar si pasa cada filtro
                    let pasaFiltro = true;
                    
                    // 1. Filtro de búsqueda general (incluye todos los municipios)
                    if (searchTerm) {
                        const textoTodo = row.textContent.toLowerCase();
                        const municipiosText = [municipioPrincipal, municipioSecundario, municipioTerciario]
                            .filter(m => m)
                            .join(' ');
                        const textoCompleto = textoTodo + ' ' + municipiosText;
                        
                        if (!textoCompleto.includes(searchTerm)) {
                            pasaFiltro = false;
                        }
                    }
                    
                    // 2. Filtro de estado de contrato
                    if (pasaFiltro && filterStatus) {
                        if ((filterStatus === 'vigente' && estadoContrato !== 'vigente') ||
                            (filterStatus === 'vencido' && estadoContrato !== 'vencido')) {
                            pasaFiltro = false;
                        }
                    }
                    
                    // 3. Filtro de área
                    if (pasaFiltro && filterArea && area !== filterArea) {
                        pasaFiltro = false;
                    }
                    
                    // 4. Filtro de vinculación
                    if (pasaFiltro && filterVinculacion && vinculacion !== filterVinculacion) {
                        pasaFiltro = false;
                    }
                    
                    // 5. Filtro de municipio - BUSCA EN TODOS LOS MUNICIPIOS
                    if (pasaFiltro && filterMunicipio) {
                        const municipios = [municipioPrincipal, municipioSecundario, municipioTerciario];
                        const encontrado = municipios.some(municipio => 
                            municipio && municipio.includes(filterMunicipio)
                        );
                        
                        if (!encontrado) {
                            pasaFiltro = false;
                        }
                    }
                    
                    if (pasaFiltro) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            document.getElementById('rowCount').textContent = visibleCount;
            
            // Mostrar mensaje si no hay resultados
            const emptyRow = document.querySelector('.empty-row');
            const originalEmptyRow = document.querySelector('tr.empty-row[data-is-original="true"]');
            
            if (visibleCount === 0 && allRows.length > 0) {
                // Eliminar fila vacía anterior si existe (pero no la original)
                if (emptyRow && !emptyRow.hasAttribute('data-is-original')) {
                    emptyRow.remove();
                }
                
                // Crear nueva fila de "no resultados"
                const newRow = document.createElement('tr');
                newRow.classList.add('empty-row');
                newRow.innerHTML = `
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h5>No se encontraron resultados</h5>
                            <p>Intenta con otros términos de búsqueda o filtros.</p>
                            <button onclick="limpiarFiltros()" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-broom"></i> Limpiar filtros
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(newRow);
            } else {
                // Eliminar fila de "no resultados" si existe (pero no la original)
                if (emptyRow && !emptyRow.hasAttribute('data-is-original')) {
                    emptyRow.remove();
                }
                
                // Si hay una fila vacía original y ahora tenemos resultados, quitarla
                if (originalEmptyRow && visibleCount > 0) {
                    originalEmptyRow.remove();
                }
            }
        }
        
        // Función para limpiar filtros - MEJORADA
        function limpiarFiltros() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterArea').value = '';
            document.getElementById('filterVinculacion').value = '';
            document.getElementById('filterMunicipio').value = '';
            
            // Forzar una nueva búsqueda
            filtrarTabla();
            
            // Enfocar el campo de búsqueda
            document.getElementById('searchInput').focus();
        }
        
        // Función para recargar datos - NUEVA
        function recargarDatos() {
            // Mostrar indicador de carga
            const refreshBtn = document.getElementById('refreshBtn');
            const originalHTML = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
            refreshBtn.disabled = true;
            
            // Recargar la página después de un breve retraso para mostrar el spinner
            setTimeout(() => {
                location.reload();
            }, 500);
        }
        
        // Funciones para descargar documentos
        function descargarCV(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_cv.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarContrato(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_contrato.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarActa(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_acta.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarRP(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_rp.php?id=${idDetalle}`, '_blank');
        }
        
        // Event listeners actualizados
        document.addEventListener('DOMContentLoaded', function() {
            // Buscar
            document.getElementById('searchInput').addEventListener('input', filtrarTabla);
            
            // Filtros
            document.getElementById('filterStatus').addEventListener('change', filtrarTabla);
            document.getElementById('filterArea').addEventListener('change', filtrarTabla);
            document.getElementById('filterVinculacion').addEventListener('change', filtrarTabla);
            document.getElementById('filterMunicipio').addEventListener('change', filtrarTabla);
            
            // Botones
            document.getElementById('clearFiltersBtn').addEventListener('click', limpiarFiltros);
            document.getElementById('refreshBtn').addEventListener('click', recargarDatos);
            document.getElementById('volverBtn').addEventListener('click', () => {
                window.location.href = 'menuContratistas.php';
            });
            
            // Permitir buscar con Enter
            document.getElementById('searchInput').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') filtrarTabla();
            });
            
            // También filtrar al cargar la página
            filtrarTabla();
        });
        
        // Funciones placeholder para acciones
        function verDetalle(idDetalle) {
            if (!idDetalle || idDetalle === '0') return alert('Error: ID no válido');
            window.location.href = `ver_detalle.php?id_detalle=${idDetalle}`;
        }
        
        function editarContratista(idDetalle) {
            if (!idDetalle || idDetalle === '0') return alert('Error: ID no válido');
            window.location.href = `editar_contratista.php?id_detalle=${idDetalle}`;
        }
    </script>
    
</body>
</html>