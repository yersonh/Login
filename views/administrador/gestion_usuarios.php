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

// Obtener año actual
$anio = date('Y');

// 3. Obtener usuarios de la base de datos
try {
    $database = new Database();
    $db = $database->conectar();
    $usuarioModel = new Usuario($db);
    $usuarios = $usuarioModel->obtenerTodos();
    $totalUsuarios = count($usuarios);
} catch (Exception $e) {
    error_log("Error al obtener usuarios: " . $e->getMessage());
    $usuarios = [];
    $totalUsuarios = 0;
}

// Función para formatear la fecha
function formatearFecha($fechaBD) {
    if (empty($fechaBD)) {
        return 'No registrada';
    }
    // Si la fecha ya está en formato d/m/Y, devolverla tal cual
    if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $fechaBD)) {
        return $fechaBD;
    }
    // Si es de MySQL (YYYY-MM-DD HH:MM:SS) o similar, formatear
    try {
        $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $fechaBD);
        if ($fecha) {
            return $fecha->format('d/m/Y');
        }
        // Intentar otros formatos
        $fecha = new DateTime($fechaBD);
        return $fecha->format('d/m/Y');
    } catch (Exception $e) {
        return $fechaBD;
    }
}

// Función para determinar el estado del usuario
function obtenerEstadoUsuario($activo) {
    if ($activo == 1 || $activo === true) {
        return ['texto' => 'Activo', 'clase' => 'status-active'];
    } else {
        return ['texto' => 'Inactivo', 'clase' => 'status-blocked'];
    }
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
        /* Estilos adicionales para estados */
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .no-users {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-color);
            font-style: italic;
        }
        
        .no-users i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
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
                <h1 class="page-title">Usuarios registrados</h1>
                <div class="page-controls">
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="search-users" placeholder="Buscar usuario por nombre, email...">
                    </div>
                    <button class="btn-add-user">
                        <i class="fas fa-user-plus"></i> Añadir usuario
                    </button>
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
                            <th>Estado usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="5" class="no-users">
                                    <i class="fas fa-users-slash"></i>
                                    <p>No hay usuarios registrados en el sistema</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <?php
                                $nombreCompletoUsuario = htmlspecialchars(trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')));
                                $correo = htmlspecialchars($usuario['correo'] ?? '');
                                $fechaRegistro = formatearFecha($usuario['fecha_registro'] ?? '');
                                $tipoUsuario = htmlspecialchars($usuario['tipo_usuario'] ?? 'contratista');
                                $estado = obtenerEstadoUsuario($usuario['activo'] ?? 0);
                                $idUsuario = $usuario['id_usuario'] ?? 0;
                                ?>
                                <tr>
                                    <td><?php echo $fechaRegistro; ?></td>
                                    <td><?php echo $nombreCompletoUsuario; ?></td>
                                    <td><?php echo $correo; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $estado['clase']; ?>">
                                            <?php echo $estado['texto']; ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <?php if ($usuario['activo'] == 1 || $usuario['activo'] === true): ?>
                                            <button class="btn-action btn-block" 
                                                    data-id="<?php echo $idUsuario; ?>"
                                                    data-nombre="<?php echo $nombreCompletoUsuario; ?>"
                                                    title="Bloquear usuario">
                                                <i class="fas fa-ban"></i> Bloquear
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-action btn-approve" 
                                                    data-id="<?php echo $idUsuario; ?>"
                                                    data-nombre="<?php echo $nombreCompletoUsuario; ?>"
                                                    title="Activar usuario">
                                                <i class="fas fa-check-circle"></i> Activar
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($tipoUsuario === 'contratista' && ($usuario['activo'] == 1 || $usuario['activo'] === true)): ?>
                                            <!-- Aquí podrías agregar más acciones si las necesitas -->
                                        <?php endif; ?>
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
                    <span>Total: <?php echo $totalUsuarios; ?> usuario(s)</span>
                </div>
                <div class="pagination">
                    <button class="btn-pagination" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="current-page">Página 1</span>
                    <button class="btn-pagination">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
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

    <!-- JavaScript para funcionalidad básica -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Buscador de usuarios
            const searchInput = document.getElementById('search-users');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('.users-table tbody tr');
                    
                    rows.forEach(row => {
                        // Verificar que no sea la fila de "no hay usuarios"
                        if (row.classList.contains('no-users-row')) return;
                        
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
            
            // Botones de acción (activar/bloquear)
            const actionButtons = document.querySelectorAll('.btn-action');
            actionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const userName = this.getAttribute('data-nombre');
                    const isActivate = this.classList.contains('btn-approve');
                    const action = isActivate ? 'activar' : 'bloquear';
                    
                    if (confirm(`¿Está seguro que desea ${action} al usuario "${userName}"?`)) {
                        // Aquí iría la llamada AJAX para realizar la acción
                        console.log(`${action.toUpperCase()} usuario ID: ${userId}`);
                        
                        // Por ahora, solo recargamos la página
                        // window.location.reload();
                        
                        // En producción, harías una llamada AJAX
                        // fetch(`/api/usuarios/${action}/${userId}`, { method: 'POST' })
                        // .then(response => response.json())
                        // .then(data => {
                        //     if (data.success) {
                        //         window.location.reload();
                        //     } else {
                        //         alert('Error: ' + data.error);
                        //     }
                        // });
                        
                        alert(`Funcionalidad de ${action} usuario aún no implementada.`);
                    }
                });
            });
            
            // Botón "Añadir usuario"
            const addUserBtn = document.querySelector('.btn-add-user');
            if (addUserBtn) {
                addUserBtn.addEventListener('click', function() {
                    alert('Funcionalidad de añadir usuario aún no implementada.');
                });
            }
        });
    </script>
</body>
</html>