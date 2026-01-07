<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';
session_start();

// Verificar autenticación y permisos
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->conectar();
    $usuarioModel = new Usuario($db);
    
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['accion'])) {
        $admin_id = $_SESSION['usuario_id'];
        
        switch ($input['accion']) {
            case 'aprobar_usuario':
                if (!isset($input['id_usuario'])) {
                    throw new Exception('ID de usuario no especificado');
                }
                
                $id_usuario = intval($input['id_usuario']);
                $correoEnviado = false;
                
                // **PASO 1: Verificar si es primera aprobación ANTES de aprobar**
                if ($usuarioModel->esPrimeraAprobacion($id_usuario)) {
                    // **PASO 2: Obtener información para el correo ANTES de aprobar**
                    $infoUsuario = $usuarioModel->obtenerInfoParaCorreo($id_usuario);
                    
                    if ($infoUsuario && !empty($infoUsuario['correo_personal'])) {
                        $nombreCompleto = trim($infoUsuario['nombres'] . ' ' . $infoUsuario['apellidos']);
                        
                        // **PASO 3: Enviar correo usando EmailHelper ANTES de aprobar**
                        $correoEnviado = EmailHelper::enviarCorreoAprobacion(
                            $infoUsuario['correo_personal'],
                            $nombreCompleto,
                            $infoUsuario['correo']
                        );
                        
                        if ($correoEnviado) {
                            error_log("✅ Correo de aprobación enviado a: " . 
                                     $infoUsuario['correo_personal'] . 
                                     " (Usuario ID: $id_usuario)");
                        } else {
                            error_log("⚠️ No se pudo enviar correo de aprobación a: " . 
                                     $infoUsuario['correo_personal']);
                        }
                    } else {
                        error_log("⚠️ Usuario sin correo personal para notificación (ID: $id_usuario)");
                    }
                }
                
                // **PASO 4: FINALMENTE aprobar el usuario**
                $resultado = $usuarioModel->aprobarUsuario($id_usuario, $admin_id);
                
                if ($resultado) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuario aprobado correctamente',
                        'estado_texto' => 'Activo',
                        'estado_clase' => 'status-active',
                        'correo_enviado' => $correoEnviado
                    ]);
                } else {
                    throw new Exception('Error al aprobar el usuario');
                }
                break;
                
            case 'activar_usuario':
                if (!isset($input['id_usuario'])) {
                    throw new Exception('ID de usuario no especificado');
                }
                
                $id_usuario = intval($input['id_usuario']);
                
                // Solo activar (sin notificar) - para usuarios que ya fueron aprobados antes
                $resultado = $usuarioModel->activarUsuario($id_usuario);
                
                if ($resultado) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuario activado correctamente',
                        'estado_texto' => 'Activo',
                        'estado_clase' => 'status-active'
                    ]);
                } else {
                    throw new Exception('Error al activar el usuario');
                }
                break;
                
            case 'desactivar_usuario':
                if (!isset($input['id_usuario'])) {
                    throw new Exception('ID de usuario no especificado');
                }
                
                $id_usuario = intval($input['id_usuario']);
                
                // Desactivar usuario (sin notificar)
                $resultado = $usuarioModel->desactivarUsuario($id_usuario);
                
                if ($resultado) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuario desactivado correctamente',
                        'estado_texto' => 'Inactivo',
                        'estado_clase' => 'status-blocked'
                    ]);
                } else {
                    throw new Exception('Error al desactivar el usuario');
                }
                break;
                
            case 'mantener_pendiente':
                // Solo cambiamos el estado visual, no hacemos nada en BD
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario mantenido como pendiente'
                ]);
                break;
                
            // ================= NUEVAS ACCIONES PARA VENCIMIENTOS =================
            
            case 'verificar_vencimientos':
                // Verificación completa: notifica contratos por vencer y desactiva vencidos
                $diasAnticipacion = isset($input['dias_anticipacion']) ? intval($input['dias_anticipacion']) : 15;
                $resultado = $usuarioModel->verificarVencimientosCompleto($diasAnticipacion);
                
                if ($resultado !== false) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Verificación de vencimientos completada',
                        'resultados' => $resultado
                    ]);
                } else {
                    throw new Exception('Error en la verificación de vencimientos');
                }
                break;
                
            case 'notificar_contratos_por_vencer':
                // Solo notificar contratos por vencer (preventivo)
                $diasAnticipacion = isset($input['dias_anticipacion']) ? intval($input['dias_anticipacion']) : 15;
                $resultado = $usuarioModel->notificarContratosPorVencer($diasAnticipacion);
                
                if ($resultado !== false) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Notificaciones de contratos por vencer enviadas',
                        'resultados' => $resultado
                    ]);
                } else {
                    throw new Exception('Error al notificar contratos por vencer');
                }
                break;
                
            case 'desactivar_contratos_vencidos':
                // Solo desactivar usuarios con contratos vencidos
                $resultado = $usuarioModel->desactivarUsuariosContratosVencidos();
                
                if ($resultado !== false) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuarios con contratos vencidos desactivados',
                        'resultados' => $resultado
                    ]);
                } else {
                    throw new Exception('Error al desactivar contratos vencidos');
                }
                break;
                
            case 'verificar_contrato_usuario':
                // Verificar si un usuario específico tiene contrato vencido
                if (!isset($input['id_usuario'])) {
                    throw new Exception('ID de usuario no especificado');
                }
                
                $id_usuario = intval($input['id_usuario']);
                $tieneContratoVencido = $usuarioModel->tieneContratoVencido($id_usuario);
                
                echo json_encode([
                    'success' => true,
                    'tiene_contrato_vencido' => $tieneContratoVencido,
                    'id_usuario' => $id_usuario
                ]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    } else {
        throw new Exception('Método no permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>