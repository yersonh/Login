<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Menú Principal - Usuario</title>
</head>
<body>
    <h1>Bienvenido Usuario: <?php echo htmlspecialchars($_SESSION['nombres']); ?></h1>
    <p>Correo: <?php echo htmlspecialchars($_SESSION['correo']); ?></p>
    <p>Rol: <?php echo htmlspecialchars($_SESSION['tipo_usuario']); ?></p>
    
    <h2>Opciones de Usuario:</h2>
    <ul>
        <li><a href="perfil.php">Mi Perfil</a></li>
        <li><a href="solicitudes.php">Mis Solicitudes</a></li>
        <li><a href="reportes.php">Generar Reportes</a></li>
        <li><a href="logout.php">Cerrar Sesión</a></li>
    </ul>
</body>
</html>