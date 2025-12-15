<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ContratistaModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    $redireccion = match($_SESSION['tipo_usuario'] ?? '') {
        'administrador', 'usuario' => 'menu.php',
        default => '../index.php'
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
                                <th>Número de contrato</th>
                                <th>Fecha del contrato</th>
                                <th>Fecha inicio</th>
                                <th>Fecha final</th>
                                <th>Ubicación</th>
                                <th>Contacto</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contratistas)): ?>
                                <tr class="empty-row">
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h5>No hay contratistas registrados</h5>
                                            <p>Comienza agregando un nuevo contratista desde el menú principal.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                        </tbody>
                        </table>
                    </div>
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
                            <td colspan="7">
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