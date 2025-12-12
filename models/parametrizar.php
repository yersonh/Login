<?php
require_once __DIR__ . '/../config/database.php';

class Configuracion {
    private $conn;
    private $table = 'parametrizar';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }

    public function obtenerConfiguracionMasReciente() {
    $query = "SELECT * FROM " . $this->table . " 
              ORDER BY id_parametrizacion DESC 
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
}
?>