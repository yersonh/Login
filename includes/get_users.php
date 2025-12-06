<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Solo administradores pueden acceder
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] ?? '') !== 'administrador') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

try {
    $database = new Database();
    $db = $database->conectar();
    
    $stmt = $db->prepare("SELECT 
                            u.id_usuario,
                            u.correo,
                            u.tipo_usuario,
                            p.nombres,
                            p.apellidos
                          FROM usuario u
                          INNER JOIN persona p ON u.id_persona = p.id_persona
                          ORDER BY u.correo");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'users' => $users]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>