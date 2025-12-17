<?php
require_once __DIR__ . '/../config/database.php';
class MunicipioModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }
    
    public function obtenerTodosMunicipios() {
        $sql = "SELECT id_municipio, nombre, departamento, activo 
                FROM municipio 
                ORDER BY nombre";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    public function buscarPorNombre($nombre) {
        $sql = "SELECT * FROM municipio WHERE nombre ILIKE :nombre AND activo = true";
        $stmt = $this->conn->prepare($sql);
        $busqueda = "%" . $nombre . "%";
        $stmt->bindParam(':nombre', $busqueda);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function crearMunicipio($nombre, $departamento, $activo) {
        $sql = "INSERT INTO municipio (nombre, departamento, activo) 
                VALUES (:nombre, :departamento, :activo) 
                RETURNING id_municipio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':departamento', $departamento);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC)['id_municipio'];
        }
        
        return false;
    }

    public function actualizarMunicipio($id_municipio, $nombre, $departamento, $activo) {
        $sql = "UPDATE municipio 
                SET nombre = :nombre, 
                    departamento = :departamento, 
                    activo = :activo
                WHERE id_municipio = :id_municipio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':departamento', $departamento);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    public function eliminarMunicipio($id_municipio) {
        $sql = "UPDATE municipio 
                SET activo = false
                WHERE id_municipio = :id_municipio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function existeMunicipio($nombre, $excluir_id = null) {
        $sql = "SELECT COUNT(*) FROM municipio 
                WHERE nombre = :nombre";
        
        if ($excluir_id) {
            $sql .= " AND id_municipio != :excluir_id";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        
        if ($excluir_id) {
            $stmt->bindParam(':excluir_id', $excluir_id, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    public function cambiarEstado($id_municipio, $activo) {
        $sql = "UPDATE municipio 
                SET activo = :activo
                WHERE id_municipio = :id_municipio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }
}
?>