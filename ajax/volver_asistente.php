<?php
// ajax/volver_asistente.php - Volver a la sesión original del asistente

session_start();

// Verificar que existe un usuario original guardado
if (!isset($_SESSION['usuario_original'])) {
    // Si no hay usuario original, redirigir al logout
    header("Location: ../views/logout.php");
    exit();
}

// Obtener datos del usuario original (asistente)
$usuarioOriginal = $_SESSION['usuario_original'];

// Registrar el cierre de sesión de administrador
error_log("SESIÓN CAMBIADA: Admin " . $_SESSION['correo'] . 
          " vuelve a ser Asistente " . $usuarioOriginal['correo']);

// Restaurar datos del asistente
$_SESSION['usuario_id'] = $usuarioOriginal['id_usuario'];
$_SESSION['nombres'] = $usuarioOriginal['nombres'];
$_SESSION['apellidos'] = $usuarioOriginal['apellidos'];
$_SESSION['correo'] = $usuarioOriginal['correo'];
$_SESSION['tipo_usuario'] = $usuarioOriginal['tipo_usuario'];

// Limpiar datos del cambio
unset($_SESSION['usuario_original']);
unset($_SESSION['admin_login_timestamp']);
unset($_SESSION['admin_login_method']);

// Regenerar ID de sesión por seguridad
session_regenerate_id(true);

// Redirigir al menú del asistente
header("Location: ../views/menuAsistente.php");
exit();
?>