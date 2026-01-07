<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';
require_once __DIR__ . '/../helpers/EmailHelper.php';
session_start();

// Verificar autenticación y permisos
//if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    //http_response_code(401);
    //echo json_encode(['success' => false, 'error' => 'No autorizado']);
  //  exit();
//}
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1; // ID de admin para pruebas
    $_SESSION['tipo_usuario'] = 'administrador';
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
                            error_log("[CORREO] Correo de aprobación enviado a: " . 
                                     $infoUsuario['correo_personal'] . 
                                     " (Usuario ID: $id_usuario)");
                        } else {
                            error_log("[ADVERTENCIA] No se pudo enviar correo de aprobación a: " . 
                                     $infoUsuario['correo_personal']);
                        }
                    } else {
                        error_log("[ADVERTENCIA] Usuario sin correo personal para notificación (ID: $id_usuario)");
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
                    // Enviar correos de notificación para contratos por vencer
                    $correosPreventivos = 0;
                    if (isset($resultado['preventivas']['contratos_para_notificar'])) {
                        foreach ($resultado['preventivas']['contratos_para_notificar'] as $contrato) {
                            $usuario = $contrato['usuario'];
                            if (!empty($usuario['correo_personal'])) {
                                $nombreCompleto = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
                                $enviado = EmailHelper::enviarNotificacionContratoPorVencer(
                                    $usuario['correo_personal'],
                                    $nombreCompleto,
                                    $usuario['fecha_final'],
                                    $contrato['dias_restantes'], 
                                    $usuario['numero_contrato']
                                );
                                if ($enviado) $correosPreventivos++;
                            }
                        }
                    }
                    
                    // Enviar correos de notificación para contratos vencidos
                    $correosVencidos = 0;
                    if (isset($resultado['vencidos']['usuarios_vencidos'])) {
                        foreach ($resultado['vencidos']['usuarios_vencidos'] as $usuario) {
                            if (!empty($usuario['correo_personal'])) {
                                $nombreCompleto = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
                                $enviado = EmailHelper::enviarCorreoContratoVencido(
                                    $usuario['correo_personal'],
                                    $nombreCompleto,
                                    $usuario['numero_contrato'],
                                    $usuario['fecha_final']
                                );
                                if ($enviado) $correosVencidos++;
                            }
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Verificación de vencimientos completada',
                        'resultados' => $resultado,
                        'correos_enviados' => [
                            'preventivos' => $correosPreventivos,
                            'vencidos' => $correosVencidos
                        ]
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
                    $correosEnviados = 0;
                    
                    if (isset($resultado['contratos_para_notificar'])) {
                        foreach ($resultado['contratos_para_notificar'] as $contrato) {
                            $usuario = $contrato['usuario'];
                            if (!empty($usuario['correo_personal'])) {
                                $nombreCompleto = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
                                $enviado = EmailHelper::enviarNotificacionContratoPorVencer(
                                    $usuario['correo_personal'],
                                    $nombreCompleto,
                                    $usuario['fecha_final'],
                                    $contrato['dias_restantes'],
                                    $usuario['numero_contrato']
                                );
                                if ($enviado) $correosEnviados++;
                            }
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Notificaciones de contratos por vencer enviadas',
                        'resultados' => $resultado,
                        'correos_enviados' => $correosEnviados
                    ]);
                } else {
                    throw new Exception('Error al notificar contratos por vencer');
                }
                break;
                
            case 'desactivar_contratos_vencidos':
                // Solo desactivar usuarios con contratos vencidos
                $resultado = $usuarioModel->desactivarUsuariosContratosVencidos();
                
                if ($resultado !== false) {
                    $correosEnviados = 0;
                    
                    // ENVIAR CORREOS DE NOTIFICACIÓN DE VENCIMIENTO
                    if (isset($resultado['usuarios_vencidos'])) {
                        foreach ($resultado['usuarios_vencidos'] as $usuario) {
                            if (!empty($usuario['correo_personal'])) {
                                $nombreCompleto = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
                                
                                $enviado = EmailHelper::enviarCorreoContratoVencido(
                                    $usuario['correo_personal'],
                                    $nombreCompleto,
                                    $usuario['numero_contrato'],
                                    $usuario['fecha_final']
                                );
                                
                                if ($enviado) {
                                    $correosEnviados++;
                                    error_log("[CORREO] Correo de vencimiento enviado a: " . 
                                             $usuario['correo_personal']);
                                }
                            }
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuarios con contratos vencidos desactivados',
                        'resultados' => $resultado,
                        'correos_enviados' => $correosEnviados
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