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

    public function registrar($nombres, $apellidos, $correo, $telefono, $password) {

        if ($this->usuarioModel->existeCorreo($correo)) {
            return false;
        }

        // Insertar la persona primero
        $id_persona = $this->personaModel->insertar($nombres, $apellidos, $telefono);

        if ($id_persona) {
            $id_rol = 2; 
            $id_estado = 1; 
            
            // Insertar usuario asociado a la persona
            return $this->usuarioModel->insertar($id_persona, $id_rol, $id_estado, $correo, $password);
        }

        return false;
    }

    public function login($correo, $password) {
        $usuario = $this->usuarioModel->obtenerPorCorreo($correo);

        if ($usuario && password_verify($password, $usuario['contrasena'])) {
            if ($usuario['id_estado'] == 1) { // 1 = Activo
                return $usuario;
            } else {
                return false;
            }
        }

        return false;
    }
}
?>