<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ContratistaModel.php';

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

// Obtener contratistas
try {
    $database = new Database();
    $db = $database->conectar();
    $contratistaModel = new ContratistaModel($db);
    $contratistas = $contratistaModel->obtenerTodosContratistas();
} catch (Exception $e) {
    error_log("Error al cargar contratistas: " . $e->getMessage());
    $contratistas = [];
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
        /* Estilos adicionales para la columna CV */
        .cv-info {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            max-width: 180px;
        }
        
        .cv-name {
            font-weight: 500;
            color: #2c3e50;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        
        .cv-size {
            color: #7f8c8d;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        
        .text-muted {
            color: #6c757d !important;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
            flex-wrap: nowrap;
        }
        
        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-view {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background-color: #138496;
            transform: translateY(-2px);
        }
        
        .btn-download {
            background-color: #28a745;
            color: white;
        }
        
        .btn-download:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-edit:hover {
            background-color: #e0a800;
            transform: translateY(-2px);
        }
        
        .text-primary {
            color: #007bff !important;
        }
        
        .fa-file-pdf {
            color: #d9534f;
        }
        
        .fa-file-exclamation {
            color: #6c757d;
        }
        
        /* Ajuste para tabla con nueva columna */
        .contratistas-table th:nth-child(9),
        .contratistas-table td:nth-child(9) {
            min-width: 150px;
            max-width: 180px;
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
            
            <!-- Estadísticas -->
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
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
                            <?php 
                            $activos = array_filter($contratistas, function($c) {
                                return isset($c['usuario_activo']) && $c['usuario_activo'] == true;
                            });
                            echo count($activos);
                            ?>
                        </div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">
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
                            echo count($contratosVigentes);
                            ?>
                        </div>
                        <div class="stat-label">Contratos Vigentes</div>
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
                               placeholder="Buscar por nombre, cédula, contrato...">
                    </div>
                    <div class="search-actions">
                        <button id="searchBtn" class="btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button id="refreshBtn" class="btn-refresh">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
                
                <div class="filter-container">
                    <select id="filterStatus" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="activo">Usuarios Activos</option>
                        <option value="inactivo">Usuarios Inactivos</option>
                        <option value="vigente">Contratos Vigentes</option>
                        <option value="vencido">Contratos Vencidos</option>
                    </select>
                    
                    <select id="filterArea" class="filter-select">
                        <option value="">Todas las áreas</option>
                        <?php 
                        $areasUnicas = [];
                        foreach ($contratistas as $c) {
                            if (isset($c['area']) && !in_array($c['area'], $areasUnicas)) {
                                $areasUnicas[] = $c['area'];
                            }
                        }
                        sort($areasUnicas);
                        foreach ($areasUnicas as $area): 
                        ?>
                            <option value="<?php echo htmlspecialchars($area); ?>">
                                <?php echo htmlspecialchars($area); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Tabla de contratistas -->
            <div class="table-container">
                <div class="table-header">
                    <h4>Listado de Contratistas</h4>
                    <div class="table-count">
                        Mostrando <span id="rowCount"><?php echo count($contratistas); ?></span> registros
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="contratistas-table" id="contratistasTable">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Nombres</th>
                                <th>Cédula</th>
                                <th>N° contrato</th>
                                <th>Fecha contrato</th>
                                <th>Fecha inicio</th>
                                <th>Fecha final</th>
                                <th>Ubicación</th>
                                <th>Contacto</th>
                                <th>CV</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contratistas)): ?>
                                <tr class="empty-row">
                                    <td colspan="12">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h5>No hay contratistas registrados</h5>
                                            <p>Comienza agregando un nuevo contratista desde el menú principal.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contratistas as $index => $contratista): 
                                    $fechaInicio = isset($contratista['fecha_inicio']) ? date('d/m/Y', strtotime($contratista['fecha_inicio'])) : 'N/A';
                                    $fechaFinal = isset($contratista['fecha_final']) ? date('d/m/Y', strtotime($contratista['fecha_final'])) : 'N/A';
                                    $estadoUsuario = isset($contratista['usuario_activo']) && $contratista['usuario_activo'] ? 'activo' : 'inactivo';
                                    
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
                                    
                                    // Verificar si tiene CV
                                    $tieneCV = !empty($contratista['cv_nombre_original']) && !empty($contratista['cv_tamano']);
                                    $tamanoCV = $tieneCV ? round($contratista['cv_tamano'] / 1024, 1) : 0;
                                ?>
                                    <tr class="contratista-row" 
                                        data-estado-usuario="<?php echo $estadoUsuario; ?>"
                                        data-estado-contrato="<?php echo $estadoContrato; ?>"
                                        data-area="<?php echo htmlspecialchars($contratista['area'] ?? ''); ?>">
                                        <td class="text-center"><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?></td>
                                        <td><?php echo htmlspecialchars($contratista['cedula'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($contratista['numero_contrato'] ?? 'N/A'); ?></td>
                                        <td><?php echo isset($contratista['fecha_contrato']) ? date('d/m/Y', strtotime($contratista['fecha_contrato'])) : 'N/A'; ?></td>
                                        <td><?php echo $fechaInicio; ?></td>
                                        <td><?php echo $fechaFinal; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($contratista['area'] ?? 'N/A'); ?><br>
                                            <small class="info-secondary">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($contratista['municipio_principal'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($contratista['correo'] ?? 'N/A'); ?><br>
                                            <small class="info-secondary">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($contratista['telefono'] ?? 'N/A'); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($tieneCV): ?>
                                                <div class="cv-info">
                                                    <i class="fas fa-file-pdf text-primary"></i>
                                                    <span class="cv-name">
                                                        <?php 
                                                        $nombreCorto = strlen($contratista['cv_nombre_original']) > 15 
                                                            ? substr($contratista['cv_nombre_original'], 0, 15) . '...' 
                                                            : $contratista['cv_nombre_original'];
                                                        echo htmlspecialchars($nombreCorto);
                                                        ?>
                                                    </span>
                                                    <small class="cv-size">(<?php echo $tamanoCV; ?> KB)</small>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-file-exclamation"></i> Sin CV
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="status-badges">
                                                <?php if ($estadoUsuario === 'activo'): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-user-check"></i> Activo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-user-times"></i> Inactivo
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($estadoContrato === 'vigente'): ?>
                                                    <span class="badge badge-primary">
                                                        <i class="fas fa-check-circle"></i> Vigente
                                                    </span>
                                                <?php elseif ($estadoContrato === 'vencido'): ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-exclamation-circle"></i> Vencido
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="action-buttons">
                                                <button class="btn-action btn-view" 
                                                        onclick="verDetalle('<?php echo $contratista['id_detalle'] ?? 0; ?>')"
                                                        title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($tieneCV): ?>
                                                <button class="btn-action btn-download" 
                                                        onclick="descargarCV('<?php echo $contratista['id_detalle'] ?? 0; ?>')"
                                                        title="Descargar CV">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn-action btn-edit" 
                                                        onclick="editarContratista('<?php echo $contratista['id_detalle'] ?? 0; ?>')"
                                                        title="Editar">
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
        // Función para descargar CV
        function descargarCV(idDetalle) {
            if (!idDetalle || idDetalle === '0') {
                alert('Error: ID no válido');
                return;
            }
            
            // Mostrar indicador de carga
            const btn = event.target.closest('.btn-download');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;
            
            // Descargar el archivo
            const url = `../../controllers/descargar_cv.php?id=${idDetalle}`;
            
            // Crear enlace temporal para descarga
            const link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Restaurar botón después de 2 segundos
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }, 2000);
        }
        
        // Función para buscar en la tabla
        function filtrarTabla() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filterStatus = document.getElementById('filterStatus').value;
            const filterArea = document.getElementById('filterArea').value;
            const rows = document.querySelectorAll('.contratista-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const estadoUsuario = row.getAttribute('data-estado-usuario');
                const estadoContrato = row.getAttribute('data-estado-contrato');
                const area = row.getAttribute('data-area');
                
                let matchesSearch = text.includes(searchTerm);
                let matchesStatus = true;
                let matchesArea = true;
                
                // Filtrar por estado
                if (filterStatus) {
                    if (filterStatus === 'activo' && estadoUsuario !== 'activo') matchesStatus = false;
                    if (filterStatus === 'inactivo' && estadoUsuario !== 'inactivo') matchesStatus = false;
                    if (filterStatus === 'vigente' && estadoContrato !== 'vigente') matchesStatus = false;
                    if (filterStatus === 'vencido' && estadoContrato !== 'vencido') matchesStatus = false;
                }
                
                // Filtrar por área
                if (filterArea && area.toLowerCase() !== filterArea.toLowerCase()) {
                    matchesArea = false;
                }
                
                if (matchesSearch && matchesStatus && matchesArea) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.getElementById('rowCount').textContent = visibleCount;
            
            // Mostrar mensaje si no hay resultados
            const emptyRow = document.querySelector('.empty-row');
            if (visibleCount === 0 && rows.length > 0) {
                if (!emptyRow) {
                    const tbody = document.querySelector('#contratistasTable tbody');
                    tbody.innerHTML = `
                        <tr class="empty-row">
                            <td colspan="12">
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h5>No se encontraron resultados</h5>
                                    <p>Intenta con otros términos de búsqueda o filtros.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
            } else if (emptyRow && visibleCount > 0) {
                emptyRow.remove();
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Buscar
            document.getElementById('searchInput').addEventListener('input', filtrarTabla);
            document.getElementById('searchBtn').addEventListener('click', filtrarTabla);
            
            // Filtros
            document.getElementById('filterStatus').addEventListener('change', filtrarTabla);
            document.getElementById('filterArea').addEventListener('change', filtrarTabla);
            
            // Actualizar
            document.getElementById('refreshBtn').addEventListener('click', function() {
                location.reload();
            });
            
            // Volver al menú
            document.getElementById('volverBtn').addEventListener('click', function() {
                window.location.href = 'menuContratistas.php';
            });
            
            // Permitir buscar con Enter
            document.getElementById('searchInput').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    filtrarTabla();
                }
            });
        });
        
        // Funciones placeholder para acciones
        function verDetalle(idDetalle) {
            if (!idDetalle || idDetalle === '0') {
                alert('Error: ID no válido');
                return;
            }
            alert(`Ver detalle del contratista ID: ${idDetalle}\n\nEsta función estará disponible pronto.`);
            // window.location.href = `ver_detalle.php?id_detalle=${idDetalle}`;
        }
        
        function editarContratista(idDetalle) {
            if (!idDetalle || idDetalle === '0') {
                alert('Error: ID no válido');
                return;
            }
            alert(`Editar contratista ID: ${idDetalle}\n\nEsta función estará disponible pronto.`);
            // window.location.href = `editar_contratista.php?id_detalle=${idDetalle}`;
        }
    </script>
    
</body>
</html>