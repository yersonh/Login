<?php
require_once __DIR__ . '/../config/database.php';

class Configuracion {
    private $conn;
    private $table = 'parametrizar';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }

    public function obtenerConfiguracion() {
    $query = "SELECT * FROM " . $this->table . " 
                ORDER BY id_parametrizacion DESC 
                LIMIT 1";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function actualizarLogoYDatos(string $rutaLogo, string $entidad, string $nit, string $enlaceWeb): bool {
        
        $query = "UPDATE " . $this->table . " SET 
                    ruta_logo = :logo, 
                    entidad = :entidad, 
                    nit = 'nit',
                    enlace_web = :enlace, 
                    updated_at = NOW() 
                  WHERE id_parametrizacion = 1"; // Asume que la configuración a modificar es el ID 1

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':logo', $rutaLogo);
        $stmt->bindParam(':entidad', $entidad);
        $stmt->bindParam(':nit', $nit);
        $stmt->bindParam(':enlace', $enlaceWeb);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage()); 
            return false;
        }
    }
    public function actualizarDatos(string $version, string $tipo_licencia, string $valida_hasta, string $desarrollado_por, string $direccion, string $correo_contacto, string $telefono): bool {
        $query = "UPDATE " . $this->table . " SET 
                    version_sistema = :version,
                    tipo_licencia = :tipo_licencia,
                    valida_hasta = :valida_hasta,
                    desarrollado_por = :desarrollado_por,
                    direccion = :direccion, 
                    correo_contacto = :correo_contacto, 
                    telefono = :telefono, 
                    updated_at = NOW() 
                  WHERE id_parametrizacion = 1"; // Asume que la configuración a modificar es el ID 1

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':version', $version);
        $stmt->bindParam(':tipo_licencia', $tipo_licencia);
        $stmt->bindParam(':valida_hasta', $valida_hasta);
        $stmt->bindParam(':desarrollado_por', $desarrollado_por);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':correo_contacto', $correo_contacto);
        $stmt->bindParam(':telefono', $telefono);

        try {
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log($e->getMessage()); 
            return false;
        }
    }
}
?>