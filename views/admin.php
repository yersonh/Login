<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel de Administración</title>
</head>
<body>
    <h1>Panel de Administración</h1>
    <p>Bienvenido Administrador: <?php echo htmlspecialchars($_SESSION['nombres']); ?></p>
    <p>Correo: <?php echo htmlspecialchars($_SESSION['correo']); ?></p>
    
    <h2>Opciones de Administrador:</h2>
    <ul>
        <li><a href="gestion_usuarios.php">Gestión de Usuarios</a></li>
        <li><a href="configuracion_sistema.php">Configuración del Sistema</a></li>
        <li><a href="reportes_admin.php">Reportes Administrativos</a></li>
        <li><a href="auditoria.php">Auditoría del Sistema</a></li>
        <li><a href="perfil.php">Mi Perfil</a></li>
        <li><a href="logout.php">Cerrar Sesión</a></li>
    </ul>
    
    <h3>Accesos Rápidos:</h3>
    <button onclick="window.location.href='crear_usuario.php'">Crear Nuevo Usuario</button>
    <button onclick="window.location.href='backup.php'">Respaldar Sistema</button>
    <button onclick="window.location.href='logs.php'">Ver Logs del Sistema</button>
</body>
</html>