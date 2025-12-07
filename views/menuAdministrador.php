<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Verificar que sea administrador
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

// Datos del usuario
$nombreUsuario = $_SESSION['nombres'] ?? '';
$apellidoUsuario = $_SESSION['apellidos'] ?? '';
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
if (empty($nombreCompleto)) {
    $nombreCompleto = 'Administrador del Sistema';
}

$correoUsuario = $_SESSION['correo'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Secretaría de Minas y Energía</title>
    <link rel="icon" href="../imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="../imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/admin.css">
</head>
<body>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Logo Institucional -->
            <div class="logo-section">
                <img src="../imagenes/logo.png" alt="Logo Gobernación del Meta" class="admin-logo">
                <div class="department-name">GOBERNACIÓN DEL META</div>
                <div class="department-subtitle">Secretaría de Minas y Energía</div>
            </div>

            <!-- Perfil del Usuario -->
            <div class="user-profile">
                <div class="user-info">
                    <div class="user-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></div>
                    <div class="user-role">Administrador Principal</div>
                    <div class="user-email">
                        <?php echo htmlspecialchars($correoUsuario); ?>
                    </div>
                </div>
            </div>

            <!-- Navegación - DOS MÓDULOS -->
            <div class="nav-container">
                <div class="nav-title">Administración del Sistema</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="parametrizar.php" class="nav-link active" id="parametrizacion-link">
                            <span class="nav-icon"><i class="fas fa-sliders-h"></i></span>
                            <span class="nav-text">Parametrización</span>
                            <span class="nav-badge">Nuevo</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="gestion_usuarios.php" class="nav-link" id="usuarios-link">
                            <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                            <span class="nav-text">Gestión de Usuarios</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Logout -->
            <div class="logout-section">
                <a href="../logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Encabezado -->
            <div class="main-header">
                <h1 class="welcome-title">Panel de Control Administrativo</h1>
                <p class="welcome-subtitle">Seleccione una opción del menú lateral para comenzar</p>
            </div>

            <!-- Contenido de Bienvenida -->
            <div class="welcome-content">
                <div class="welcome-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2 class="welcome-heading">Bienvenido al Panel Administrativo</h2>
                <div class="welcome-message">
                    Desde este panel podrá gestionar la parametrización del sistema y la administración de usuarios. 
                    Utilice el menú lateral para acceder a las diferentes funcionalidades disponibles.
                </div>
            </div>
        </main>
        
        <!-- Botón flotante para volver como asistente -->
        <?php if (isset($_SESSION['usuario_original'])): ?>
        <button class="return-assistant-btn" id="return-assistant-btn">
            <i class="fas fa-exchange-alt"></i>
            Volver como Asistente
        </button>
        <?php endif; ?>
    </div>

    <script src="../javascript/admin.js"></script>
</body>
</html>