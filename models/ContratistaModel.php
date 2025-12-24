<?php
require_once __DIR__ . '/persona.php';

class ContratistaModel {
    private $conn;
    private $personaModel;

    public function __construct($db) {
        $this->conn = $db;
        $this->personaModel = new Persona($db);
    }

    // ================= MÉTODO PRINCIPAL =================
    
    public function registrarContratistaCompleto($datos, $archivos = []) {
        $this->conn->beginTransaction();
        
        try {
            if ($this->existeContratista($datos['cedula'], $datos['numero_contrato'])) {
                throw new Exception('Ya existe un contratista con esa cédula o número de contrato');
            }

            $nombreCompleto = $datos['nombre_completo'];
            $partesNombre = $this->separarNombresApellidos($nombreCompleto);

            // Insertar persona con todos los campos (incluyendo profesion)
            $id_persona = $this->personaModel->insertar(
                $datos['cedula'],
                $partesNombre['nombres'],
                $partesNombre['apellidos'],
                $datos['celular'],
                $datos['correo'],
                $datos['profesion'] ?? null  // Nuevo campo agregado
            );

            if (!$id_persona) {
                throw new Exception('Error al registrar los datos personales');
            }

            // Guardar foto de perfil si se subió
            if (isset($archivos['foto_perfil']) && $archivos['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $this->personaModel->guardarFotoPerfil($id_persona, $archivos['foto_perfil']);
            }

            $id_detalle = $this->insertarDetalleContrato($id_persona, $datos, $archivos);
            
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

    // ================= MÉTODOS AUXILIARES PRIVADOS =================

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

    private function insertarDetalleContrato($id_persona, $datos, $archivos = []) {
        // Manejar campos opcionales
        $id_municipio_secundario = !empty($datos['id_municipio_secundario']) && $datos['id_municipio_secundario'] !== '0' 
            ? $datos['id_municipio_secundario'] : null;
        
        $id_municipio_terciario = !empty($datos['id_municipio_terciario']) && $datos['id_municipio_terciario'] !== '0' 
            ? $datos['id_municipio_terciario'] : null;
        
        $direccion_municipio_secundario = !empty($datos['direccion_municipio_secundario']) 
            ? $datos['direccion_municipio_secundario'] : null;
        
        $direccion_municipio_terciario = !empty($datos['direccion_municipio_terciario']) 
            ? $datos['direccion_municipio_terciario'] : null;

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
        $cv_archivo = isset($archivos['adjuntar_cv']) && $archivos['adjuntar_cv']['error'] === UPLOAD_ERR_OK 
            ? file_get_contents($archivos['adjuntar_cv']['tmp_name']) : null;
        $cv_nombre_original = isset($archivos['adjuntar_cv']) && $archivos['adjuntar_cv']['error'] === UPLOAD_ERR_OK 
            ? basename($archivos['adjuntar_cv']['name']) : null;
        $cv_tipo_mime = isset($archivos['adjuntar_cv']) && $archivos['adjuntar_cv']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_cv']['type'] : null;
        $cv_tamano = isset($archivos['adjuntar_cv']) && $archivos['adjuntar_cv']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_cv']['size'] : null;

        // Campos para contrato
        $contrato_archivo = isset($archivos['adjuntar_contrato']) && $archivos['adjuntar_contrato']['error'] === UPLOAD_ERR_OK 
            ? file_get_contents($archivos['adjuntar_contrato']['tmp_name']) : null;
        $contrato_nombre_original = isset($archivos['adjuntar_contrato']) && $archivos['adjuntar_contrato']['error'] === UPLOAD_ERR_OK 
            ? basename($archivos['adjuntar_contrato']['name']) : null;
        $contrato_tipo_mime = isset($archivos['adjuntar_contrato']) && $archivos['adjuntar_contrato']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_contrato']['type'] : null;
        $contrato_tamano = isset($archivos['adjuntar_contrato']) && $archivos['adjuntar_contrato']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_contrato']['size'] : null;

        // Campos para acta de inicio
        $acta_inicio_archivo = isset($archivos['adjuntar_acta_inicio']) && $archivos['adjuntar_acta_inicio']['error'] === UPLOAD_ERR_OK 
            ? file_get_contents($archivos['adjuntar_acta_inicio']['tmp_name']) : null;
        $acta_inicio_nombre_original = isset($archivos['adjuntar_acta_inicio']) && $archivos['adjuntar_acta_inicio']['error'] === UPLOAD_ERR_OK 
            ? basename($archivos['adjuntar_acta_inicio']['name']) : null;
        $acta_inicio_tipo_mime = isset($archivos['adjuntar_acta_inicio']) && $archivos['adjuntar_acta_inicio']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_acta_inicio']['type'] : null;
        $acta_inicio_tamano = isset($archivos['adjuntar_acta_inicio']) && $archivos['adjuntar_acta_inicio']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_acta_inicio']['size'] : null;

        // Campos para RP
        $rp_archivo = isset($archivos['adjuntar_rp']) && $archivos['adjuntar_rp']['error'] === UPLOAD_ERR_OK 
            ? file_get_contents($archivos['adjuntar_rp']['tmp_name']) : null;
        $rp_nombre_original = isset($archivos['adjuntar_rp']) && $archivos['adjuntar_rp']['error'] === UPLOAD_ERR_OK 
            ? basename($archivos['adjuntar_rp']['name']) : null;
        $rp_tipo_mime = isset($archivos['adjuntar_rp']) && $archivos['adjuntar_rp']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_rp']['type'] : null;
        $rp_tamano = isset($archivos['adjuntar_rp']) && $archivos['adjuntar_rp']['error'] === UPLOAD_ERR_OK 
            ? $archivos['adjuntar_rp']['size'] : null;

        // Vincular parámetros
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->bindParam(':id_area', $datos['id_area']);
        $stmt->bindParam(':id_tipo_vinculacion', $datos['id_tipo_vinculacion']);
        $stmt->bindParam(':id_municipio_principal', $datos['id_municipio_principal']);
        $stmt->bindParam(':id_municipio_secundario', $id_municipio_secundario);
        $stmt->bindParam(':id_municipio_terciario', $id_municipio_terciario);
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
        $stmt->bindParam(':direccion_municipio_principal', $datos['direccion_municipio_principal']);
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

    // ================= MÉTODOS DE CONSULTA =================

    public function obtenerTodosContratistas() {
    $sql = "SELECT 
            p.id_persona, p.cedula, p.nombres, p.apellidos, 
            p.telefono, p.correo_personal, p.profesion,
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
            dc.rp_nombre_original,
            fp.nombre_archivo AS foto_perfil_nombre,
            fp.tipo_mime AS foto_perfil_tipo,
            fp.fecha_subida AS foto_perfil_fecha
            -- NOTA: No se incluye tamano porque no existe en fotos_perfil
        FROM detalle_contrato dc
        JOIN persona p ON dc.id_persona = p.id_persona
        LEFT JOIN area a ON dc.id_area = a.id_area
        LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
        LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
        LEFT JOIN municipio m2 ON dc.id_municipio_secundario = m2.id_municipio
        LEFT JOIN municipio m3 ON dc.id_municipio_terciario = m3.id_municipio
        LEFT JOIN fotos_perfil fp ON p.id_persona = fp.id_persona 
            AND fp.fecha_subida = (
                SELECT MAX(fecha_subida) 
                FROM fotos_perfil fp2 
                WHERE fp2.id_persona = p.id_persona
            )
        ORDER BY dc.created_at ASC";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

  public function obtenerContratistaPorId($id_detalle) {
    $sql = "SELECT 
                p.id_persona, p.nombres, p.apellidos, p.telefono, 
                p.cedula, p.fecha_registro, p.correo_personal, p.profesion,
                dc.*,
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
                dc.rp_tipo_mime, dc.rp_tamano,
                fp.id_foto, fp.nombre_archivo AS foto_nombre,
                fp.tipo_mime AS foto_tipo_mime,
                fp.contenido AS foto_contenido,
                fp.fecha_subida AS foto_fecha_subida
            FROM detalle_contrato dc
            JOIN persona p ON dc.id_persona = p.id_persona
            LEFT JOIN area a ON dc.id_area = a.id_area
            LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
            LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
            LEFT JOIN municipio m2 ON dc.id_municipio_secundario = m2.id_municipio
            LEFT JOIN municipio m3 ON dc.id_municipio_terciario = m3.id_municipio
            LEFT JOIN fotos_perfil fp ON p.id_persona = fp.id_persona 
                AND fp.fecha_subida = (
                    SELECT MAX(fecha_subida) 
                    FROM fotos_perfil fp2 
                    WHERE fp2.id_persona = p.id_persona
                )
            WHERE dc.id_detalle = :id_detalle";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':id_detalle', $id_detalle);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Convertir recursos de PostgreSQL a strings
    if ($resultado) {
        // Convertir contenido de foto si existe (PostgreSQL devuelve bytea como recurso)
        if (isset($resultado['foto_contenido']) && is_resource($resultado['foto_contenido'])) {
            $resultado['foto_contenido'] = stream_get_contents($resultado['foto_contenido']);
        }
        
        // Convertir otros archivos binarios si también son recursos
        $camposBinarios = ['cv_archivo', 'contrato_archivo', 'acta_inicio_archivo', 'rp_archivo'];
        foreach ($camposBinarios as $campo) {
            if (isset($resultado[$campo]) && is_resource($resultado[$campo])) {
                $resultado[$campo] = stream_get_contents($resultado[$campo]);
            }
        }
    }
    
    return $resultado;
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

    // ================= MÉTODOS DE MAPA =================

    public function obtenerContratistasParaMapa() {
        $sql = "SELECT 
                    p.id_persona, 
                    p.cedula, 
                    p.nombres, 
                    p.apellidos, 
                    p.telefono,
                    p.correo_personal,
                    dc.id_detalle, 
                    dc.numero_contrato, 
                    dc.fecha_inicio, 
                    dc.fecha_final,
                    dc.direccion_municipio_principal,
                    dc.direccion_municipio_secundario,
                    dc.direccion_municipio_terciario,
                    a.nombre AS area,
                    tv.nombre AS tipo_vinculacion,
                    m1.nombre AS municipio_principal,
                    m1.id_municipio AS id_municipio_principal,
                    m2.nombre AS municipio_secundario,
                    m2.id_municipio AS id_municipio_secundario,
                    m3.nombre AS municipio_terciario,
                    m3.id_municipio AS id_municipio_terciario,
                    dc.created_at
                FROM detalle_contrato dc
                JOIN persona p ON dc.id_persona = p.id_persona
                LEFT JOIN area a ON dc.id_area = a.id_area
                LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
                LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
                LEFT JOIN municipio m2 ON dc.id_municipio_secundario = m2.id_municipio
                LEFT JOIN municipio m3 ON dc.id_municipio_terciario = m3.id_municipio
                WHERE dc.direccion_municipio_principal IS NOT NULL 
                    AND dc.direccion_municipio_principal != ''
                    AND dc.id_municipio_principal IS NOT NULL
                ORDER BY dc.created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================= MÉTODOS DE ACTUALIZACIÓN =================

    public function actualizarContratista($id_detalle, $datos, $archivos = []) {
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
            
            // 2. Actualizar datos de persona (incluyendo profesion)
            $sqlPersona = "UPDATE persona SET 
                            nombres = :nombres,
                            apellidos = :apellidos,
                            cedula = :cedula,
                            telefono = :telefono,
                            correo_personal = :correo_personal,
                            profesion = :profesion
                        WHERE id_persona = :id_persona";
            
            $stmtPersona = $this->conn->prepare($sqlPersona);
            $stmtPersona->bindParam(':nombres', $datos['nombres']);
            $stmtPersona->bindParam(':apellidos', $datos['apellidos']);
            $stmtPersona->bindParam(':cedula', $datos['cedula']);
            $stmtPersona->bindParam(':telefono', $datos['telefono']);
            $stmtPersona->bindParam(':correo_personal', $datos['correo_personal']);
            $stmtPersona->bindParam(':profesion', $datos['profesion'] ?? null);
            $stmtPersona->bindParam(':id_persona', $id_persona);
            $stmtPersona->execute();
            
            // 3. Actualizar foto de perfil si se subió
            if (isset($archivos['foto_perfil']) && $archivos['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $this->personaModel->guardarFotoPerfil($id_persona, $archivos['foto_perfil']);
            }

            // 4. Formatear fechas
            $fecha_contrato = !empty($datos['fecha_contrato']) ? $this->formatearFecha($datos['fecha_contrato']) : null;
            $fecha_inicio = !empty($datos['fecha_inicio']) ? $this->formatearFecha($datos['fecha_inicio']) : null;
            $fecha_final = !empty($datos['fecha_final']) ? $this->formatearFecha($datos['fecha_final']) : null;
            $fecha_rp = !empty($datos['fecha_rp']) ? $this->formatearFecha($datos['fecha_rp']) : null;

            // 5. Actualizar detalle_contrato
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
                            direccion = :direccion,
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
            $stmtDetalle->bindParam(':direccion', $datos['direccion']);
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

    // ================= MÉTODOS UTILITARIOS =================

    public function obtenerProximoConsecutivo() {
        $sql = "SELECT COALESCE(MAX(id_detalle), 0) + 1 AS proximo FROM detalle_contrato";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['proximo'];
    }

    private function formatearFecha($fecha) {
        if (empty($fecha)) return null;
        
        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        
        return $fecha;
    }

    // ================= MÉTODOS ADICIONALES =================

    public function buscarContratistas($termino) {
        $sql = "SELECT 
                    p.id_persona, p.cedula, p.nombres, p.apellidos, 
                    p.telefono, p.correo_personal, p.profesion,
                    dc.id_detalle, dc.numero_contrato,
                    dc.fecha_contrato, dc.fecha_inicio, dc.fecha_final,
                    a.nombre AS area, 
                    tv.nombre AS tipo_vinculacion,
                    m1.nombre AS municipio_principal
                FROM detalle_contrato dc
                JOIN persona p ON dc.id_persona = p.id_persona
                LEFT JOIN area a ON dc.id_area = a.id_area
                LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
                LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
                WHERE p.nombres ILIKE :termino 
                    OR p.apellidos ILIKE :termino
                    OR p.cedula ILIKE :termino
                    OR p.profesion ILIKE :termino
                    OR dc.numero_contrato ILIKE :termino
                ORDER BY dc.created_at DESC
                LIMIT 50";
        
        $stmt = $this->conn->prepare($sql);
        $terminoBusqueda = "%" . $termino . "%";
        $stmt->bindParam(':termino', $terminoBusqueda);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarTotalContratistas() {
        $sql = "SELECT COUNT(*) as total FROM detalle_contrato";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total'];
    }

    public function eliminarContratista($id_detalle) {
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
            
            // 2. Eliminar detalle_contrato (esto debería activar ON DELETE CASCADE para fotos)
            $sqlDetalle = "DELETE FROM detalle_contrato WHERE id_detalle = :id_detalle";
            $stmtDetalle = $this->conn->prepare($sqlDetalle);
            $stmtDetalle->bindParam(':id_detalle', $id_detalle);
            $stmtDetalle->execute();
            
            // 3. Verificar si la persona tiene otros contratos
            $sqlContarContratos = "SELECT COUNT(*) FROM detalle_contrato WHERE id_persona = :id_persona";
            $stmtContarContratos = $this->conn->prepare($sqlContarContratos);
            $stmtContarContratos->bindParam(':id_persona', $id_persona);
            $stmtContarContratos->execute();
            $tieneOtrosContratos = $stmtContarContratos->fetchColumn() > 0;
            
            // 4. Si no tiene otros contratos, eliminar la persona y sus fotos
            if (!$tieneOtrosContratos) {
                $this->personaModel->eliminar($id_persona);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'mensaje' => 'Contratista eliminado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error en eliminarContratista: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>