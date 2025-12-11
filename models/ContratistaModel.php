<?php
require_once 'Persona.php';
require_once 'Usuario.php';

class ContratistaModel {
    private $conn;
    private $personaModel;
    private $usuarioModel;

    public function __construct($db) {
        $this->conn = $db;
        $this->personaModel = new Persona($db);
        $this->usuarioModel = new Usuario($db);
    }

    /**
     * Registra un contratista completo (persona + contrato + usuario)
     */
    public function registrarContratistaCompleto($datos) {
        // Iniciar transacción
        $this->conn->beginTransaction();
        
        try {
            // 1. VERIFICAR SI YA EXISTE
            if ($this->existeContratista($datos['cedula'], $datos['numero_contrato'])) {
                throw new Exception('Ya existe un contratista con esa cédula o número de contrato');
            }

            // 2. SEPARAR NOMBRES Y APELLIDOS
            $nombreCompleto = $datos['nombre_completo'];
            $partesNombre = $this->separarNombresApellidos($nombreCompleto);

            // 3. INSERTAR PERSONA
            $id_persona = $this->personaModel->insertar(
                $datos['cedula'],
                $partesNombre['nombres'],
                $partesNombre['apellidos'],
                $datos['celular']
            );

            if (!$id_persona) {
                throw new Exception('Error al registrar los datos personales');
            }

            // 4. INSERTAR DETALLE DE CONTRATO
            $id_detalle = $this->insertarDetalleContrato($id_persona, $datos);
            
            // 5. CREAR USUARIO AUTOMÁTICAMENTE
            $credenciales = $this->crearUsuarioAutomatico($id_persona, $datos['correo']);
            
            // 6. ACTUALIZAR SEJ EN DETALLE_CONTRATO (si aplica)
            if (isset($datos['sej'])) {
                $this->actualizarSEJ($id_detalle, $datos['sej']);
            }

            // Confirmar transacción
            $this->conn->commit();
            
            return [
                'success' => true,
                'id_persona' => $id_persona,
                'id_detalle' => $id_detalle,
                'id_usuario' => $credenciales['id_usuario'],
                'password_temporal' => $credenciales['password_temporal']
            ];
            
        } catch (Exception $e) {
            // Revertir en caso de error
            $this->conn->rollBack();
            error_log("Error en registrarContratistaCompleto: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si ya existe un contratista con esa cédula o número de contrato
     */
    private function existeContratista($cedula, $numero_contrato) {
        // Verificar en persona
        $sql_persona = "SELECT COUNT(*) FROM persona WHERE cedula = :cedula";
        $stmt = $this->conn->prepare($sql_persona);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        $existePersona = $stmt->fetchColumn() > 0;

        // Verificar en detalle_contrato
        $sql_contrato = "SELECT COUNT(*) FROM detalle_contrato WHERE numero_contrato = :numero_contrato";
        $stmt = $this->conn->prepare($sql_contrato);
        $stmt->bindParam(':numero_contrato', $numero_contrato);
        $stmt->execute();
        $existeContrato = $stmt->fetchColumn() > 0;

        return $existePersona || $existeContrato;
    }

    /**
     * Separa nombres y apellidos del nombre completo
     */
    private function separarNombresApellidos($nombreCompleto) {
        $partes = explode(' ', trim($nombreCompleto));
        
        if (count($partes) >= 2) {
            $nombres = array_shift($partes); // Primer nombre
            $apellidos = implode(' ', $partes); // El resto son apellidos
        } else {
            $nombres = $nombreCompleto;
            $apellidos = '';
        }
        
        return [
            'nombres' => $nombres,
            'apellidos' => $apellidos
        ];
    }

    /**
     * Inserta el detalle del contrato
     */
    private function insertarDetalleContrato($id_persona, $datos) {
        $sql = "INSERT INTO detalle_contrato (
            id_persona, id_area, id_tipo_vinculacion,
            id_municipio_principal, id_municipio_secundario, id_municipio_terciario,
            numero_contrato, fecha_contrato, fecha_inicio, fecha_final,
            duracion_contrato, numero_registro_presupuestal, fecha_rp, direccion
        ) VALUES (
            :id_persona, :id_area, :id_tipo_vinculacion,
            :id_municipio_principal, :id_municipio_secundario, :id_municipio_terciario,
            :numero_contrato, :fecha_contrato, :fecha_inicio, :fecha_final,
            :duracion_contrato, :numero_registro_presupuestal, :fecha_rp, :direccion
        ) RETURNING id_detalle";

        $stmt = $this->conn->prepare($sql);
        
        // Formatear fechas de "dd/mm/aaaa" a "aaaa-mm-dd"
        $fecha_contrato = $this->formatearFecha($datos['fecha_contrato']);
        $fecha_inicio = $this->formatearFecha($datos['fecha_inicio']);
        $fecha_final = $this->formatearFecha($datos['fecha_final']);
        $fecha_rp = !empty($datos['fecha_rp']) ? $this->formatearFecha($datos['fecha_rp']) : null;

        // Bind parameters
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

        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['id_detalle'];
    }

    /**
     * Crea usuario automáticamente
     */
    private function crearUsuarioAutomatico($id_persona, $correo) {
        // Verificar si el correo ya está registrado
        if ($this->usuarioModel->existeCorreo($correo)) {
            throw new Exception('El correo electrónico ya está registrado en el sistema');
        }

        // Generar contraseña temporal (puede ser la cédula o aleatoria)
        $password_temporal = $this->generarPasswordTemporal();
        
        // Insertar usuario
        $success = $this->usuarioModel->insertar(
            $id_persona,
            $correo,
            $password_temporal,
            'contratista',  // Tipo de usuario fijo
            true            // Activo
        );

        if (!$success) {
            throw new Exception('Error al crear el usuario');
        }

        // Obtener el ID del usuario recién creado
        $usuario = $this->usuarioModel->obtenerPorCorreo($correo);
        
        return [
            'id_usuario' => $usuario['id_usuario'],
            'password_temporal' => $password_temporal
        ];
    }

    /**
     * Genera una contraseña temporal
     */
    private function generarPasswordTemporal() {
        // Puedes usar diferentes estrategias:
        // 1. Últimos 4 dígitos de la cédula + "Meta"
        // 2. Generar aleatorio
        // 3. Fecha actual
        
        // Por ahora, generamos una aleatoria
        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        return $password;
    }

    /**
     * Formatea fecha de dd/mm/aaaa a aaaa-mm-dd
     */
    private function formatearFecha($fecha) {
        if (empty($fecha)) return null;
        
        $partes = explode('/', $fecha);
        if (count($partes) === 3) {
            return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        
        return $fecha; // Si ya está en formato correcto
    }

    /**
     * Actualiza el SEJ en el detalle del contrato
     */
    private function actualizarSEJ($id_detalle, $sej) {
        $sql = "UPDATE detalle_contrato SET sej = :sej WHERE id_detalle = :id_detalle";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':sej', $sej);
        $stmt->bindParam(':id_detalle', $id_detalle);
        
        return $stmt->execute();
    }

    /**
     * Método para obtener todos los contratistas
     */
    public function obtenerTodosContratistas() {
        $sql = "SELECT 
                    p.id_persona, p.cedula, p.nombres, p.apellidos, p.telefono,
                    dc.id_detalle, dc.numero_contrato, dc.fecha_inicio, dc.fecha_final,
                    a.nombre AS area, tv.nombre AS tipo_vinculacion,
                    m1.nombre AS municipio_principal,
                    u.correo, u.activo AS usuario_activo
                FROM detalle_contrato dc
                JOIN persona p ON dc.id_persona = p.id_persona
                LEFT JOIN area a ON dc.id_area = a.id_area
                LEFT JOIN tipo_vinculacion tv ON dc.id_tipo_vinculacion = tv.id_tipo
                LEFT JOIN municipio m1 ON dc.id_municipio_principal = m1.id_municipio
                LEFT JOIN usuario u ON p.id_persona = u.id_persona
                ORDER BY dc.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>