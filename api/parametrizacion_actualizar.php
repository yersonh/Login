<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/ConfiguracionControlador.php';

try {
    // Verificar que sea una solicitud POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos JSON del cuerpo de la solicitud
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        throw new Exception('Datos JSON inválidos');
    }

    // Validar campos requeridos
    $camposRequeridos = ['version_sistema', 'desarrollado_por', 'correo_contacto'];
    foreach ($camposRequeridos as $campo) {
        if (empty(trim($input[$campo] ?? ''))) {
            throw new Exception("El campo $campo es requerido");
        }
    }

    // Obtener datos con valores por defecto
    $version = trim($input['version_sistema'] ?? '');
    $tipoLicencia = trim($input['tipo_licencia'] ?? '');
    $validaHasta = trim($input['valida_hasta'] ?? '');
    $desarrolladoPor = trim($input['desarrollado_por'] ?? '');
    $direccion = trim($input['direccion'] ?? '');
    $correoContacto = trim($input['correo_contacto'] ?? '');
    $telefono = trim($input['telefono'] ?? '');

    // Actualizar usando el controlador
    $controlador = new ConfiguracionControlador();
    
    if ($controlador->actualizarDatos($version, $tipoLicencia, $validaHasta, $desarrolladoPor, $direccion, $correoContacto, $telefono)) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuración del sistema actualizada correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar en la base de datos');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}