<?php
class MunicipioModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerMunicipiosActivos() {
        $sql = "SELECT id_municipio, nombre FROM municipio 
                WHERE activo = true 
                ORDER BY nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id_municipio) {
        $sql = "SELECT * FROM municipio WHERE id_municipio = :id_municipio";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>