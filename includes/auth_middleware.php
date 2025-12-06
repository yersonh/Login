<?php
function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../../index.php");
        exit();
    }
    
    $tipoUsuario = $_SESSION['tipo_usuario'] ?? '';
    
    if ($tipoUsuario !== 'administrador') {
        // Registrar intento de acceso no autorizado
        error_log("ACCESO DENEGADO - Ruta admin accedida por: " . 
                 ($_SESSION['correo'] ?? 'Desconocido') . 
                 " (Rol: " . $tipoUsuario . ")");
        
        // Redirigir según rol
        if ($tipoUsuario === 'asistente') {
            header("Location: ../menuAsistente.php");
        } else {
            header("Location: ../menu.php");
        }
        exit();
    }
}

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../../index.php");
        exit();
    }
}

function getCurrentUserRole() {
    return $_SESSION['tipo_usuario'] ?? null;
}

function puedeAcceder($ruta) {
    $rol = getCurrentUserRole();
    
    $permisosPorRuta = [
        '/views/manage/parametrizacion.php' => ['administrador'],
        '/views/menu.php' => ['administrador', 'usuario'],
        '/views/menuAsistente.php' => ['asistente']
    ];
    
    return isset($permisosPorRuta[$ruta]) && 
           in_array($rol, $permisosPorRuta[$ruta]);
}
?>