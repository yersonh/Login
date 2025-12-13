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
        try {
            $query = "SELECT * FROM " . $this->table . " 
                      ORDER BY id_parametrizacion DESC 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt->execute()) {
                error_log("Error en obtenerConfiguracion: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si no hay datos, retornar array vacío
            return $resultado ?: [];
            
        } catch (PDOException $e) {
            error_log("Error en obtenerConfiguracion: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarConfiguracion($datos) {
        try {
            // Primero, verificar si existe algún registro
            $existe = $this->obtenerConfiguracion();
            
            // Asegurar que todos los campos existan
            $camposRequeridos = [
                'version_sistema' => '',
                'tipo_licencia' => '',
                'valida_hasta' => null,
                'desarrollado_por' => '',
                'direccion' => '',
                'correo_contacto' => '',
                'telefono' => '',
                'entidad' => '',
                'enlace_web' => '',
                'ruta_logo' => 'imagenes/gobernacion.png', // Ruta por defecto
                'dias_restantes' => null
            ];
            
            // Fusionar con datos proporcionados
            $datosCompletos = array_merge($camposRequeridos, $datos);
            
            if ($existe && !empty($existe)) {
                // Si existe, actualizar
                $query = "UPDATE " . $this->table . " SET 
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
                $stmt->bindParam(':version_sistema', $datosCompletos['version_sistema']);
                $stmt->bindParam(':tipo_licencia', $datosCompletos['tipo_licencia']);
                $stmt->bindParam(':valida_hasta', $datosCompletos['valida_hasta'], PDO::PARAM_STR);
                $stmt->bindParam(':desarrollado_por', $datosCompletos['desarrollado_por']);
                $stmt->bindParam(':direccion', $datosCompletos['direccion']);
                $stmt->bindParam(':correo_contacto', $datosCompletos['correo_contacto']);
                $stmt->bindParam(':telefono', $datosCompletos['telefono']);
                $stmt->bindParam(':entidad', $datosCompletos['entidad']);
                $stmt->bindParam(':enlace_web', $datosCompletos['enlace_web']);
                $stmt->bindParam(':ruta_logo', $datosCompletos['ruta_logo']);
                
                // Calcular días restantes
                $dias_restantes = null;
                if (!empty($datosCompletos['valida_hasta'])) {
                    $hoy = new DateTime();
                    $validaHasta = new DateTime($datosCompletos['valida_hasta']);
                    $diferencia = $hoy->diff($validaHasta);
                    $dias_restantes = $diferencia->days;
                    if ($diferencia->invert) {
                        $dias_restantes = -$dias_restantes; // Negativo si ya pasó
                    }
                }
                $stmt->bindParam(':dias_restantes', $dias_restantes, PDO::PARAM_INT);
                
                $stmt->bindParam(':id', $existe['id_parametrizacion']);
                
            } else {
                // Si no existe, crear nuevo
                $query = "INSERT INTO " . $this->table . " (
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
                $stmt->bindParam(':version_sistema', $datosCompletos['version_sistema']);
                $stmt->bindParam(':tipo_licencia', $datosCompletos['tipo_licencia']);
                $stmt->bindParam(':valida_hasta', $datosCompletos['valida_hasta'], PDO::PARAM_STR);
                $stmt->bindParam(':desarrollado_por', $datosCompletos['desarrollado_por']);
                $stmt->bindParam(':direccion', $datosCompletos['direccion']);
                $stmt->bindParam(':correo_contacto', $datosCompletos['correo_contacto']);
                $stmt->bindParam(':telefono', $datosCompletos['telefono']);
                $stmt->bindParam(':entidad', $datosCompletos['entidad']);
                $stmt->bindParam(':enlace_web', $datosCompletos['enlace_web']);
                $stmt->bindParam(':ruta_logo', $datosCompletos['ruta_logo']);
                
                // Calcular días restantes
                $dias_restantes = null;
                if (!empty($datosCompletos['valida_hasta'])) {
                    $hoy = new DateTime();
                    $validaHasta = new DateTime($datosCompletos['valida_hasta']);
                    $diferencia = $hoy->diff($validaHasta);
                    $dias_restantes = $diferencia->days;
                    if ($diferencia->invert) {
                        $dias_restantes = -$dias_restantes;
                    }
                }
                $stmt->bindParam(':dias_restantes', $dias_restantes, PDO::PARAM_INT);
            }
            
            // Ejecutar y verificar
            $resultado = $stmt->execute();
            
            if (!$resultado) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error en execute actualizarConfiguracion: " . print_r($errorInfo, true));
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error en actualizarConfiguracion: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }
}
?>