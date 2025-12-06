<?php
// test_ajax.php - Prueba simple
header('Content-Type: application/json');

// Simular sesión
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['tipo_usuario'] = 'asistente';
$_SESSION['correo'] = 'asistente@test.com';

echo json_encode([
    'success' => true,
    'message' => 'Test funcionando',
    'test' => 'OK'
]);
exit();
?>