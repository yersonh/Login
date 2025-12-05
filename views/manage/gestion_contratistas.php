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
    <title>Gestionar Contratistas</title>
</head>
<body>
    <h1>Gestionar Contratistas</h1>
    <p>Página en construcción...</p>
    <a href="menuadmin.php">Volver al Panel</a>
</body>
</html>