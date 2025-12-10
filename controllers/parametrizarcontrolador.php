<?php
require_once __DIR__ . '/../models/parametrizar.php';

class ConfiguracionControlador {
    private $modelo;

    public function __construct() {
        $this->modelo = new Configuracion();
    }

    // Método para usar en las vistas (menuAdministrador, login, etc.)
    public function getDatos() {
        return $this->modelo->obtenerConfiguracion();
    }

    // Método para procesar el formulario de edición
    public function actualizarDatos() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // 1. Obtener datos actuales por si no se sube logo nuevo
            $configActual = $this->getDatos();
            $rutaLogo = $configActual['ruta_logo'];

            // 2. Manejo de subida de imagen (Logo)
            if (isset($_FILES['logo_nuevo']) && $_FILES['logo_nuevo']['error'] === UPLOAD_ERR_OK) {
                $directorioDestino = __DIR__ . '/../imagenes/';
                $nombreArchivo = 'logo_sistema_' . time() . '.png'; // Nombre único
                $rutaDestino = $directorioDestino . $nombreArchivo;
                
                // Mover archivo
                if (move_uploaded_file($_FILES['logo_nuevo']['tmp_name'], $rutaDestino)) {
                    $rutaLogo = '../imagenes/' . $nombreArchivo; // Ruta relativa para guardar en BD
                }
            }

            // 3. Preparar array de datos
            $datos = [
                'version_sistema' => $_POST['version'] ?? $configActual['version_sistema'],
                'tipo_licencia'   => $_POST['licencia'] ?? $configActual['tipo_licencia'],
                'valida_hasta'    => $_POST['valida_hasta'] ?? $configActual['valida_hasta'],
                'desarrollado_por'=> $_POST['desarrollador'] ?? $configActual['desarrollado_por'],
                'direccion'       => $_POST['direccion'] ?? $configActual['direccion'],
                'correo_contacto' => $_POST['email'] ?? $configActual['correo_contacto'],
                'telefono'        => $_POST['telefono'] ?? $configActual['telefono'],
                'ruta_logo'       => $rutaLogo
            ];

            // 4. Guardar en BD
            if ($this->modelo->actualizarConfiguracion($datos)) {
                return ['success' => true, 'message' => 'Configuración actualizada correctamente.'];
            } else {
                return ['success' => false, 'message' => 'Error al guardar en base de datos.'];
            }
        }
    }
}
?>