<?php
require_once __DIR__ . '/../config/database.php';

class Configuracion {
    private $conn;
    private $table = 'parametrizar';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }

    public function obtenerConfiguracion() {
    $query = "SELECT * FROM " . $this->table . " 
              ORDER BY id_parametrizacion DESC 
              LIMIT 1";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    public function insertarConfiguracion($datos) {
    // Elimina created_at y updated_at si no existen en tu tabla
    $query = "INSERT INTO " . $this->table . " (
                version_sistema, tipo_licencia, valida_hasta,
                desarrollado_por, direccion, correo_contacto,
                telefono, ruta_logo, enlace_web, entidad
              ) VALUES (
                :version, :licencia, :valida,
                :dev, :dir, :email,
                :tel, :logo, :enlace, :entidad
              )";

    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(':version', $datos['version_sistema']);
    $stmt->bindParam(':licencia', $datos['tipo_licencia']);
    $stmt->bindParam(':valida', $datos['valida_hasta']);
    $stmt->bindParam(':dev', $datos['desarrollado_por']);
    $stmt->bindParam(':dir', $datos['direccion']);
    $stmt->bindParam(':email', $datos['correo_contacto']);
    $stmt->bindParam(':tel', $datos['telefono']);
    $stmt->bindParam(':logo', $datos['ruta_logo']);
    $stmt->bindParam(':enlace', $datos['enlace_web']); 
    $stmt->bindParam(':entidad', $datos['entidad']);

    return $stmt->execute();
}
   public function actualizarLogo($rutaLogo, $entidad, $enlace) {
    // Obtener la última configuración
    $ultimaConfig = $this->obtenerConfiguracion();
    
    if (!$ultimaConfig) {
        // Si no hay configuraciones, crear una nueva
        $datosDefault = [
            'version_sistema' => '1.0.0',
            'tipo_licencia' => 'Evaluación',
            'valida_hasta' => date('Y-m-d', strtotime('+90 days')),
            'desarrollado_por' => 'SisgonTech',
            'direccion' => 'Carrera 33 # 38-45, Edificio Central...',
            'correo_contacto' => 'gobernaciondelmeta@meta.gov.co',
            'telefono' => '(57 -608) 6 818503',
            'ruta_logo' => $rutaLogo,
            'enlace_web' => $enlace,
            'entidad' => $entidad
        ];
        
        return $this->insertarConfiguracion($datosDefault);
    }
    
    // Usar el ID de la última configuración
    $id = $ultimaConfig['id_parametrizacion'];
    
    $query = "UPDATE " . $this->table . " SET 
                ruta_logo = :logo,
                entidad = :entidad,
                enlace_web = :enlace
              WHERE id_parametrizacion = :id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':logo', $rutaLogo);
    $stmt->bindParam(':entidad', $entidad);
    $stmt->bindParam(':enlace', $enlace);
    $stmt->bindParam(':id', $id);

    return $stmt->execute();
}
}
?>