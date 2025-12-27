<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';
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
        switch ($input['accion']) {
            case 'cambiar_estado':
                if (!isset($input['id_usuario']) || !isset($input['nuevo_estado'])) {
                    throw new Exception('Datos incompletos');
                }
                
                $id_usuario = intval($input['id_usuario']);
                $nuevo_estado = intval($input['nuevo_estado']);
                $admin_id = $_SESSION['usuario_id'];
                
                // Ejecutar cambio de estado
                $resultado = $usuarioModel->cambiarEstado($id_usuario, $nuevo_estado, $admin_id);
                
                if ($resultado) {
                    $estado_texto = $nuevo_estado == 1 ? 'Activo' : 'Inactivo';
                    $estado_clase = $nuevo_estado == 1 ? 'status-active' : 'status-blocked';
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Estado actualizado correctamente',
                        'estado_texto' => $estado_texto,
                        'estado_clase' => $estado_clase
                    ]);
                } else {
                    throw new Exception('Error al actualizar el estado');
                }
                break;
                
            case 'aprobar_usuario':
                if (!isset($input['id_usuario'])) {
                    throw new Exception('ID de usuario no especificado');
                }
                
                $id_usuario = intval($input['id_usuario']);
                $admin_id = $_SESSION['usuario_id'];
                
                // Activar usuario (aprobar)
                $resultado = $usuarioModel->cambiarEstado($id_usuario, 1, $admin_id);
                
                if ($resultado) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Usuario aprobado correctamente',
                        'estado_texto' => 'Activo',
                        'estado_clase' => 'status-active'
                    ]);
                } else {
                    throw new Exception('Error al aprobar el usuario');
                }
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