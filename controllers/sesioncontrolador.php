<?php
require_once __DIR__ . '/../models/persona.php';
require_once __DIR__ . '/../models/usuario.php';

class SesionControlador {
    private $personaModel;
    private $usuarioModel;

    public function __construct($db) {
        $this->personaModel = new Persona($db);
        $this->usuarioModel = new Usuario($db);
    }

     public function registrar($nombres, $apellidos, $correo, $telefono, $password, $tipo_usuario = 'contratista') {
        if ($this->usuarioModel->existeCorreo($correo)) {
            return false;
        }
        $id_persona = $this->personaModel->insertar($cedula, $nombres, $apellidos, $telefono, $correo);

        if ($id_persona) {
            return $this->usuarioModel->insertar($id_persona, $correo, $password, $tipo_usuario);
        }

        return false;
    }

    public function login($correo, $password) {
        $usuario = $this->usuarioModel->obtenerPorCorreo($correo);

        if ($usuario && password_verify($password, $usuario['contrasena'])) {
            return $usuario;
        }

        return false;
    }


    public function actualizarRolUsuario($id_usuario, $tipo_usuario) {
        return $this->usuarioModel->actualizarTipoUsuario($id_usuario, $tipo_usuario);
    }

    public function obtenerUsuarios() {
        return $this->usuarioModel->obtenerTodos();
    }
}
?>