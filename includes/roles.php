<?php
class SistemaRoles {
    const ADMIN = 'administrador';
    const ASISTENTE = 'asistente';
    const USUARIO = 'usuario';
    
    public static function esAdministrador() {
        return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === self::ADMIN;
    }
    
    public static function esAsistente() {
        return isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === self::ASISTENTE;
    }
    
    public static function tienePermiso($permisoRequerido) {
        if (!isset($_SESSION['permisos'])) {
            return false;
        }
        
        return in_array($permisoRequerido, $_SESSION['permisos']);
    }
    
    public static function redirigirSegunRol() {
        if (!isset($_SESSION['tipo_usuario'])) {
            header("Location: ../index.php");
            exit();
        }
        
        switch ($_SESSION['tipo_usuario']) {
            case self::ADMIN:
                header("Location: ../views/menu.php");
                break;
            case self::ASISTENTE:
                header("Location: ../views/menuAsistente.php");
                break;
            default:
                header("Location: ../views/menu.php");
        }
        exit();
    }
}
?>