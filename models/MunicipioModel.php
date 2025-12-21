<?php
require_once __DIR__ . '/../config/database.php';
class MunicipioModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }
    
    public function obtenerTodosMunicipios() {
        $sql = "SELECT id_municipio, nombre, departamento, activo, codigo_dane 
                FROM municipio 
                ORDER BY id_municipio ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function obtenerMunicipiosActivos() {
        $sql = "SELECT id_municipio, nombre FROM municipio 
                WHERE activo = true 
                ORDER BY id_municipio ASC";
        
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
    
    public function crearMunicipio($nombre, $departamento, $activo, $codigo_dane = null) {
        $sql = "INSERT INTO municipio (nombre, departamento, activo, codigo_dane) 
                VALUES (:nombre, :departamento, :activo, :codigo_dane) 
                RETURNING id_municipio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':departamento', $departamento);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':codigo_dane', $codigo_dane);
        
        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC)['id_municipio'];
        }
        
        return false;
    }

    public function actualizarMunicipio($id_municipio, $nombre, $departamento, $activo, $codigo_dane = null) {
        $sql = "UPDATE municipio 
                SET nombre = :nombre, 
                    departamento = :departamento, 
                    activo = :activo,
                    codigo_dane = :codigo_dane
                WHERE id_municipio = :id_municipio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':departamento', $departamento);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':codigo_dane', $codigo_dane);
        
        return $stmt->execute();
    }
    public function cambiarEstadoMunicipio($id_municipio, $activo) {
        $sql = "UPDATE municipio 
                SET activo = :activo
                WHERE id_municipio = :id_municipio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        
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
    public function eliminarMunicipio($id_municipio) {
        // Primero verificar si el municipio está siendo usado en otras tablas
        $sqlCheck = "SELECT COUNT(*) as count FROM contratistas WHERE id_municipio = :id_municipio";
        $stmtCheck = $this->conn->prepare($sqlCheck);
        $stmtCheck->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        $stmtCheck->execute();
        $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            throw new Exception("No se puede eliminar el municipio porque está siendo utilizado por contratistas");
        }
        
        // Si no hay dependencias, eliminar físicamente
        $sql = "DELETE FROM municipio WHERE id_municipio = :id_municipio";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    public function verificarDependencias($id_municipio) {
        $dependencias = [];
        
        // Verificar contratistas
        $sql = "SELECT COUNT(*) as count FROM contratistas WHERE id_municipio = :id_municipio";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_municipio', $id_municipio, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $dependencias['contratistas'] = $result['count'];
        
        return $dependencias;
    }
}
?>