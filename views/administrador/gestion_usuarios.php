<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/usuario.php';

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

// Obtener filtro actual
$filtro = $_GET['filtro'] ?? 'todos';

// 3. Obtener usuarios según filtro
try {
    $database = new Database();
    $db = $database->conectar();
    $usuarioModel = new Usuario($db);
    
    if ($filtro === 'todos') {
        $usuarios = $usuarioModel->obtenerTodos();
    } else {
        $usuarios = $usuarioModel->obtenerPorEstado($filtro);
    }
    
    $totalUsuarios = count($usuarios);
    
    // Obtener contadores
    $contadores = $usuarioModel->contarPorEstado();
    
} catch (Exception $e) {
    error_log("Error al obtener usuarios: " . $e->getMessage());
    $usuarios = [];
    $totalUsuarios = 0;
    $contadores = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
}

// Función para formatear la fecha
function formatearFecha($fechaBD) {
    if (empty($fechaBD)) {
        return 'No registrada';
    }
    if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $fechaBD)) {
        return $fechaBD;
    }
    try {
        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaBD);
        if ($fecha) {
            return $fecha->format('d/m/Y H:i');
        }
        $fecha = new DateTime($fechaBD);
        return $fecha->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $fechaBD;
    }
}

// Función para determinar el estado del usuario
function obtenerEstadoUsuario($activo, $fechaAprobacion = null) {
    if ($activo == 1 || $activo === true) {
        return ['texto' => 'Activo', 'clase' => 'status-active'];
    } else {
        // Si tiene fecha_aprobacion pero está inactivo, es "Inactivo"
        if ($fechaAprobacion) {
            return ['texto' => 'Inactivo', 'clase' => 'status-blocked'];
        } else {
            return ['texto' => 'Pendiente', 'clase' => 'status-pending'];
        }
    }
}

// Función para obtener el badge del tipo de usuario
function obtenerBadgeTipoUsuario($tipo) {
    $tipos = [
        'administrador' => ['texto' => 'Administrador', 'clase' => 'badge-admin'],
        'asistente' => ['texto' => 'Asistente', 'clase' => 'badge-assistant'],
        'contratista' => ['texto' => 'Contratista', 'clase' => 'badge-contractor'],
        'superadmin' => ['texto' => 'Super Admin', 'clase' => 'badge-superadmin']
    ];
    
    $tipo = strtolower($tipo);
    if (isset($tipos[$tipo])) {
        return $tipos[$tipo];
    }
    
    return ['texto' => ucfirst($tipo), 'clase' => 'badge-default'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/gestion_usuarios.css">
    <style>
        /* Estilos adicionales para los filtros */
        .filters-container {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            background: #f1f3f4;
            border-color: #adb5bd;
        }
        
        .filter-btn.active {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        
        .filter-btn.filter-pending.active {
            border-color: #ffc107;
            background: #ffc107;
        }
        
        .filter-btn.filter-active.active {
            border-color: #28a745;
            background: #28a745;
        }
        
        .filter-btn.filter-inactive.active {
            border-color: #dc3545;
            background: #dc3545;
        }
        
        .filter-count {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .filter-btn.active .filter-count {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-blocked {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .action-group {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .btn-icon-action.btn-approve {
            color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        
        .btn-icon-action.btn-approve:hover {
            background: rgba(40, 167, 69, 0.2);
        }
        
        .btn-icon-action.btn-activate {
            color: #17a2b8;
            background: rgba(23, 162, 184, 0.1);
        }
        
        .btn-icon-action.btn-activate:hover {
            background: rgba(23, 162, 184, 0.2);
        }
        
        .btn-icon-action.btn-deactivate {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }
        
        .btn-icon-action.btn-deactivate:hover {
            background: rgba(220, 53, 69, 0.2);
        }
        
        .btn-icon-action.btn-keep-pending {
            color: #ffc107;
            background: rgba(255, 193, 7, 0.1);
        }
        
        .btn-icon-action.btn-keep-pending:hover {
            background: rgba(255, 193, 7, 0.2);
        }
        
        .btn-icon-action.btn-view {
            color: #6f42c1;
            background: rgba(111, 66, 193, 0.1);
        }
        
        .btn-icon-action.btn-view:hover {
            background: rgba(111, 66, 193, 0.2);
        }
        
        .btn-icon-action.btn-edit {
            color: #fd7e14;
            background: rgba(253, 126, 20, 0.1);
        }
        
        .btn-icon-action.btn-edit:hover {
            background: rgba(253, 126, 20, 0.2);
        }
        
        .badge-notificado {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #28a745;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 12px;
            cursor: help;
        }
        
        /* Estilo para usuarios pendientes en la tabla */
        tr.user-pending td {
            background-color: #fffdf6;
        }
        
        tr.user-pending:hover td {
            background-color: #fff9e6;
        }
        
        tr.user-inactivo td {
            background-color: #fef5f5;
        }
        
        tr.user-inactivo:hover td {
            background-color: #fde8e8;
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
            <!-- Título y controles -->
            <div class="page-header">
                <h1 class="page-title">
                    <?php 
                    if ($filtro === 'pendientes') {
                        echo 'Solicitudes Pendientes';
                    } elseif ($filtro === 'activos') {
                        echo 'Usuarios Activos';
                    } elseif ($filtro === 'inactivos') {
                        echo 'Usuarios Inactivos';
                    } else {
                        echo 'Todos los Usuarios';
                    }
                    ?>
                </h1>
                <div class="page-controls">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search-users" placeholder="Buscar usuario por nombre, email...">
                    </div>
                    <button class="btn-add-user" onclick="window.location.href='../registrarusuario.php'">
                        <i class="fas fa-user-plus"></i> Añadir usuario
                    </button>
                </div>
            </div>

            <!-- Filtros de estado -->
            <div class="filters-container">
                <div class="filter-buttons">
                    <a href="?filtro=todos" class="filter-btn <?php echo $filtro == 'todos' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Todos
                        <span class="filter-count"><?php echo $contadores['total']; ?></span>
                    </a>
                    
                    <a href="?filtro=pendientes" class="filter-btn filter-pending <?php echo $filtro == 'pendientes' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pendientes
                        <span class="filter-count"><?php echo $contadores['inactivos']; ?></span>
                    </a>
                    
                    <a href="?filtro=activos" class="filter-btn filter-active <?php echo $filtro == 'activos' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Activos
                        <span class="filter-count"><?php echo $contadores['activos']; ?></span>
                    </a>
                    
                    <a href="?filtro=inactivos" class="filter-btn filter-inactive <?php echo $filtro == 'inactivos' ? 'active' : ''; ?>">
                        <i class="fas fa-ban"></i> Inactivos
                        <span class="filter-count"><?php echo $contadores['inactivos']; ?></span>
                    </a>
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Fecha de registro</th>
                            <th>Nombre completo</th>
                            <th>Email</th>
                            <th>Tipo de usuario</th>
                            <th>Estado usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" class="no-users">
                                    <i class="fas fa-users-slash"></i>
                                    <p>
                                        <?php 
                                        if ($filtro === 'pendientes') {
                                            echo 'No hay solicitudes pendientes de aprobación';
                                        } elseif ($filtro === 'activos') {
                                            echo 'No hay usuarios activos';
                                        } elseif ($filtro === 'inactivos') {
                                            echo 'No hay usuarios inactivos';
                                        } else {
                                            echo 'No hay usuarios registrados en el sistema';
                                        }
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <?php
                                $nombreCompletoUsuario = htmlspecialchars(trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')));
                                $correo = htmlspecialchars($usuario['correo'] ?? '');
                                $fechaRegistro = formatearFecha($usuario['fecha_registro'] ?? '');
                                $tipoUsuario = htmlspecialchars($usuario['tipo_usuario'] ?? 'contratista');
                                $tipoBadge = obtenerBadgeTipoUsuario($tipoUsuario);
                                $idUsuario = $usuario['id_usuario'] ?? 0;
                                $estaActivo = ($usuario['activo'] == 1 || $usuario['activo'] === true);
                                $fechaAprobacion = $usuario['fecha_aprobacion'] ?? null;
                                $notificadoAprobacion = $usuario['notificado_aprobacion'] ?? false;
                                
                                // Determinar el tipo de usuario inactivo
                                $esPendienteNuevo = !$estaActivo && empty($fechaAprobacion);
                                $esInactivoPeroYaAprobado = !$estaActivo && !empty($fechaAprobacion);
                                
                                // Obtener estado con texto correcto
                                $estado = obtenerEstadoUsuario($estaActivo, $fechaAprobacion);
                                
                                // Determinar clase CSS para la fila
                                $claseFila = '';
                                if ($esPendienteNuevo) {
                                    $claseFila = 'user-pending';
                                } elseif ($esInactivoPeroYaAprobado) {
                                    $claseFila = 'user-inactivo';
                                }
                                ?>
                                <tr class="<?php echo $claseFila; ?>">
                                    <td><?php echo $fechaRegistro; ?></td>
                                    <td><?php echo $nombreCompletoUsuario; ?></td>
                                    <td><?php echo $correo; ?></td>
                                    <td>
                                        <span class="type-badge <?php echo $tipoBadge['clase']; ?>">
                                            <?php echo $tipoBadge['texto']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $estado['clase']; ?>">
                                            <?php echo $estado['texto']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <?php if ($estaActivo): ?>
                                                <!-- Usuario activo: mostrar opción para desactivar -->
                                                <button class="btn-icon-action btn-deactivate" 
                                                        data-id="<?php echo $idUsuario; ?>"
                                                        data-nombre="<?php echo $nombreCompletoUsuario; ?>"
                                                        data-accion="desactivar_usuario"
                                                        title="Desactivar usuario">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                                
                                            <?php elseif ($esPendienteNuevo): ?>
                                                <!-- Usuario pendiente (nunca aprobado): mostrar aprobar/mantener pendiente -->
                                                <button class="btn-icon-action btn-approve" 
                                                        data-id="<?php echo $idUsuario; ?>"
                                                        data-nombre="<?php echo $nombreCompletoUsuario; ?>"
                                                        data-accion="aprobar_usuario"
                                                        title="Aprobar usuario (se enviará correo de notificación)">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                                
                                                <button class="btn-icon-action btn-keep-pending" 
                                                        data-id="<?php echo $idUsuario; ?>"
                                                        data-nombre="<?php echo $nombreCompletoUsuario; ?>"
                                                        data-accion="mantener_pendiente"
                                                        title="Mantener como pendiente">
                                                    <i class="fas fa-clock"></i>
                                                </button>
                                                
                                            <?php elseif ($esInactivoPeroYaAprobado): ?>
                                                <!-- Usuario inactivo pero ya fue aprobado antes: mostrar solo activar -->
                                                <button class="btn-icon-action btn-activate" 
                                                        data-id="<?php echo $idUsuario; ?>"
                                                        data-nombre="<?php echo $nombreCompletoUsuario; ?>"
                                                        data-accion="activar_usuario"
                                                        title="Activar usuario (sin enviar correo)">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Indicador de notificación -->
                                            <?php if ($notificadoAprobacion && !empty($fechaAprobacion)): ?>
                                                <span class="badge-notificado" title="Correo de aprobación enviado">
                                                    <i class="fas fa-envelope"></i>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Botón para ver detalles -->
                                            <button class="btn-icon-action btn-view" 
                                                    onclick="verDetallesUsuario(<?php echo $idUsuario; ?>)"
                                                    title="Ver detalles del usuario">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- Botón para editar -->
                                            <button class="btn-icon-action btn-edit" 
                                                    onclick="editarUsuario(<?php echo $idUsuario; ?>)"
                                                    title="Editar usuario">
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

            <!-- Información de paginación -->
            <div class="table-info">
                <div class="total-users">
                    <span>
                        <?php 
                        if ($filtro === 'todos') {
                            echo "Total: {$contadores['total']} usuario(s)";
                        } elseif ($filtro === 'pendientes') {
                            echo "Pendientes de aprobación: {$contadores['inactivos']} usuario(s)";
                        } elseif ($filtro === 'activos') {
                            echo "Activos: {$contadores['activos']} usuario(s)";
                        } elseif ($filtro === 'inactivos') {
                            echo "Inactivos: {$contadores['inactivos']} usuario(s)";
                        }
                        ?>
                    </span>
                </div>
                <!-- Aquí podrías agregar paginación si es necesario -->
            </div>
        </main>

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

    <!-- JavaScript para funcionalidad -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Buscador de usuarios
        const searchInput = document.getElementById('search-users');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('.users-table tbody tr');
                
                rows.forEach(row => {
                    if (row.classList.contains('no-users')) return;
                    
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Función para cambiar estado
        async function cambiarEstadoUsuario(usuarioId, accion) {
            try {
                const response = await fetch('../../api/gestion_usuarios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        accion: accion,
                        id_usuario: usuarioId
                    })
                });
                
                return await response.json();
            } catch (error) {
                console.error('Error:', error);
                return { success: false, error: 'Error de conexión' };
            }
        }
        
        // Función para manejar clic en botones de acción
        function manejarAccionUsuario(button, accion, mensajeConfirmacion) {
            const userId = button.getAttribute('data-id');
            const userName = button.getAttribute('data-nombre');
            
            if (confirm(mensajeConfirmacion)) {
                // Mostrar indicador de carga
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;
                
                cambiarEstadoUsuario(userId, accion)
                    .then(data => {
                        if (data.success) {
                            // Mostrar mensaje de éxito
                            showNotification(data.message, 'success');
                            
                            // Recargar la página después de 1.5 segundos
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            throw new Error(data.error || 'Error al procesar la solicitud');
                        }
                    })
                    .catch(error => {
                        // Restaurar botón
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                        
                        // Mostrar error
                        showNotification(`Error: ${error.message}`, 'error');
                    });
            }
        }
        
        // Botón para aprobar usuario (primera aprobación)
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-approve');
            if (button) {
                e.preventDefault();
                const userName = button.getAttribute('data-nombre');
                const mensaje = `¿Está seguro que desea APROBAR al usuario "${userName}"?\n\nSe enviará un correo de notificación al contratista.`;
                manejarAccionUsuario(button, 'aprobar_usuario', mensaje);
            }
        });
        
        // Botón para activar usuario (reactivación)
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-activate');
            if (button) {
                e.preventDefault();
                const userName = button.getAttribute('data-nombre');
                const mensaje = `¿Activar al usuario "${userName}"?`;
                manejarAccionUsuario(button, 'activar_usuario', mensaje);
            }
        });
        
        // Botón para desactivar usuario
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-deactivate');
            if (button) {
                e.preventDefault();
                const userName = button.getAttribute('data-nombre');
                const mensaje = `¿Está seguro que desea DESACTIVAR al usuario "${userName}"?\n\nEl usuario NO podrá acceder al sistema.`;
                manejarAccionUsuario(button, 'desactivar_usuario', mensaje);
            }
        });
        
        // Botón para mantener pendiente
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-keep-pending');
            if (button) {
                e.preventDefault();
                const userName = button.getAttribute('data-nombre');
                const mensaje = `¿Mantener al usuario "${userName}" como pendiente?`;
                manejarAccionUsuario(button, 'mantener_pendiente', mensaje);
            }
        });
        
        // Botón "Añadir usuario"
        const addUserBtn = document.querySelector('.btn-add-user');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', function() {
                window.location.href = '../registrarusuario.php';
            });
        }
    });
    
    // Funciones auxiliares
    function verDetallesUsuario(idUsuario) {
        alert(`Ver detalles del usuario ID: ${idUsuario}\n\nEsta funcionalidad se implementará posteriormente.`);
    }
    
    function editarUsuario(idUsuario) {
        alert(`Editar usuario ID: ${idUsuario}\n\nEsta funcionalidad se implementará posteriormente.`);
    }
    
    // Función para mostrar notificaciones
    function showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;
        
        // Agregar estilos
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
            color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            max-width: 400px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
        `;
        
        // Estilos para el contenido
        const contentStyle = `
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        `;
        notification.querySelector('.notification-content').style.cssText = contentStyle;
        
        // Estilo para el botón de cerrar
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.style.cssText = `
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            margin-left: 10px;
            opacity: 0.7;
            transition: opacity 0.2s;
        `;
        
        // Animación
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .notification-close:hover { opacity: 1; }
        `;
        document.head.appendChild(style);
        
        // Agregar al documento
        document.body.appendChild(notification);
        
        // Cerrar al hacer clic en el botón
        closeBtn.addEventListener('click', () => {
            notification.style.animation = 'slideOut 0.3s ease';
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        });
        
        // Cerrar automáticamente después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOut 0.3s ease';
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
    </script>
</body>
</html>