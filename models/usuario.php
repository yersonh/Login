<?php
class Usuario {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function insertar($id_persona, $correo, $password, $tipo_usuario = 'contratista', $activo = 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $fecha_registro = date('Y-m-d H:i:s');

        $sql = "INSERT INTO usuario (id_persona, correo, contrasena, tipo_usuario, activo, fecha_registro)
                VALUES (:id_persona, :correo, :password, :tipo_usuario, :activo, :fecha_registro)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':password', $hash);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':fecha_registro', $fecha_registro);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al insertar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function existeCorreo($correo) {
        $sql = "SELECT COUNT(*) FROM usuario WHERE correo = :correo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);

        try {
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar correo: " . $e->getMessage());
            return true;
        }
    }

    public function obtenerPorCorreo($correo) {
        $sql = "SELECT u.*, u.fecha_registro, u.fecha_creacion, 
                       p.nombres, p.apellidos, p.telefono, p.cedula
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.correo = :correo";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);

        try {
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                // Agregar un campo para indicar si tiene foto (opcional)
                $usuario['tiene_foto'] = $this->personaTieneFoto($usuario['id_persona']);
            }
            
            return $usuario;
        } catch (PDOException $e) {
            error_log("Error al obtener usuario por correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Método auxiliar para verificar si una persona tiene foto
     */
    private function personaTieneFoto($id_persona) {
        try {
            $sql = "SELECT COUNT(*) FROM fotos_perfil WHERE id_persona = :id_persona";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id_persona', $id_persona);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar foto de persona: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarTipoUsuario($id_usuario, $tipo_usuario) {
        $sql = "UPDATE usuario SET tipo_usuario = :tipo_usuario 
                WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':tipo_usuario', $tipo_usuario);
        $stmt->bindParam(':id_usuario', $id_usuario);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar tipo de usuario: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerTodos() {
        $sql = "SELECT u.id_usuario, u.correo, u.tipo_usuario, u.activo, u.fecha_registro,
                       p.nombres, p.apellidos, p.telefono, p.cedula
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                ORDER BY u.id_usuario";

        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener usuarios: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorEstado($estado = 'todos') {
        $sql = "SELECT u.id_usuario, u.correo, u.tipo_usuario, u.activo, u.fecha_registro,
                       u.fecha_aprobacion, u.notificado_aprobacion, u.aprobado_por,
                       p.nombres, p.apellidos, p.telefono, p.cedula
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                WHERE 1=1";
        
        // Aplicar filtro según estado
        if ($estado === 'pendientes') {
            $sql .= " AND u.activo IS FALSE AND u.fecha_aprobacion IS NULL";
        } elseif ($estado === 'activos') {
            $sql .= " AND u.activo IS TRUE";
        } elseif ($estado === 'inactivos') {
            $sql .= " AND u.activo IS FALSE AND u.fecha_aprobacion IS NOT NULL";
        }
        
        $sql .= " ORDER BY u.fecha_registro DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener usuarios por estado: " . $e->getMessage());
            return [];
        }
    }

    public function contarPorEstado() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN activo IS TRUE THEN 1 END) as activos,
                    COUNT(CASE WHEN activo IS FALSE THEN 1 END) as inactivos
                FROM usuario";
        
        $stmt = $this->conn->prepare($sql);
        
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al contar usuarios por estado: " . $e->getMessage());
            return ['total' => 0, 'activos' => 0, 'inactivos' => 0];
        }
    }

    public function obtenerPorId($id_usuario) {
        $sql = "SELECT u.*, p.nombres, p.apellidos, p.telefono, p.cedula
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener usuario por ID: " . $e->getMessage());
            return false;
        }
    }

    public function existeUsuarioParaPersona($id_persona) {
        $sql = "SELECT COUNT(*) FROM usuario WHERE id_persona = :id_persona";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_persona', $id_persona);
        
        try {
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error al verificar usuario para persona: " . $e->getMessage());
            return true; // Por seguridad, asume que existe
        }
    }

    public function aprobarUsuario($id_usuario, $admin_id) {
        $sql = "UPDATE usuario 
                SET activo = TRUE,
                    notificado_aprobacion = TRUE,
                    fecha_aprobacion = NOW(),
                    aprobado_por = :admin_id
                WHERE id_usuario = :id_usuario
                RETURNING id_persona, correo";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':id_usuario', $id_usuario);
        
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al aprobar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function desactivarUsuario($id_usuario) {
        $sql = "UPDATE usuario 
                SET activo = FALSE 
                WHERE id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al desactivar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function activarUsuario($id_usuario) {
        $sql = "UPDATE usuario 
                SET activo = TRUE 
                WHERE id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al activar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function esPrimeraAprobacion($id_usuario) {
        $sql = "SELECT fecha_aprobacion FROM usuario WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        
        try {
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            // Es primera aprobación si nunca ha sido aprobado (fecha_aprobacion es NULL)
            return $result && $result['fecha_aprobacion'] === null;
        } catch (PDOException $e) {
            error_log("Error al verificar primera aprobación: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerInfoParaCorreo($id_usuario) {
        $sql = "SELECT u.correo, p.nombres, p.apellidos, p.correo_personal, p.cedula
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                WHERE u.id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        
        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener info para correo: " . $e->getMessage());
            return false;
        }
    }

    public function desactivarUsuariosContratosVencidos() {
        try {
            // Consulta para obtener usuarios con contratos vencidos
            $sql = "SELECT DISTINCT u.id_usuario, u.correo, 
                           p.nombres, p.apellidos, p.correo_personal,
                           dc.fecha_final, dc.numero_contrato
                    FROM usuario u
                    JOIN persona p ON u.id_persona = p.id_persona
                    JOIN detalle_contrato dc ON p.id_persona = dc.id_persona
                    WHERE u.activo = true
                      AND u.tipo_usuario = 'contratista'
                      AND dc.fecha_final < CURRENT_DATE
                      -- Asegurar que no tiene otro contrato activo
                      AND NOT EXISTS (
                          SELECT 1 FROM detalle_contrato dc2
                          WHERE dc2.id_persona = p.id_persona
                            AND dc2.fecha_final >= CURRENT_DATE
                      )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $usuariosVencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $desactivados = 0;
            $errores = 0;
            
            foreach ($usuariosVencidos as $usuario) {
                try {
                    // Verificar una última vez que el usuario sigue activo
                    $sqlVerificar = "SELECT activo FROM usuario WHERE id_usuario = :id_usuario";
                    $stmtVerificar = $this->conn->prepare($sqlVerificar);
                    $stmtVerificar->bindParam(':id_usuario', $usuario['id_usuario']);
                    $stmtVerificar->execute();
                    $estado = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                    
                    if ($estado && $estado['activo'] === true) {
                        // USAR desactivarUsuarioConMotivo en lugar de desactivarUsuario
                        $desactivado = $this->desactivarUsuarioConMotivo(
                            $usuario['id_usuario'], 
                            'contrato_vencido'
                        );
                        
                        if ($desactivado) {
                            $desactivados++;
                            
                            // Registrar en log
                            error_log("[CONTRATO VENCIDO] Usuario desactivado: " . 
                                     $usuario['correo'] . 
                                     " (Contrato: " . $usuario['numero_contrato'] . 
                                     ", Fecha fin: " . $usuario['fecha_final'] . ")");
                            
                            // SOLO LOG, NO ENVIAR CORREO DESDE AQUÍ
                            error_log("[NOTIFICACIÓN] Debería notificar a " . $usuario['correo_personal'] . 
                                     " sobre vencimiento de contrato");
                        } else {
                            $errores++;
                            error_log("[ERROR] Error al desactivar usuario: " . $usuario['correo']);
                        }
                    } else {
                        error_log("[INFO] Usuario ya estaba inactivo: " . $usuario['correo']);
                    }
                } catch (Exception $e) {
                    $errores++;
                    error_log("[EXCEPCIÓN] Al desactivar usuario: " . $e->getMessage());
                }
            }
            
            return [
                'total_encontrados' => count($usuariosVencidos),
                'desactivados' => $desactivados,
                'errores' => $errores,
                'usuarios_vencidos' => $usuariosVencidos // ← DEVOLVER LOS USUARIOS PARA NOTIFICAR DESDE LA API
            ];
            
        } catch (Exception $e) {
            error_log("[ERROR] Error en desactivarUsuariosContratosVencidos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notifica contratos por vencer (15 días antes)
     */
    public function notificarContratosPorVencer($diasAnticipacion = 15) {
        try {
            $sql = "SELECT DISTINCT u.id_usuario, u.correo, 
                           p.nombres, p.apellidos, p.correo_personal,
                           dc.fecha_final, dc.numero_contrato
                    FROM usuario u
                    JOIN persona p ON u.id_persona = p.id_persona
                    JOIN detalle_contrato dc ON p.id_persona = dc.id_persona
                    WHERE u.activo = true
                      AND u.tipo_usuario = 'contratista'
                      AND dc.fecha_final >= CURRENT_DATE
                      AND dc.fecha_final <= CURRENT_DATE + INTERVAL :dias DAY
                      -- Verificar que este es su contrato más reciente
                      AND dc.fecha_final = (
                          SELECT MAX(dc2.fecha_final)
                          FROM detalle_contrato dc2
                          WHERE dc2.id_persona = p.id_persona
                      )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':dias', $diasAnticipacion, PDO::PARAM_INT);
            $stmt->execute();
            $contratosPorVencer = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $contratosParaNotificar = [];
            
            foreach ($contratosPorVencer as $contrato) {
                try {
                    // Calcular días restantes
                    $fechaFinal = new DateTime($contrato['fecha_final']);
                    $hoy = new DateTime();
                    $diasRestantes = $hoy->diff($fechaFinal)->days;
                    
                    // Solo notificar si realmente está por vencer
                    if ($diasRestantes <= $diasAnticipacion) {
                        $contratosParaNotificar[] = [
                            'usuario' => $contrato,
                            'dias_restantes' => $diasRestantes
                        ];
                    }
                    
                } catch (Exception $e) {
                    error_log("[EXCEPCIÓN] Al procesar contrato por vencer: " . $e->getMessage());
                }
            }
            
            return [
                'contratos_por_vencer' => count($contratosPorVencer),
                'contratos_para_notificar' => $contratosParaNotificar
            ];
            
        } catch (Exception $e) {
            error_log("[ERROR] Error en notificarContratosPorVencer: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Método unificado para verificar vencimientos
     */
    public function verificarVencimientosCompleto($diasAnticipacion = 15) {
        $resultados = [];
        
        // 1. Notificar contratos por vencer
        $resultados['preventivas'] = $this->notificarContratosPorVencer($diasAnticipacion);
        
        // 2. Desactivar contratos vencidos
        $resultados['vencidos'] = $this->desactivarUsuariosContratosVencidos();
        
        return $resultados;
    }

    /**
     * Verificar si un usuario específico tiene contrato vencido
     */
    public function tieneContratoVencido($id_usuario) {
        $sql = "SELECT COUNT(*) as vencido
                FROM usuario u
                JOIN persona p ON u.id_persona = p.id_persona
                JOIN detalle_contrato dc ON p.id_persona = dc.id_persona
                WHERE u.id_usuario = :id_usuario
                  AND u.tipo_usuario = 'contratista'
                  AND dc.fecha_final < CURRENT_DATE
                  AND NOT EXISTS (
                      SELECT 1 FROM detalle_contrato dc2
                      WHERE dc2.id_persona = p.id_persona
                        AND dc2.fecha_final >= CURRENT_DATE
                  )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado && $resultado['vencido'] > 0;
    }

    /**
     * Desactivar usuario con motivo específico
     */
    public function desactivarUsuarioConMotivo($id_usuario, $motivo = 'administrador') {
        $sql = "UPDATE usuario 
                SET activo = FALSE,
                    motivo_desactivacion = :motivo,
                    fecha_desactivacion = NOW()
                WHERE id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario);
        $stmt->bindParam(':motivo', $motivo);
        
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al desactivar usuario con motivo: " . $e->getMessage());
            return false;
        }
    }
}
?>