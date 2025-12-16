<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../controllers/ConfiguracionControlador.php';

try {
    // Verificar que sea una solicitud POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener datos del formulario
    $entidad = isset($_POST['entidad']) ? trim($_POST['entidad']) : '';
    $enlaceWeb = isset($_POST['enlace_web']) ? trim($_POST['enlace_web']) : '';
    $nit = isset($_POST['nit']) ? trim($_POST['nit']) : '';

    // Validar campos requeridos
    if (empty($entidad)) {
        throw new Exception('El campo entidad es requerido');
    }
    if (empty($enlaceWeb)) {
        throw new Exception('El campo enlace web es requerido');
    }
    if (empty($nit)) {
        throw new Exception('El campo NIT es requerido');
    }

    // Manejo del archivo de logo (OPCIONAL)
    $rutaRelativa = null;
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Obtener información del archivo
        $file = $_FILES['logo'];
        $fileName = basename($file['name']);
        $fileTmpName = $file['tmp_name'];
        
        // Ruta de destino (en /imagenes/ con el nombre original)
        $uploadDir = __DIR__ . '/../imagenes/';
        $destination = $uploadDir . $fileName;
        
        // Asegurar que la carpeta existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Mover el archivo a la carpeta de destino
        if (!move_uploaded_file($fileTmpName, $destination)) {
            throw new Exception('Error al guardar el archivo');
        }
        
        // Ruta relativa para la base de datos
        $rutaRelativa = '/imagenes/' . $fileName;
    } else {
        // Si no se envió un nuevo logo, mantener el existente
        // Necesitarás obtener la ruta actual de la base de datos
        $controlador = new ConfiguracionControlador();
        $configActual = $controlador->obtenerDatos();
        $rutaRelativa = $configActual['ruta_logo'] ?? null;
    }
    
    // Actualizar usando el controlador
    $controlador = new ConfiguracionControlador();
    
    if ($controlador->actualizarLogo($rutaRelativa, $entidad, $nit, $enlaceWeb)) {
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