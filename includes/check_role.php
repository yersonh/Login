<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'role' => 'none', 
        'authenticated' => false,
        'message' => 'No autenticado'
    ]);
    exit();
}

echo json_encode([
    'role' => $_SESSION['tipo_usuario'] ?? 'usuario',
    'authenticated' => true,
    'email' => $_SESSION['correo'] ?? '',
    'name' => ($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? ''),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>