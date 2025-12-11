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
        $query = "SELECT * FROM " . $this->table . " WHERE id_parametrizacion = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
                    enlace_web = :enlace, 
                    texto_alternativo = :texto_alt,
                    updated_at = NOW()
                  WHERE id_parametrizacion = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':version', $datos['version_sistema']);
        $stmt->bindParam(':licencia', $datos['tipo_licencia']);
        $stmt->bindParam(':valida', $datos['valida_hasta']);
        $stmt->bindParam(':dev', $datos['desarrollado_por']);
        $stmt->bindParam(':dir', $datos['direccion']);
        $stmt->bindParam(':email', $datos['correo_contacto']);
        $stmt->bindParam(':tel', $datos['telefono']);
        $stmt->bindParam(':logo', $datos['ruta_logo']);
        $stmt->bindParam(':enlace', $datos['enlace_web']); 
        $stmt->bindParam(':texto_alt', $datos['texto_alternativo']);

        return $stmt->execute();
    }

    // Método específico para actualizar solo el logo
    public function actualizarLogo($rutaLogo, $textoAlt, $enlace) {
        $query = "UPDATE " . $this->table . " SET 
                    ruta_logo = :logo,
                    texto_alternativo = :texto_alt,
                    enlace_web = :enlace,
                    updated_at = NOW()
                  WHERE id_parametrizacion = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':logo', $rutaLogo);
        $stmt->bindParam(':texto_alt', $textoAlt);
        $stmt->bindParam(':enlace', $enlace);

        return $stmt->execute();
    }
}
?>