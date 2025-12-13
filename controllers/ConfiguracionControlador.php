<?php
require_once __DIR__ . '/../models/parametrizar.php';

class ConfiguracionControlador {
    private $modelo;

    public function __construct() {
        $this->modelo = new Configuracion();
    }

    public function obtenerDatos() {
        $data = $this->modelo->obtenerConfiguracion();
        return $data ?: [];
    }
    public function actualizarDatos($datos) {
        try {
            return $this->modelo->actualizarConfiguracion($datos);
        } catch (Exception $e) {
            error_log("Error en Controlador: " . $e->getMessage());
            return false;
        }
    }
}
    
?>