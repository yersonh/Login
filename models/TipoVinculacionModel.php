<?php
class TipoVinculacionModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTiposActivos() {
        $sql = "SELECT id_tipo, nombre FROM tipo_vinculacion 
                WHERE activo = true 
                ORDER BY nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id_tipo) {
        $sql = "SELECT * FROM tipo_vinculacion WHERE id_tipo = :id_tipo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_tipo', $id_tipo);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>