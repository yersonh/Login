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
              ORDER BY created_at DESC 
              LIMIT 1";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    public function insertarConfiguracion($datos) {
    $query = "INSERT INTO " . $this->table . " (
                version_sistema, tipo_licencia, valida_hasta,
                desarrollado_por, direccion, correo_contacto,
                telefono, ruta_logo, enlace_web, entidad,
                created_at, updated_at
              ) VALUES (
                :version, :licencia, :valida,
                :dev, :dir, :email,
                :tel, :logo, :enlace, :entidad,
                NOW(), NOW()
              )";

    $stmt = $this->conn->prepare($query);
    
    // Vinculación de todos los parámetros (similar a actualizar)
    $stmt->bindParam(':version', $datos['version_sistema']);
    $stmt->bindParam(':licencia', $datos['tipo_licencia']);
    $stmt->bindParam(':valida', $datos['valida_hasta']);
    $stmt->bindParam(':dev', $datos['desarrollado_por']);
    $stmt->bindParam(':dir', $datos['direccion']);
    $stmt->bindParam(':email', $datos['correo_contacto']);
    $stmt->bindParam(':tel', $datos['telefono']);
    $stmt->bindParam(':logo', $datos['ruta_logo']);
    $stmt->bindParam(':enlace', $datos['enlace_web']); 
    $stmt->bindParam(':entidad', $datos['entidad']);

    return $stmt->execute();
}
    public function actualizarLogo($rutaLogo, $entidad, $enlace) {
        $query = "UPDATE " . $this->table . " SET 
                    ruta_logo = :logo,
                    entidad = :entidad,
                    enlace_web = :enlace,
                    updated_at = NOW()
                  WHERE id_parametrizacion = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':logo', $rutaLogo);
        $stmt->bindParam(':entidad', $entidad);
        $stmt->bindParam(':enlace', $enlace);

        return $stmt->execute();
    }
}
?>