<?php
require_once __DIR__ . '/../config/database.php';

class Configuracion {
    private $conn;
    private $table = 'parametrizar';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }

    // Obtener los datos actuales
    public function obtenerConfiguracion() {
        // Siempre traemos el ID 1
        $query = "SELECT * FROM " . $this->table . " WHERE id = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar los datos
    public function actualizarConfiguracion($datos) {
        $query = "UPDATE " . $this->table . " SET 
                    version_sistema = :version,
                    tipo_licencia = :licencia,
                    valida_hasta = :valida,
                    desarrollado_por = :dev,
                    direccion = :dir,
                    correo_contacto = :email,
                    telefono = :tel,
                    ruta_logo = :logo,
                    updated_at = NOW()
                  WHERE id = 1";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':version', $datos['version_sistema']);
        $stmt->bindParam(':licencia', $datos['tipo_licencia']);
        $stmt->bindParam(':valida', $datos['valida_hasta']);
        $stmt->bindParam(':dev', $datos['desarrollado_por']);
        $stmt->bindParam(':dir', $datos['direccion']);
        $stmt->bindParam(':email', $datos['correo_contacto']);
        $stmt->bindParam(':tel', $datos['telefono']);
        $stmt->bindParam(':logo', $datos['ruta_logo']);

        return $stmt->execute();
    }
}
?>