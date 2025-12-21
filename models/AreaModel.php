<?php
require_once __DIR__ . '/../config/database.php';
class AreaModel {
    private $conn;


    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }

    public function obtenerTodasAreas() {
        $sql = "SELECT id_area, nombre, codigo_area, descripcion, activo 
                FROM area 
                ORDER BY id_area ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAreasActivas() {
        $sql = "SELECT id_area, nombre FROM area 
                WHERE activo = true 
                ORDER BY id_area ASC";
        
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

    public function crearArea($data) {
        $sql = "INSERT INTO area (nombre, codigo_area, descripcion, activo) 
                VALUES (:nombre, :codigo_area, :descripcion, :activo) 
                RETURNING id_area";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':codigo_area', $data['codigo_area']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        $stmt->bindParam(':activo', $data['activo']);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['id_area'];
    }
    public function actualizarArea($id_area, $data) {
        $sql = "UPDATE area 
                SET nombre = :nombre, 
                    codigo_area = :codigo_area, 
                    descripcion = :descripcion
                WHERE id_area = :id_area";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_area', $id_area);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':codigo_area', $data['codigo_area']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        
        return $stmt->execute();
    }

    public function cambiarEstadoArea($id_area, $activo) {
        $sql = "UPDATE area 
                SET activo = :activo 
                WHERE id_area = :id_area";
        
        $stmt = $this->conn->prepare($sql);
        
        // Convertir a booleano explícitamente
        $activo_bool = filter_var($activo, FILTER_VALIDATE_BOOLEAN);
        
        // Usar PDO::PARAM_BOOL para especificar el tipo de dato
        $stmt->bindParam(':id_area', $id_area, PDO::PARAM_INT);
        $stmt->bindParam(':activo', $activo_bool, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    }

    public function buscarAreas($termino) {
        $sql = "SELECT id_area, nombre, codigo_area, descripcion, activo 
                FROM area 
                WHERE nombre ILIKE :termino 
                   OR codigo_area ILIKE :termino 
                   OR descripcion ILIKE :termino
                ORDER BY nombre";
        
        $stmt = $this->conn->prepare($sql);
        $terminoBusqueda = "%" . $termino . "%";
        $stmt->bindParam(':termino', $terminoBusqueda);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function existeCodigoArea($codigo_area, $id_area_excluir = null) {
        $sql = "SELECT COUNT(*) FROM area 
                WHERE codigo_area = :codigo_area";
        
        if ($id_area_excluir) {
            $sql .= " AND id_area != :id_area_excluir";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':codigo_area', $codigo_area);
        
        if ($id_area_excluir) {
            $stmt->bindParam(':id_area_excluir', $id_area_excluir);
        }
        
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
}
?>