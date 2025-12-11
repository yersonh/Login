<?php
class AreaModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerAreasActivas() {
        $sql = "SELECT id_area, nombre FROM area 
                WHERE activo = true 
                ORDER BY nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id_area) {
        $sql = "SELECT * FROM area WHERE id_area = :id_area";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_area', $id_area);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>