<?php
class Persona {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ================= MÉTODOS BÁSICOS =================
    
    /**
     * Insertar nueva persona (actualizado con profesion)
     */
    public function insertar($cedula, $nombres, $apellidos, $telefono, $correo_personal = null, $profesion = null) {
        $sql = "INSERT INTO persona (cedula, nombres, apellidos, telefono, correo_personal, profesion)
                VALUES (:cedula, :nombres, :apellidos, :telefono, :correo_personal, :profesion)
                RETURNING id_persona";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':nombres', $nombres);
        $stmt->bindParam(':apellidos', $apellidos);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':correo_personal', $correo_personal);
        $stmt->bindParam(':profesion', $profesion);

        try {
            $stmt->execute();
            $row = $stmt->fetch();
            return $row['id_persona'];
        } catch (PDOException $e) {
            error_log("Error al insertar persona: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insertar persona con todos los campos
     */
    public function insertarCompleto($cedula, $nombres, $apellidos, $telefono, $correo_personal = null, $profesion = null) {
        return $this->insertar($cedula, $nombres, $apellidos, $telefono, $correo_personal, $profesion);
    }

    // ================= MÉTODOS PARA FOTOS (TABLA SEPARADA) =================
    
    /**
     * Guardar foto de perfil en la tabla fotos_perfil
     */
    public function guardarFotoPerfil($id_persona, $archivoFoto) {
    // Validar que sea una imagen
    if (!isset($archivoFoto['error']) || $archivoFoto['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Leer el archivo como binario
    $contenido = file_get_contents($archivoFoto['tmp_name']);
    $nombre = basename($archivoFoto['name']);
    $tipo = $archivoFoto['type'];
    $tamanoArchivo = $archivoFoto['size']; // Solo para validación

    // Validar tipo de imagen
    $tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($tipo, $tiposPermitidos)) {
        error_log("Tipo de imagen no permitido: $tipo");
        return false;
    }

    // Validar tamaño (máximo 10MB - ajustable)
    if ($tamanoArchivo > 10 * 1024 * 1024) {
        error_log("Imagen demasiado grande: $tamanoArchivo bytes");
        return false;
    }

    try {
        // Primero eliminar foto anterior si existe
        $this->eliminarFotoPerfil($id_persona);

        // INSERT correcto según la estructura de la tabla
        $sql = "INSERT INTO fotos_perfil 
                (id_persona, nombre_archivo, tipo_mime, contenido)
                VALUES (:id_persona, :nombre, :tipo, :contenido)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':contenido', $contenido, PDO::PARAM_LOB);
        
        return $stmt->execute();

    } catch (PDOException $e) {
        error_log("Error al guardar foto de perfil: " . $e->getMessage());
        return false;
    }
}

    /**
     * Obtener la foto de perfil de una persona
     */
    public function obtenerFotoPerfil($id_persona) {
    try {
        $sql = "SELECT id_foto, nombre_archivo, tipo_mime, contenido, fecha_subida
                FROM fotos_perfil 
                WHERE id_persona = :id_persona 
                ORDER BY fecha_subida DESC 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error al obtener foto de perfil: " . $e->getMessage());
        return false;
    }
}

    /**
     * Verificar si una persona tiene foto de perfil
     */
    public function tieneFotoPerfil($id_persona) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM fotos_perfil 
                    WHERE id_persona = :id_persona";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_persona', $id_persona);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;

        } catch (PDOException $e) {
            error_log("Error al verificar foto de perfil: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar foto de perfil de una persona
     */
    public function eliminarFotoPerfil($id_persona) {
        try {
            $sql = "DELETE FROM fotos_perfil WHERE id_persona = :id_persona";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_persona', $id_persona);
            return $stmt->execute();

        } catch (PDOException $e) {
            error_log("Error al eliminar foto de perfil: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener datos básicos de persona junto con info de foto
     */
    public function obtenerConFoto($id_persona) {
    try {
        // Datos básicos de persona
        $sql = "SELECT p.* 
                FROM persona p
                WHERE p.id_persona = :id_persona";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->execute();
        $persona = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$persona) {
            return false;
        }

        // Agregar info de foto si existe
        $persona['tiene_foto'] = $this->tieneFotoPerfil($id_persona);
        
        if ($persona['tiene_foto']) {
            $foto = $this->obtenerFotoPerfil($id_persona);
            $persona['foto_info'] = [
                'id_foto' => $foto['id_foto'],
                'nombre_archivo' => $foto['nombre_archivo'],
                'tipo_mime' => $foto['tipo_mime'],
                'fecha_subida' => $foto['fecha_subida']
                // Nota: no incluye tamano porque no existe en la tabla
            ];
        }

        return $persona;

    } catch (PDOException $e) {
        error_log("Error al obtener persona con foto: " . $e->getMessage());
        return false;
    }
}
    // ================= MÉTODOS ADICIONALES ÚTILES =================

    /**
     * Buscar persona por cédula (incluye profesion)
     */
    public function buscarPorCedula($cedula) {
        try {
            $sql = "SELECT * FROM persona WHERE cedula = :cedula";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':cedula', $cedula);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al buscar persona por cédula: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar persona con todos los campos (incluyendo profesion)
     */
    public function actualizar($id_persona, $datos) {
        try {
            $campos = [];
            $valores = [];
            
            foreach ($datos as $campo => $valor) {
                if ($campo !== 'id_persona') {
                    $campos[] = "$campo = :$campo";
                    $valores[":$campo"] = $valor;
                }
            }
            
            $sql = "UPDATE persona SET " . implode(', ', $campos) . 
                   " WHERE id_persona = :id_persona";
            
            $valores[':id_persona'] = $id_persona;
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($valores);
            
        } catch (PDOException $e) {
            error_log("Error al actualizar persona: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar profesion específicamente
     */
    public function actualizarProfesion($id_persona, $profesion) {
        try {
            $sql = "UPDATE persona SET profesion = :profesion WHERE id_persona = :id_persona";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':profesion', $profesion);
            $stmt->bindParam(':id_persona', $id_persona);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar profesion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar persona (con cascade para fotos)
     */
    public function eliminar($id_persona) {
        try {
            // Las fotos se eliminarán automáticamente por ON DELETE CASCADE
            $sql = "DELETE FROM persona WHERE id_persona = :id_persona";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_persona', $id_persona);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar persona: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todas las personas con información básica
     */
    public function obtenerTodos($limit = null, $offset = 0) {
        try {
            $sql = "SELECT id_persona, cedula, nombres, apellidos, telefono, 
                           correo_personal, profesion, fecha_registro
                    FROM persona 
                    ORDER BY fecha_registro DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->conn->prepare($sql);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener todas las personas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar total de personas
     */
    public function contarTotal() {
        try {
            $sql = "SELECT COUNT(*) as total FROM persona";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error al contar personas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar personas por nombre, cédula o profesión
     */
    public function buscar($termino) {
        try {
            $sql = "SELECT id_persona, cedula, nombres, apellidos, telefono, 
                           correo_personal, profesion
                    FROM persona 
                    WHERE LOWER(nombres) LIKE LOWER(:termino) 
                       OR LOWER(apellidos) LIKE LOWER(:termino)
                       OR LOWER(profesion) LIKE LOWER(:termino)
                       OR cedula LIKE :termino
                    ORDER BY nombres, apellidos";
            
            $stmt = $this->conn->prepare($sql);
            $terminoBusqueda = "%" . $termino . "%";
            $stmt->bindParam(':termino', $terminoBusqueda);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al buscar personas: " . $e->getMessage());
            return false;
        }
    }
}
?>