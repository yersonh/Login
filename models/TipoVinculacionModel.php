    <?php
    require_once __DIR__ . '/../config/database.php';
    class TipoVinculacionModel {
        private $conn;

        public function __construct() {
            $database = new Database();
            $this->conn = $database->conectar();
        }

        public function obtenerTodosTipos() {
            $sql = "SELECT id_tipo, nombre, codigo, descripcion, activo 
                    FROM tipo_vinculacion 
                    ORDER BY id_tipo ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function obtenerTiposActivos() {
            $sql = "SELECT id_tipo, nombre FROM tipo_vinculacion 
                    WHERE activo = true 
                    ORDER BY id_tipo ASC";
            
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

        public function crearTipo($data) {
            $sql = "INSERT INTO tipo_vinculacion (nombre, codigo, descripcion, activo) 
                    VALUES (:nombre, :codigo, :descripcion, :activo) 
                    RETURNING id_tipo";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':codigo', $data['codigo']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            $stmt->bindParam(':activo', $data['activo'], PDO::PARAM_BOOL);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['id_tipo'];
        }

        public function actualizarTipo($id_tipo, $data) {
            $sql = "UPDATE tipo_vinculacion 
                    SET nombre = :nombre, 
                        codigo = :codigo, 
                        descripcion = :descripcion
                    WHERE id_tipo = :id_tipo";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':codigo', $data['codigo']);
            $stmt->bindParam(':descripcion', $data['descripcion']);
            
            return $stmt->execute();
        }

        public function cambiarEstadoTipo($id_tipo, $activo) {
            $sql = "UPDATE tipo_vinculacion 
                    SET activo = :activo 
                    WHERE id_tipo = :id_tipo";
            
            $stmt = $this->conn->prepare($sql);
            
            // Convertir a booleano explícitamente
            $activo_bool = filter_var($activo, FILTER_VALIDATE_BOOLEAN);
            
            // Usar PDO::PARAM_BOOL para especificar el tipo de dato
            $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            $stmt->bindParam(':activo', $activo_bool, PDO::PARAM_BOOL);
            
            return $stmt->execute();
        }

        public function buscarTipos($termino) {
            $sql = "SELECT id_tipo, nombre, codigo, descripcion, activo 
                    FROM tipo_vinculacion 
                    WHERE nombre ILIKE :termino 
                    OR codigo ILIKE :termino 
                    OR descripcion ILIKE :termino
                    ORDER BY nombre";
            
            $stmt = $this->conn->prepare($sql);
            $terminoBusqueda = "%" . $termino . "%";
            $stmt->bindParam(':termino', $terminoBusqueda);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function existeCodigo($codigo, $id_tipo_excluir = null) {
            $sql = "SELECT COUNT(*) FROM tipo_vinculacion 
                    WHERE codigo = :codigo";
            
            if ($id_tipo_excluir) {
                $sql .= " AND id_tipo != :id_tipo_excluir";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':codigo', $codigo);
            
            if ($id_tipo_excluir) {
                $stmt->bindParam(':id_tipo_excluir', $id_tipo_excluir);
            }
            
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        }
        public function eliminarTipo($id_tipo) {
            // Primero verificar si el tipo está siendo usado en otras tablas
            $sqlCheck = "SELECT COUNT(*) as count FROM contratistas WHERE id_tipo_vinculacion = :id_tipo";
            $stmtCheck = $this->conn->prepare($sqlCheck);
            $stmtCheck->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            $stmtCheck->execute();
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                throw new Exception("No se puede eliminar el tipo de vinculación porque está siendo utilizado por contratistas");
            }
            
            // Si no hay dependencias, eliminar físicamente
            $sql = "DELETE FROM tipo_vinculacion WHERE id_tipo = :id_tipo";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            
            return $stmt->execute();
        }
    public function verificarDependencias($id_tipo) {
            $dependencias = [];
            
            // Verificar contratistas
            $sql = "SELECT COUNT(*) as count FROM contratistas WHERE id_tipo_vinculacion = :id_tipo";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $dependencias['contratistas'] = $result['count'];
            
            return $dependencias;
        }
    }
    ?>