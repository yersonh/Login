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
    <script src="../../javascript/visor_registrador.js"></script>
    
</body>
</html>