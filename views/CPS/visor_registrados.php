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
    <style>
        /* Estilos básicos para la tabla */
        .contratistas-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .contratistas-table thead {
            background-color: #2c3e50;
            color: white;
        }
        
        .contratistas-table th,
        .contratistas-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .contratistas-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        .contratistas-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .status-activo {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-inactivo {
            color: #dc3545;
            font-weight: bold;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-primary {
            background-color: #007bff;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: black;
        }
        
        .search-container {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-btn {
            padding: 10px 20px;
            background-color: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-btn:hover {
            background-color: #34495e;
        }
        
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2c3e50;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
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
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($contratistas); ?></div>
                    <div class="stat-label">Total de Contratistas</div>
                </div>
                <div class="stat-card">
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
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $contratosVigentes = array_filter($contratistas, function($c) {
                            if (!isset($c['fecha_final'])) return false;
                            $fechaFinal = DateTime::createFromFormat('Y-m-d', $c['fecha_final']);
                            $hoy = new DateTime();
                            return $fechaFinal && $fechaFinal > $hoy;
                        });
                        echo count($contratosVigentes);
                        ?>
                    </div>
                    <div class="stat-label">Contratos Vigentes</div>
                </div>
            </div>
            
            <!-- Barra de búsqueda -->
            <div class="search-container">
                <input type="text" 
                       id="searchInput" 
                       class="search-input" 
                       placeholder="Buscar por nombre, cédula, contrato o municipio...">
                <button id="searchBtn" class="search-btn">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button id="refreshBtn" class="search-btn">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
            </div>
            
            <!-- Tabla de contratistas -->
            <div class="table-responsive">
                <table class="contratistas-table" id="contratistasTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Información Personal</th>
                            <th>Contrato</th>
                            <th>Ubicación</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contratistas)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                    <p style="color: #666;">No hay contratistas registrados</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contratistas as $index => $contratista): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?></strong><br>
                                        <small>Cédula: <?php echo htmlspecialchars($contratista['cedula'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <strong>Contrato: <?php echo htmlspecialchars($contratista['numero_contrato'] ?? 'N/A'); ?></strong><br>
                                        <small>
                                            Inicio: <?php echo isset($contratista['fecha_inicio']) ? date('d/m/Y', strtotime($contratista['fecha_inicio'])) : 'N/A'; ?><br>
                                            Fin: <?php echo isset($contratista['fecha_final']) ? date('d/m/Y', strtotime($contratista['fecha_final'])) : 'N/A'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($contratista['area'] ?? 'N/A'); ?></strong><br>
                                        <small>
                                            Municipio: <?php echo htmlspecialchars($contratista['municipio_principal'] ?? 'N/A'); ?><br>
                                            Tipo: <?php echo htmlspecialchars($contratista['tipo_vinculacion'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong>Email: <?php echo htmlspecialchars($contratista['correo'] ?? 'N/A'); ?></strong><br>
                                        <small>Tel: <?php echo htmlspecialchars($contratista['telefono'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <?php if (isset($contratista['usuario_activo']) && $contratista['usuario_activo']): ?>
                                            <span class="status-activo">● Activo</span><br>
                                            <span class="badge badge-success">Usuario Activo</span>
                                        <?php else: ?>
                                            <span class="status-inactivo">● Inactivo</span><br>
                                            <span class="badge badge-warning">Usuario Inactivo</span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($contratista['fecha_final'])): 
                                            $fechaFinal = new DateTime($contratista['fecha_final']);
                                            $hoy = new DateTime();
                                            if ($fechaFinal < $hoy): ?>
                                                <br><span class="badge badge-warning">Contrato Vencido</span>
                                            <?php else: ?>
                                                <br><span class="badge badge-primary">Contrato Vigente</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-view" onclick="verDetalle(<?php echo $contratista['id_detalle'] ?? 0; ?>)">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        <button class="btn-edit" onclick="editarContratista(<?php echo $contratista['id_detalle'] ?? 0; ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    
    <!-- Modal para ver detalles -->
    <div id="detalleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modalContent">
                <!-- Contenido cargado por AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script>
        // Función para buscar en la tabla
        document.getElementById('searchBtn').addEventListener('click', function() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#contratistasTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Permitir buscar con Enter
        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                document.getElementById('searchBtn').click();
            }
        });
        
        // Actualizar tabla
        document.getElementById('refreshBtn').addEventListener('click', function() {
            location.reload();
        });
        
        // Volver al menú
        document.getElementById('volverBtn').addEventListener('click', function() {
            window.location.href = 'menuContratistas.php';
        });
        
        // Ver detalle del contratista
        function verDetalle(idDetalle) {
            if (!idDetalle) {
                alert('Error: ID no válido');
                return;
            }
            
            fetch(`../../controllers/obtener_detalle_contratista.php?id_detalle=${idDetalle}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const contratista = data.data;
                        let contenido = `
                            <h3>Detalle del Contratista</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <h4>Información Personal</h4>
                                    <p><strong>Nombre:</strong> ${contratista.nombres} ${contratista.apellidos}</p>
                                    <p><strong>Cédula:</strong> ${contratista.cedula}</p>
                                    <p><strong>Teléfono:</strong> ${contratista.telefono || 'N/A'}</p>
                                </div>
                                <div>
                                    <h4>Información del Contrato</h4>
                                    <p><strong>Contrato:</strong> ${contratista.numero_contrato}</p>
                                    <p><strong>Inicio:</strong> ${new Date(contratista.fecha_inicio).toLocaleDateString('es-ES')}</p>
                                    <p><strong>Fin:</strong> ${new Date(contratista.fecha_final).toLocaleDateString('es-ES')}</p>
                                    <p><strong>Duración:</strong> ${contratista.duracion_contrato || 'N/A'}</p>
                                </div>
                                <div>
                                    <h4>Ubicación</h4>
                                    <p><strong>Área:</strong> ${contratista.area_nombre || 'N/A'}</p>
                                    <p><strong>Tipo Vinculación:</strong> ${contratista.tipo_vinculacion_nombre || 'N/A'}</p>
                                    <p><strong>Municipio Principal:</strong> ${contratista.municipio_principal_nombre || 'N/A'}</p>
                                    <p><strong>Municipio Secundario:</strong> ${contratista.municipio_secundario_nombre || 'N/A'}</p>
                                    <p><strong>Municipio Terciario:</strong> ${contratista.municipio_terciario_nombre || 'N/A'}</p>
                                </div>
                                <div>
                                    <h4>Contacto</h4>
                                    <p><strong>Email:</strong> ${contratista.correo || 'N/A'}</p>
                                    <p><strong>Dirección:</strong> ${contratista.direccion || 'N/A'}</p>
                                    <p><strong>Estado Usuario:</strong> ${contratista.usuario_activo ? 'Activo' : 'Inactivo'}</p>
                                </div>
                            </div>
                            <div style="margin-top: 20px; padding: 10px; background-color: #f5f5f5; border-radius: 4px;">
                                <p><strong>N° Registro Presupuestal:</strong> ${contratista.numero_registro_presupuestal || 'N/A'}</p>
                                <p><strong>Fecha RP:</strong> ${contratista.fecha_rp ? new Date(contratista.fecha_rp).toLocaleDateString('es-ES') : 'N/A'}</p>
                            </div>
                        `;
                        
                        document.getElementById('modalContent').innerHTML = contenido;
                        document.getElementById('detalleModal').style.display = 'block';
                    } else {
                        alert('Error al cargar los detalles: ' + (data.error || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }
        
        // Editar contratista
        function editarContratista(idDetalle) {
            // Aquí rediriges al formulario de edición
            window.location.href = `editar_contratista.php?id_detalle=${idDetalle}`;
        }
        
        // Cerrar modal
        document.querySelector('.close-modal').addEventListener('click', function() {
            document.getElementById('detalleModal').style.display = 'none';
        });
        
        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('detalleModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
    
</body>
</html>