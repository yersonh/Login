<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/ConfiguracionControlador.php';

try {
    // Verificar que sea una solicitud POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Verificar si se recibió el archivo
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió el archivo de imagen');
    }

    // Obtener datos del formulario
    $entidad = isset($_POST['entidad']) ? trim($_POST['entidad']) : '';
    $enlaceWeb = isset($_POST['enlace_web']) ? trim($_POST['enlace_web']) : '';

    // Validar campos requeridos
    if (empty($entidad)) {
        throw new Exception('El campo entidad es requerido');
    }

    // Obtener información del archivo
    $file = $_FILES['logo'];
    $fileName = basename($file['name']);
    $fileTmpName = $file['tmp_name'];
    
    // Ruta de destino (en /imagenes/ con el nombre original)
    $uploadDir = __DIR__ . '/../imagenes/';
    $destination = $uploadDir . $fileName;
    
    // Mover el archivo a la carpeta de destino
    if (!move_uploaded_file($fileTmpName, $destination)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Ruta relativa para la base de datos
    $rutaRelativa = '/imagenes/' . $fileName;
    
    // Actualizar usando el controlador
    $controlador = new ConfiguracionControlador();
    
    if ($controlador->actualizarLogo($rutaRelativa, $entidad, $enlaceWeb)) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuración actualizada correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar en la base de datos');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}