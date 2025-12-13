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
public function actualizarConfiguracion($datos) {
    try {
        // Primero, verificar si existe algún registro
        $existe = $this->obtenerConfiguracion();
        
        if ($existe) {
            // Si existe, actualizar
            $query = "UPDATE parametrizar SET 
                      version_sistema = :version_sistema,
                      tipo_licencia = :tipo_licencia,
                      valida_hasta = :valida_hasta,
                      desarrollado_por = :desarrollado_por,
                      direccion = :direccion,
                      correo_contacto = :correo_contacto,
                      telefono = :telefono,
                      entidad = :entidad,
                      enlace_web = :enlace_web,
                      ruta_logo = :ruta_logo,
                      dias_restantes = :dias_restantes,
                      updated_at = NOW()
                      WHERE id_parametrizacion = :id";
            
            $stmt = $this->conn->prepare($query);
            
            // Asignar valores
            $stmt->bindParam(':version_sistema', $datos['version_sistema']);
            $stmt->bindParam(':tipo_licencia', $datos['tipo_licencia']);
            $stmt->bindParam(':valida_hasta', $datos['valida_hasta'] ?? null);
            $stmt->bindParam(':desarrollado_por', $datos['desarrollado_por']);
            $stmt->bindParam(':direccion', $datos['direccion'] ?? '');
            $stmt->bindParam(':correo_contacto', $datos['correo_contacto']);
            $stmt->bindParam(':telefono', $datos['telefono'] ?? '');
            $stmt->bindParam(':entidad', $datos['entidad'] ?? '');
            $stmt->bindParam(':enlace_web', $datos['enlace_web'] ?? '');
            $stmt->bindParam(':ruta_logo', $datos['ruta_logo'] ?? '');
            
            // Calcular días restantes
            $dias_restantes = null;
            if (!empty($datos['valida_hasta'])) {
                $hoy = new DateTime();
                $validaHasta = new DateTime($datos['valida_hasta']);
                $diferencia = $hoy->diff($validaHasta);
                $dias_restantes = $diferencia->days;
            }
            $stmt->bindParam(':dias_restantes', $dias_restantes);
            
            $stmt->bindParam(':id', $existe['id_parametrizacion']);
            
        } else {
            // Si no existe, crear nuevo
            $query = "INSERT INTO parametrizar (
                      version_sistema, tipo_licencia, valida_hasta,
                      desarrollado_por, direccion, correo_contacto,
                      telefono, entidad, enlace_web, ruta_logo,
                      dias_restantes, updated_at
                      ) VALUES (
                      :version_sistema, :tipo_licencia, :valida_hasta,
                      :desarrollado_por, :direccion, :correo_contacto,
                      :telefono, :entidad, :enlace_web, :ruta_logo,
                      :dias_restantes, NOW()
                      )";
            
            $stmt = $this->conn->prepare($query);
            
            // Asignar valores
            $stmt->bindParam(':version_sistema', $datos['version_sistema']);
            $stmt->bindParam(':tipo_licencia', $datos['tipo_licencia']);
            $stmt->bindParam(':valida_hasta', $datos['valida_hasta'] ?? null);
            $stmt->bindParam(':desarrollado_por', $datos['desarrollado_por']);
            $stmt->bindParam(':direccion', $datos['direccion'] ?? '');
            $stmt->bindParam(':correo_contacto', $datos['correo_contacto']);
            $stmt->bindParam(':telefono', $datos['telefono'] ?? '');
            $stmt->bindParam(':entidad', $datos['entidad'] ?? '');
            $stmt->bindParam(':enlace_web', $datos['enlace_web'] ?? '');
            $stmt->bindParam(':ruta_logo', $datos['ruta_logo'] ?? '');
            
            // Calcular días restantes
            $dias_restantes = null;
            if (!empty($datos['valida_hasta'])) {
                $hoy = new DateTime();
                $validaHasta = new DateTime($datos['valida_hasta']);
                $diferencia = $hoy->diff($validaHasta);
                $dias_restantes = $diferencia->days;
            }
            $stmt->bindParam(':dias_restantes', $dias_restantes);
        }
        
        return $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Error en actualizarConfiguracion: " . $e->getMessage());
        return false;
    }
}
}
?>