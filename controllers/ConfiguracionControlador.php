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
    public function actualizarLogo(string $rutaLogo, string $entidad, string $nit, string $enlaceWeb): bool {
        return $this->modelo->actualizarLogoYDatos($rutaLogo, $entidad, $nit, $enlaceWeb);
    }
    public function actualizarDatos(string $version, string $tipo_licencia, string $valida_hasta, string $desarrollado_por, string $direccion, string $correo_contacto, string $telefono): bool {
        return $this->modelo->actualizarDatos($version, $tipo_licencia, $valida_hasta, $desarrollado_por, $direccion, $correo_contacto, $telefono);
    }
}
?>