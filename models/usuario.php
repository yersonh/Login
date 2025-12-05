<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function insertar($id_persona, $correo, $password, $tipo_usuario = 'usuario') {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuario (id_persona, correo, contrasena, tipo_usuario)
                VALUES (:id_persona, :correo, :password, :tipo_usuario)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);

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
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar correo: " . $e->getMessage());
            return true; // Por seguridad, si hay error asumimos que existe
        }
    }

    public function obtenerPorCorreo($correo) {
        $sql = "SELECT u.*, p.nombres, p.apellidos, p.telefono
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
        $sql = "SELECT u.id_usuario, u.correo, u.tipo_usuario, 
                    p.nombres, p.apellidos, p.telefono
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
?>