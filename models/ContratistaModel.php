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
            cv_archivo, cv_nombre_original, cv_tipo_mime, cv_tamano,
            direccion_municipio_principal, direccion_municipio_secundario, direccion_municipio_terciario,
            contrato_archivo, contrato_nombre_original, contrato_tipo_mime, contrato_tamano,
            acta_inicio_archivo, acta_inicio_nombre_original, acta_inicio_tipo_mime, acta_inicio_tamano,
            rp_archivo, rp_nombre_original, rp_tipo_mime, rp_tamano
        ) VALUES (
            :id_persona, :id_area, :id_tipo_vinculacion,
            :id_municipio_principal, :id_municipio_secundario, :id_municipio_terciario,
            :numero_contrato, :fecha_contrato, :fecha_inicio, :fecha_final,
            :duracion_contrato, :numero_registro_presupuestal, :fecha_rp, :direccion,
            :cv_archivo, :cv_nombre_original, :cv_tipo_mime, :cv_tamano,
            :direccion_municipio_principal, :direccion_municipio_secundario, :direccion_municipio_terciario,
            :contrato_archivo, :contrato_nombre_original, :contrato_tipo_mime, :contrato_tamano,
            :acta_inicio_archivo, :acta_inicio_nombre_original, :acta_inicio_tipo_mime, :acta_inicio_tamano,
            :rp_archivo, :rp_nombre_original, :rp_tipo_mime, :rp_tamano
        ) RETURNING id_detalle";

        $stmt = $this->conn->prepare($sql);
        
        // Formatear fechas
        $fecha_contrato = $this->formatearFecha($datos['fecha_contrato']);
        $fecha_inicio = $this->formatearFecha($datos['fecha_inicio']);
        $fecha_final = $this->formatearFecha($datos['fecha_final']);
        $fecha_rp = !empty($datos['fecha_rp']) ? $this->formatearFecha($datos['fecha_rp']) : null;

        // Campos para CV
        $cv_archivo = isset($datos['cv_archivo']) ? $datos['cv_archivo'] : null;
        $cv_nombre_original = isset($datos['cv_nombre_original']) ? $datos['cv_nombre_original'] : null;
        $cv_tipo_mime = isset($datos['cv_tipo_mime']) ? $datos['cv_tipo_mime'] : null;
        $cv_tamano = isset($datos['cv_tamano']) ? $datos['cv_tamano'] : null;

        // Campos de dirección específicos
        $direccion_municipio_principal = $datos['direccion_municipio_principal'] ?? '';
        $direccion_municipio_secundario = $datos['direccion_municipio_secundario'] ?? null;
        $direccion_municipio_terciario = $datos['direccion_municipio_terciario'] ?? null;

        // Campos para contrato
        $contrato_archivo = isset($datos['contrato_archivo']) ? $datos['contrato_archivo'] : null;
        $contrato_nombre_original = isset($datos['contrato_nombre_original']) ? $datos['contrato_nombre_original'] : null;
        $contrato_tipo_mime = isset($datos['contrato_tipo_mime']) ? $datos['contrato_tipo_mime'] : null;
        $contrato_tamano = isset($datos['contrato_tamano']) ? $datos['contrato_tamano'] : null;

        // Campos para acta de inicio
        $acta_inicio_archivo = isset($datos['acta_inicio_archivo']) ? $datos['acta_inicio_archivo'] : null;
        $acta_inicio_nombre_original = isset($datos['acta_inicio_nombre_original']) ? $datos['acta_inicio_nombre_original'] : null;
        $acta_inicio_tipo_mime = isset($datos['acta_inicio_tipo_mime']) ? $datos['acta_inicio_tipo_mime'] : null;
        $acta_inicio_tamano = isset($datos['acta_inicio_tamano']) ? $datos['acta_inicio_tamano'] : null;

        // Campos para RP
        $rp_archivo = isset($datos['rp_archivo']) ? $datos['rp_archivo'] : null;
        $rp_nombre_original = isset($datos['rp_nombre_original']) ? $datos['rp_nombre_original'] : null;
        $rp_tipo_mime = isset($datos['rp_tipo_mime']) ? $datos['rp_tipo_mime'] : null;
        $rp_tamano = isset($datos['rp_tamano']) ? $datos['rp_tamano'] : null;

        // Vincular parámetros
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

        // Vincular parámetros de CV
        $stmt->bindParam(':cv_archivo', $cv_archivo, PDO::PARAM_LOB);
        $stmt->bindParam(':cv_nombre_original', $cv_nombre_original);
        $stmt->bindParam(':cv_tipo_mime', $cv_tipo_mime);
        $stmt->bindParam(':cv_tamano', $cv_tamano);

        // Vincular parámetros de dirección específicos
        $stmt->bindParam(':direccion_municipio_principal', $direccion_municipio_principal);
        $stmt->bindParam(':direccion_municipio_secundario', $direccion_municipio_secundario);
        $stmt->bindParam(':direccion_municipio_terciario', $direccion_municipio_terciario);

        // Vincular parámetros de contrato
        $stmt->bindParam(':contrato_archivo', $contrato_archivo, PDO::PARAM_LOB);
        $stmt->bindParam(':contrato_nombre_original', $contrato_nombre_original);
        $stmt->bindParam(':contrato_tipo_mime', $contrato_tipo_mime);
        $stmt->bindParam(':contrato_tamano', $contrato_tamano);

        // Vincular parámetros de acta de inicio
        $stmt->bindParam(':acta_inicio_archivo', $acta_inicio_archivo, PDO::PARAM_LOB);
        $stmt->bindParam(':acta_inicio_nombre_original', $acta_inicio_nombre_original);
        $stmt->bindParam(':acta_inicio_tipo_mime', $acta_inicio_tipo_mime);
        $stmt->bindParam(':acta_inicio_tamano', $acta_inicio_tamano);

        // Vincular parámetros de RP
        $stmt->bindParam(':rp_archivo', $rp_archivo, PDO::PARAM_LOB);
        $stmt->bindParam(':rp_nombre_original', $rp_nombre_original);
        $stmt->bindParam(':rp_tipo_mime', $rp_tipo_mime);
        $stmt->bindParam(':rp_tamano', $rp_tamano);

        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['id_detalle'];
    }

    public function obtenerTodosContratistas() {
        $sql = "SELECT 
                p.id_persona, p.cedula, p.nombres, p.apellidos, 
                p.telefono, p.correo_personal,
                dc.id_detalle, dc.numero_contrato, 
                dc.fecha_contrato, dc.fecha_inicio, dc.fecha_final,
                dc.duracion_contrato, dc.numero_registro_presupuestal,
                dc.created_at,
                a.nombre AS area, 
                tv.nombre AS tipo_vinculacion,
                m1.nombre AS municipio_principal,
                m2.nombre AS municipio_secundario,
                m3.nombre AS municipio_terciario,
                dc.direccion_municipio_principal,
                dc.cv_nombre_original, 
                dc.contrato_nombre_original,
                dc.acta_inicio_nombre_original,
                dc.rp_nombre_original
            FROM detalle_contrato dc
            JOIN persona p ON dc.id_persona = p.id_persona
            LEFT JOIN area a ON dc.id_area = a.id_area
            LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
            LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
            LEFT JOIN municipio m2 ON dc.id_municipio_secundario = m2.id_municipio
            LEFT JOIN municipio m3 ON dc.id_municipio_terciario = m3.id_municipio
            ORDER BY dc.created_at ASC";
        
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
                    dc.cv_tipo_mime, dc.cv_tamano,
                    dc.direccion_municipio_principal,
                    dc.direccion_municipio_secundario,
                    dc.direccion_municipio_terciario,
                    dc.contrato_archivo, dc.contrato_nombre_original,
                    dc.contrato_tipo_mime, dc.contrato_tamano,
                    dc.acta_inicio_archivo, dc.acta_inicio_nombre_original,
                    dc.acta_inicio_tipo_mime, dc.acta_inicio_tamano,
                    dc.rp_archivo, dc.rp_nombre_original,
                    dc.rp_tipo_mime, dc.rp_tamano
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

        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['cv_archivo'])) {

            $contenido = $resultado['cv_archivo'];

            if (is_string($contenido) && substr($contenido, 0, 2) === '\\x') {
                $resultado['cv_archivo'] = hex2bin(substr($contenido, 2));
            } 
            elseif (is_resource($contenido)) {
                $resultado['cv_archivo'] = stream_get_contents($contenido);
            }
        }
        
        return $resultado;
    }
    // Método para obtener contrato
    public function obtenerContrato($id_detalle) {
        $sql = "SELECT contrato_archivo, contrato_nombre_original, contrato_tipo_mime, contrato_tamano
                FROM detalle_contrato 
                WHERE id_detalle = :id_detalle";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_detalle', $id_detalle);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['contrato_archivo'])) {
            $contenido = $resultado['contrato_archivo'];
            
            if (is_string($contenido) && substr($contenido, 0, 2) === '\\x') {
                $resultado['contrato_archivo'] = hex2bin(substr($contenido, 2));
            } 
            elseif (is_resource($contenido)) {
                $resultado['contrato_archivo'] = stream_get_contents($contenido);
            }
        }
        
        return $resultado;
    }

    // Método para obtener acta de inicio
    public function obtenerActaInicio($id_detalle) {
        $sql = "SELECT acta_inicio_archivo, acta_inicio_nombre_original, acta_inicio_tipo_mime, acta_inicio_tamano
                FROM detalle_contrato 
                WHERE id_detalle = :id_detalle";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_detalle', $id_detalle);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['acta_inicio_archivo'])) {
            $contenido = $resultado['acta_inicio_archivo'];
            
            if (is_string($contenido) && substr($contenido, 0, 2) === '\\x') {
                $resultado['acta_inicio_archivo'] = hex2bin(substr($contenido, 2));
            } 
            elseif (is_resource($contenido)) {
                $resultado['acta_inicio_archivo'] = stream_get_contents($contenido);
            }
        }
        
        return $resultado;
    }

    // Método para obtener RP
    public function obtenerRP($id_detalle) {
        $sql = "SELECT rp_archivo, rp_nombre_original, rp_tipo_mime, rp_tamano
                FROM detalle_contrato 
                WHERE id_detalle = :id_detalle";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_detalle', $id_detalle);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado && !empty($resultado['rp_archivo'])) {
            $contenido = $resultado['rp_archivo'];
            
            if (is_string($contenido) && substr($contenido, 0, 2) === '\\x') {
                $resultado['rp_archivo'] = hex2bin(substr($contenido, 2));
            } 
            elseif (is_resource($contenido)) {
                $resultado['rp_archivo'] = stream_get_contents($contenido);
            }
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
    private function formatearFecha($fecha) {
        if (empty($fecha)) return null;
        
        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        
        return $fecha;
    }
  /*  public function obtenerContratistasParaMapa() {
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
                dc.direccion_municipio_principal,
                dc.direccion_municipio_secundario,
                dc.direccion_municipio_terciario,
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
            WHERE (dc.direccion IS NOT NULL AND dc.direccion != '')
                OR (dc.direccion_municipio_principal IS NOT NULL AND dc.direccion_municipio_principal != '')
            ORDER BY dc.created_at DESC";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}*/
public function actualizarContratista($id_detalle, $datos) {
    $this->conn->beginTransaction();
    
    try {
        // 1. Obtener id_persona del detalle
        $sqlGetPersona = "SELECT id_persona FROM detalle_contrato WHERE id_detalle = :id_detalle";
        $stmtGetPersona = $this->conn->prepare($sqlGetPersona);
        $stmtGetPersona->bindParam(':id_detalle', $id_detalle);
        $stmtGetPersona->execute();
        $resultado = $stmtGetPersona->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            throw new Exception('Detalle de contrato no encontrado');
        }
        
        $id_persona = $resultado['id_persona'];
        
        // 2. Actualizar datos de persona
        $sqlPersona = "UPDATE persona SET 
                        nombres = :nombres,
                        apellidos = :apellidos,
                        cedula = :cedula,
                        telefono = :telefono,
                        correo_personal = :correo_personal
                      WHERE id_persona = :id_persona";
        
        $stmtPersona = $this->conn->prepare($sqlPersona);
        $stmtPersona->bindParam(':nombres', $datos['nombres']);
        $stmtPersona->bindParam(':apellidos', $datos['apellidos']);
        $stmtPersona->bindParam(':cedula', $datos['cedula']);
        $stmtPersona->bindParam(':telefono', $datos['telefono']);
        $stmtPersona->bindParam(':correo_personal', $datos['correo_personal']);
        $stmtPersona->bindParam(':id_persona', $id_persona);
        $stmtPersona->execute();
        
        // 3. Formatear fechas
        $fecha_contrato = !empty($datos['fecha_contrato']) ? $this->formatearFecha($datos['fecha_contrato']) : null;
        $fecha_inicio = !empty($datos['fecha_inicio']) ? $this->formatearFecha($datos['fecha_inicio']) : null;
        $fecha_final = !empty($datos['fecha_final']) ? $this->formatearFecha($datos['fecha_final']) : null;
        $fecha_rp = !empty($datos['fecha_rp']) ? $this->formatearFecha($datos['fecha_rp']) : null;
        
        // 4. Actualizar detalle_contrato
        $sqlDetalle = "UPDATE detalle_contrato SET 
                        id_area = :id_area,
                        id_tipo_vinculacion = :id_tipo_vinculacion,
                        id_municipio_principal = :id_municipio_principal,
                        id_municipio_secundario = :id_municipio_secundario,
                        id_municipio_terciario = :id_municipio_terciario,
                        numero_contrato = :numero_contrato,
                        fecha_contrato = :fecha_contrato,
                        fecha_inicio = :fecha_inicio,
                        fecha_final = :fecha_final,
                        duracion_contrato = :duracion_contrato,
                        numero_registro_presupuestal = :numero_registro_presupuestal,
                        fecha_rp = :fecha_rp,
                        direccion_municipio_principal = :direccion_municipio_principal,
                        direccion_municipio_secundario = :direccion_municipio_secundario,
                        direccion_municipio_terciario = :direccion_municipio_terciario,
                        updated_at = CURRENT_TIMESTAMP
                      WHERE id_detalle = :id_detalle";
        
        $stmtDetalle = $this->conn->prepare($sqlDetalle);
        $stmtDetalle->bindParam(':id_area', $datos['id_area']);
        $stmtDetalle->bindParam(':id_tipo_vinculacion', $datos['id_tipo_vinculacion']);
        $stmtDetalle->bindParam(':id_municipio_principal', $datos['id_municipio_principal']);
        $stmtDetalle->bindParam(':id_municipio_secundario', $datos['id_municipio_secundario']);
        $stmtDetalle->bindParam(':id_municipio_terciario', $datos['id_municipio_terciario']);
        $stmtDetalle->bindParam(':numero_contrato', $datos['numero_contrato']);
        $stmtDetalle->bindParam(':fecha_contrato', $fecha_contrato);
        $stmtDetalle->bindParam(':fecha_inicio', $fecha_inicio);
        $stmtDetalle->bindParam(':fecha_final', $fecha_final);
        $stmtDetalle->bindParam(':duracion_contrato', $datos['duracion_contrato']);
        $stmtDetalle->bindParam(':numero_registro_presupuestal', $datos['numero_registro_presupuestal']);
        $stmtDetalle->bindParam(':fecha_rp', $fecha_rp);
        $stmtDetalle->bindParam(':direccion_municipio_principal', $datos['direccion_municipio_principal']);
        $stmtDetalle->bindParam(':direccion_municipio_secundario', $datos['direccion_municipio_secundario']);
        $stmtDetalle->bindParam(':direccion_municipio_terciario', $datos['direccion_municipio_terciario']);
        $stmtDetalle->bindParam(':id_detalle', $id_detalle);
        
        $stmtDetalle->execute();
        
        $this->conn->commit();
        
        return [
            'success' => true,
            'mensaje' => 'Contratista actualizado exitosamente',
            'id_detalle' => $id_detalle
        ];
        
    } catch (Exception $e) {
        $this->conn->rollBack();
        error_log("Error en actualizarContratista: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
}
?>