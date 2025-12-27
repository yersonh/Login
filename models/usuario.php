<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function insertar($id_persona, $correo, $password, $tipo_usuario = 'contratista', $activo = 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $fecha_registro = date('Y-m-d H:i:s');

        $sql = "INSERT INTO usuario (id_persona, correo, contrasena, tipo_usuario, activo, fecha_registro)
                VALUES (:id_persona, :correo, :password, :tipo_usuario, :activo, :fecha_registro)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':fecha_registro', $fecha_registro);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al insertar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function existeCorreo($correo) {
        $sql = "SELECT COUNT(*) FROM usuario WHERE correo = :correo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);

        try {
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar correo: " . $e->getMessage());
            return true;
        }
    }

    public function obtenerPorCorreo($correo) {
        $sql = "SELECT u.*, u.fecha_registro, u.fecha_creacion, 
                       p.nombres, p.apellidos, p.telefono, p.cedula
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.correo = :correo";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);

        try {
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Agregar un campo para indicar si tiene foto (opcional)
                $usuario['tiene_foto'] = $this->personaTieneFoto($usuario['id_persona']);
            }
            
            return $usuario;
        } catch (PDOException $e) {
            error_log("Error al obtener usuario por correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Método auxiliar para verificar si una persona tiene foto
     */
    private function personaTieneFoto($id_persona) {
        try {
            $sql = "SELECT COUNT(*) FROM fotos_perfil WHERE id_persona = :id_persona";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_persona', $id_persona);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar foto de persona: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarTipoUsuario($id_usuario, $tipo_usuario) {
        $sql = "UPDATE usuario SET tipo_usuario = :tipo_usuario 
                WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
        $stmt->bindParam(':id_usuario', $id_usuario);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar tipo de usuario: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT u.id_usuario, u.correo, u.tipo_usuario, u.activo, u.fecha_registro,
                       p.nombres, p.apellidos, p.telefono, p.cedula
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                ORDER BY u.id_usuario";

        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Método para obtener datos completos de usuario con foto
     */
    public function obtenerConFoto($correo) {
        try {
            // Primero obtener los datos básicos
            $sql = "SELECT u.*, p.nombres, p.apellidos, p.telefono, p.cedula
                    FROM usuario u
                    JOIN persona p ON u.id_persona = p.id_persona
                    WHERE u.correo = :correo";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':correo', $correo);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return false;
            }
            
            // Verificar si tiene foto
            $sqlFoto = "SELECT tipo_mime, contenido, tamano 
                       FROM fotos_perfil 
                       WHERE id_persona = :id_persona 
                       ORDER BY fecha_subida DESC 
                       LIMIT 1";
            
            $stmtFoto = $this->conn->prepare($sqlFoto);
            $stmtFoto->bindParam(':id_persona', $usuario['id_persona']);
            $stmtFoto->execute();
            $foto = $stmtFoto->fetch(PDO::FETCH_ASSOC);
            
            if ($foto) {
                $usuario['foto_info'] = $foto;
                $usuario['tiene_foto'] = true;
            } else {
                $usuario['tiene_foto'] = false;
            }
            
            return $usuario;
            
        } catch (PDOException $e) {
            error_log("Error al obtener usuario con foto: " . $e->getMessage());
            return false;
        }
    }
    public function obtenerPorEstado($estado = 'todos') {
    $sql = "SELECT u.id_usuario, u.correo, u.tipo_usuario, u.activo, u.fecha_registro,
                   p.nombres, p.apellidos, p.telefono, p.cedula
            FROM usuario u
            JOIN persona p ON u.id_persona = p.id_persona
            WHERE 1=1";
    
    // Aplicar filtro según estado - usar TRUE/FALSE para boolean
    if ($estado === 'pendientes') {
        $sql .= " AND u.activo IS FALSE";
    } elseif ($estado === 'activos') {
        $sql .= " AND u.activo IS TRUE";
    } elseif ($estado === 'inactivos') {
        $sql .= " AND u.activo IS FALSE";
    }
    
    $sql .= " ORDER BY u.fecha_registro DESC";
    
    $stmt = $this->conn->prepare($sql);
    
    try {
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener usuarios por estado: " . $e->getMessage());
        return [];
    }
}

public function cambiarEstado($id_usuario, $activo, $admin_id = null) {
    $sql = "UPDATE usuario 
            SET activo = :activo 
            WHERE id_usuario = :id_usuario";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL); // Esto está bien
    $stmt->bindParam(':id_usuario', $id_usuario);
    
    try {
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error al cambiar estado de usuario: " . $e->getMessage());
        return false;
    }
}

public function contarPorEstado() {
    $sql = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN activo IS TRUE THEN 1 END) as activos,
                COUNT(CASE WHEN activo IS FALSE THEN 1 END) as inactivos
            FROM usuario";
    
    $stmt = $this->conn->prepare($sql);
    
    try {
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al contar usuarios por estado: " . $e->getMessage());
        return ['total' => 0, 'activos' => 0, 'inactivos' => 0];
    }
}

public function obtenerPorId($id_usuario) {
    $sql = "SELECT u.*, p.nombres, p.apellidos, p.telefono, p.cedula
            FROM usuario u
            JOIN persona p ON u.id_persona = p.id_persona
            WHERE u.id_usuario = :id_usuario";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id_usuario', $id_usuario);
    
    try {
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener usuario por ID: " . $e->getMessage());
        return false;
    }
}
public function existeUsuarioParaPersona($id_persona) {
    $sql = "SELECT COUNT(*) FROM usuario WHERE id_persona = :id_persona";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id_persona', $id_persona);
    
    try {
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error al verificar usuario para persona: " . $e->getMessage());
        return true; // Por seguridad, asume que existe
    }
}
}
?>