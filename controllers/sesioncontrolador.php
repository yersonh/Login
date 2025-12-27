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

    // MÉTODO PARA CONTRATISTAS YA REGISTRADOS
    public function registrar($cedula, $correo, $password, $tipo_usuario = 'contratista') {
        
        // 1. Verificar si YA EXISTE usuario con ese correo
        if ($this->usuarioModel->existeCorreo($correo)) {
            error_log("Correo ya registrado: $correo");
            return false;
        }
        
        // 2. BUSCAR persona por cédula exacta
        $persona = $this->personaModel->buscarPorCedula($cedula);
        
        if (!$persona) {
            error_log("ERROR: No se encontró persona con cédula: $cedula");
            return false;
        }
        
        $id_persona = $persona['id_persona'];
        
        // 3. Verificar si YA TIENE usuario
        if ($this->usuarioModel->existeUsuarioParaPersona($id_persona)) {
            error_log("ERROR: La persona con cédula $cedula ya tiene usuario");
            return false;
        }
        
        // 4. Crear usuario (activo = 0 para pendiente de aprobación)
        $resultado = $this->usuarioModel->insertar($id_persona, $correo, $password, $tipo_usuario, 0);
        
        if ($resultado) {
            error_log("Usuario creado exitosamente para cédula $cedula (ID persona: $id_persona)");
            // NO actualizamos correo_personal - son campos diferentes
        }
        
        return $resultado;
    }

    // MÉTODO ANTIGUO (para compatibilidad con otros usos si es necesario)
    public function registrarCompleto($nombres, $apellidos, $correo, $telefono, $password, $tipo_usuario = 'contratista') {
        if ($this->usuarioModel->existeCorreo($correo)) {
            return false;
        }
        
        $cedula = 'TEMP_' . date('YmdHis') . rand(100, 999);
        $id_persona = $this->personaModel->insertar($cedula, $nombres, $apellidos, $telefono, $correo);
        
        if ($id_persona) {
            return $this->usuarioModel->insertar($id_persona, $correo, $password, $tipo_usuario, 0);
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