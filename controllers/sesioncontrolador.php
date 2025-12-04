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
        // Verificar si el correo ya existe
        if ($this->usuarioModel->existeCorreo($correo)) {
            return false;
        }

        // Insertar la persona primero
        $id_persona = $this->personaModel->insertar($nombres, $apellidos, $telefono);

        if ($id_persona) {
            // Solo pasar id_persona, correo y password (sin id_rol ni id_estado)
            return $this->usuarioModel->insertar($id_persona, $correo, $password);
        }

        return false;
    }

    public function login($correo, $password) {
        $usuario = $this->usuarioModel->obtenerPorCorreo($correo);

        if ($usuario && password_verify($password, $usuario['contrasena'])) {
            // Ya no verificamos id_estado porque no existe
            return $usuario;
        }

        return false;
    }
}
?>