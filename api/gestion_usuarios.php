<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/usuario.php';

// Verificar que sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado']);
    exit();
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['accion'])) {
        throw new Exception('Acción no especificada');
    }
    
    $accion = $input['accion'];
    $database = new Database();
    $db = $database->conectar();
    $usuarioModel = new Usuario($db);
    
    switch ($accion) {
        case 'cambiar_estado':
            if (!isset($input['id_usuario']) || !isset($input['nuevo_estado'])) {
                throw new Exception('Datos incompletos para cambiar estado');
            }
            
            $idUsuario = (int)$input['id_usuario'];
            $nuevoEstado = (int)$input['nuevo_estado']; // 1 para activo, 0 para inactivo
            
            // Verificar que el usuario existe
            $usuarios = $usuarioModel->obtenerTodos();
            $usuarioExiste = false;
            foreach ($usuarios as $usuario) {
                if ($usuario['id_usuario'] == $idUsuario) {
                    $usuarioExiste = true;
                    break;
                }
            }
            
            if (!$usuarioExiste) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Actualizar el estado en la base de datos
            $sql = "UPDATE usuario SET activo = :activo WHERE id_usuario = :id_usuario";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':activo', $nuevoEstado, PDO::PARAM_INT);
            $stmt->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Obtener el nombre del usuario para el mensaje
                $sqlNombre = "SELECT p.nombres, p.apellidos 
                             FROM usuario u 
                             JOIN persona p ON u.id_persona = p.id_persona 
                             WHERE u.id_usuario = :id_usuario";
                $stmtNombre = $db->prepare($sqlNombre);
                $stmtNombre->bindParam(':id_usuario', $idUsuario, PDO::PARAM_INT);
                $stmtNombre->execute();
                $datosUsuario = $stmtNombre->fetch(PDO::FETCH_ASSOC);
                
                $nombreCompleto = $datosUsuario ? 
                    htmlspecialchars(trim($datosUsuario['nombres'] . ' ' . $datosUsuario['apellidos'])) : 
                    'Usuario';
                
                $mensaje = $nuevoEstado == 1 ? 
                    "Usuario '$nombreCompleto' activado exitosamente" : 
                    "Usuario '$nombreCompleto' desactivado exitosamente";
                
                echo json_encode([
                    'success' => true,
                    'message' => $mensaje,
                    'nuevo_estado' => $nuevoEstado,
                    'estado_texto' => $nuevoEstado == 1 ? 'Activo' : 'Inactivo',
                    'estado_clase' => $nuevoEstado == 1 ? 'status-active' : 'status-blocked'
                ]);
            } else {
                throw new Exception('Error al actualizar el estado del usuario');
            }
            break;
            
        case 'obtener_usuario':
            // Para futuras acciones
            if (!isset($input['id_usuario'])) {
                throw new Exception('ID de usuario no especificado');
            }
            
            $idUsuario = (int)$input['id_usuario'];
            echo json_encode(['success' => true, 'message' => 'Función no implementada aún']);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    error_log("Error en gestión_usuarios.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>