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
}
?>