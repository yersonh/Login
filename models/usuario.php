<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function insertar($id_persona, $correo, $password, $tipo_usuario = 'contratista', $activo = true) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuario (id_persona, correo, contrasena, tipo_usuario, activo)
                VALUES (:id_persona, :correo, :password, :tipo_usuario, :activo)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);

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
    // Agregamos u.reset_password y u.fecha_creacion
    $sql = "SELECT u.*, u.reset_password, u.fecha_creacion, 
                   p.nombres, p.apellidos, p.telefono, p.cedula, p.foto_perfil
            FROM usuario u
            JOIN persona p ON u.id_persona = p.id_persona
            WHERE u.correo = :correo";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':correo', $correo);

    try {
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener usuario por correo: " . $e->getMessage());
        return false;
    }
}
public function marcarClaveEstablecida($id_usuario) {
    $sql = "UPDATE usuario SET reset_password = FALSE 
            WHERE id_usuario = :id_usuario";

    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id_usuario', $id_usuario);

    try {
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error al marcar clave como establecida: " . $e->getMessage());
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
        $sql = "SELECT u.id_usuario, u.correo, u.tipo_usuario, u.activo,
                       p.nombres, p.apellidos, p.telefono, p.cedula, p.foto_perfil
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
}
