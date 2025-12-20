<?php
require_once __DIR__ . '/persona.php';

class ContratistaModel {
    private $conn;
    private $personaModel;
    private $usuarioModel;

    public function __construct($db) {
        $this->conn = $db;
        $this->personaModel = new Persona($db);
    }

    public function registrarContratistaCompleto($datos) {
        $this->conn->beginTransaction();
        
        try {
            if ($this->existeContratista($datos['cedula'], $datos['numero_contrato'])) {
                throw new Exception('Ya existe un contratista con esa cédula o número de contrato');
            }

            $nombreCompleto = $datos['nombre_completo'];
            $partesNombre = $this->separarNombresApellidos($nombreCompleto);

            $id_persona = $this->personaModel->insertar(
                $datos['cedula'],
                $partesNombre['nombres'],
                $partesNombre['apellidos'],
                $datos['celular']
            );

            if (!$id_persona) {
                throw new Exception('Error al registrar los datos personales');
            }

            $id_detalle = $this->insertarDetalleContrato($id_persona, $datos);
            
            $this->conn->commit();

            $proximo_consecutivo = $this->obtenerProximoConsecutivo();

            return [
                'success' => true,
                'id_persona' => $id_persona,
                'id_detalle' => $id_detalle,
                'mensaje' => 'Contratista registrado exitosamente',
                'proximo_consecutivo' => $proximo_consecutivo
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en registrarContratistaCompleto: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function existeContratista($cedula, $numero_contrato) {
        $sql_persona = "SELECT COUNT(*) FROM persona WHERE cedula = :cedula";
        $stmt = $this->conn->prepare($sql_persona);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        $existePersona = $stmt->fetchColumn() > 0;

        $sql_contrato = "SELECT COUNT(*) FROM detalle_contrato WHERE numero_contrato = :numero_contrato";
        $stmt = $this->conn->prepare($sql_contrato);
        $stmt->bindParam(':numero_contrato', $numero_contrato);
        $stmt->execute();
        $existeContrato = $stmt->fetchColumn() > 0;

        return $existePersona || $existeContrato;
    }

    private function separarNombresApellidos($nombreCompleto) {
        $partes = explode(' ', trim($nombreCompleto));
        
        if (count($partes) >= 2) {
            $nombres = array_shift($partes);
            $apellidos = implode(' ', $partes);
        } else {
            $nombres = $nombreCompleto;
            $apellidos = '';
        }
        
        return [
            'nombres' => $nombres,
            'apellidos' => $apellidos
        ];
    }

    private function insertarDetalleContrato($id_persona, $datos) {
    $sql = "INSERT INTO detalle_contrato (
        id_persona, id_area, id_tipo_vinculacion,
        id_municipio_principal, id_municipio_secundario, id_municipio_terciario,
        numero_contrato, fecha_contrato, fecha_inicio, fecha_final,
        duracion_contrato, numero_registro_presupuestal, fecha_rp, direccion,
        cv_archivo, cv_nombre_original, cv_tipo_mime, cv_tamano
    ) VALUES (
        :id_persona, :id_area, :id_tipo_vinculacion,
        :id_municipio_principal, :id_municipio_secundario, :id_municipio_terciario,
        :numero_contrato, :fecha_contrato, :fecha_inicio, :fecha_final,
        :duracion_contrato, :numero_registro_presupuestal, :fecha_rp, :direccion,
        :cv_archivo, :cv_nombre_original, :cv_tipo_mime, :cv_tamano  
    ) RETURNING id_detalle";

    $stmt = $this->conn->prepare($sql);
    
    $fecha_contrato = $this->formatearFecha($datos['fecha_contrato']);
    $fecha_inicio = $this->formatearFecha($datos['fecha_inicio']);
    $fecha_final = $this->formatearFecha($datos['fecha_final']);
    $fecha_rp = !empty($datos['fecha_rp']) ? $this->formatearFecha($datos['fecha_rp']) : null;

    $cv_archivo = isset($datos['cv_archivo']) ? $datos['cv_archivo'] : null;
    $cv_nombre_original = isset($datos['cv_nombre_original']) ? $datos['cv_nombre_original'] : null;
    $cv_tipo_mime = isset($datos['cv_tipo_mime']) ? $datos['cv_tipo_mime'] : null;
    $cv_tamano = isset($datos['cv_tamano']) ? $datos['cv_tamano'] : null;

    $stmt->bindParam(':id_persona', $id_persona);
    $stmt->bindParam(':id_area', $datos['id_area']);
    $stmt->bindParam(':id_tipo_vinculacion', $datos['id_tipo_vinculacion']);
    $stmt->bindParam(':id_municipio_principal', $datos['id_municipio_principal']);
    $stmt->bindParam(':id_municipio_secundario', $datos['id_municipio_secundario']);
    $stmt->bindParam(':id_municipio_terciario', $datos['id_municipio_terciario']);
    $stmt->bindParam(':numero_contrato', $datos['numero_contrato']);
    $stmt->bindParam(':fecha_contrato', $fecha_contrato);
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_final', $fecha_final);
    $stmt->bindParam(':duracion_contrato', $datos['duracion_contrato']);
    $stmt->bindParam(':numero_registro_presupuestal', $datos['numero_registro_presupuestal']);
    $stmt->bindParam(':fecha_rp', $fecha_rp);
    $stmt->bindParam(':direccion', $datos['direccion']);

    $stmt->bindParam(':cv_archivo', $cv_archivo, PDO::PARAM_LOB);
    $stmt->bindParam(':cv_nombre_original', $cv_nombre_original);
    $stmt->bindParam(':cv_tipo_mime', $cv_tipo_mime);
    $stmt->bindParam(':cv_tamano', $cv_tamano);

    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $resultado['id_detalle'];
}
    private function formatearFecha($fecha) {
        if (empty($fecha)) return null;
        
        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        
        return $fecha;
    }

    public function obtenerTodosContratistas() {
        $sql = "SELECT 
                    p.id_persona, p.cedula, p.nombres, p.apellidos, p.telefono,
                    dc.id_detalle, dc.numero_contrato, dc.fecha_inicio, dc.fecha_final,
                    a.nombre AS area, tv.nombre AS tipo_vinculacion,
                    m1.nombre AS municipio_principal,
                    dc.cv_nombre_original, 
                    dc.cv_tamano 
                FROM detalle_contrato dc
                JOIN persona p ON dc.id_persona = p.id_persona
                LEFT JOIN area a ON dc.id_area = a.id_area
                LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
                LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
                ORDER BY dc.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerContratistaPorId($id_detalle) {
        $sql = "SELECT 
                    p.*, dc.*,
                    a.nombre AS area_nombre,
                    tv.nombre AS tipo_vinculacion_nombre,
                    m1.nombre AS municipio_principal_nombre,
                    m2.nombre AS municipio_secundario_nombre,
                    m3.nombre AS municipio_terciario_nombre,
                    dc.cv_archivo, dc.cv_nombre_original, 
                    dc.cv_tipo_mime, dc.cv_tamano  
                FROM detalle_contrato dc
                JOIN persona p ON dc.id_persona = p.id_persona
                LEFT JOIN area a ON dc.id_area = a.id_area
                LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
                LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
                LEFT JOIN municipio m2 ON dc.id_municipio_secundario = m2.id_municipio
                LEFT JOIN municipio m3 ON dc.id_municipio_terciario = m3.id_municipio
                WHERE dc.id_detalle = :id_detalle";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_detalle', $id_detalle);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function obtenerCV($id_detalle) {
        $sql = "SELECT cv_archivo, cv_nombre_original, cv_tipo_mime, cv_tamano
                FROM detalle_contrato 
                WHERE id_detalle = :id_detalle";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_detalle', $id_detalle);
        
        // IMPORTANTE: Configurar para que PDO no convierta los LOBs
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['cv_archivo'])) {
            // PostgreSQL devuelve bytea con prefijo '\x' para formato hexadecimal
            // o como un stream/resource
            $contenido = $resultado['cv_archivo'];
            
            // Si es string y comienza con '\x' (formato hexadecimal de PostgreSQL)
            if (is_string($contenido) && substr($contenido, 0, 2) === '\\x') {
                // Decodificar hexadecimal
                $resultado['cv_archivo'] = hex2bin(substr($contenido, 2));
            } 
            // Si es un resource/stream
            elseif (is_resource($contenido)) {
                $resultado['cv_archivo'] = stream_get_contents($contenido);
            }
            // Si ya es binario, dejarlo tal cual
        }
        
        return $resultado;
    }
    public function obtenerProximoConsecutivo() {
        $sql = "SELECT COALESCE(MAX(id_detalle), 0) + 1 AS proximo FROM detalle_contrato";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['proximo'];
    }
    public function obtenerContratistasParaMapa() {
        $sql = "SELECT 
                    p.id_persona, 
                    p.cedula, 
                    p.nombres, 
                    p.apellidos, 
                    p.telefono,
                    dc.id_detalle, 
                    dc.numero_contrato, 
                    dc.fecha_inicio, 
                    dc.fecha_final,
                    dc.direccion,
                    a.nombre AS area,
                    tv.nombre AS tipo_vinculacion,
                    m1.nombre AS municipio_principal,
                    m2.nombre AS municipio_secundario,
                    m3.nombre AS municipio_terciario,
                    dc.created_at
                FROM detalle_contrato dc
                JOIN persona p ON dc.id_persona = p.id_persona
                LEFT JOIN area a ON dc.id_area = a.id_area
                LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
                LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
                LEFT JOIN municipio m2 ON dc.id_municipio_secundario = m2.id_municipio
                LEFT JOIN municipio m3 ON dc.id_municipio_terciario = m3.id_municipio
                WHERE dc.direccion IS NOT NULL AND dc.direccion != ''
                ORDER BY dc.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>