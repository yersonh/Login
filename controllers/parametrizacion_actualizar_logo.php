<?php
header("Content-Type: application/json");
session_start();

// Habilitar errores para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "Acceso no autorizado"
    ]);
    exit;
}

try {
    // Incluir el controlador
    require_once __DIR__ . '/ConfiguracionControlador.php';
    $controlador = new ConfiguracionControlador();
    
    // Obtener la configuración actual
    $configActual = $controlador->obtenerDatos();
    
    // Preparar datos base para actualizar
    $datosActualizar = [
        'entidad' => trim($_POST['entidad'] ?? ($configActual['entidad'] ?? '')),
        'enlace_web' => trim($_POST['enlace_web'] ?? ($configActual['enlace_web'] ?? ''))
    ];
    
    // Manejar subida de archivo si existe
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['logo'];
        
        // Validaciones básicas
        $extensionesPermitidas = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception("Formato de archivo no permitido. Use PNG, JPG, SVG o GIF.");
        }
        
        if ($archivo['size'] > 2 * 1024 * 1024) {
            throw new Exception("El archivo es demasiado grande. Máximo 2MB permitido.");
        }
        
        // Crear directorio si no existe
        $directorioLogos = __DIR__ . '/../../imagenes/logos/';
        if (!file_exists($directorioLogos)) {
            if (!mkdir($directorioLogos, 0755, true)) {
                throw new Exception("No se pudo crear el directorio para logos.");
            }
        }
        
        // Generar nombre único
        $nombreArchivo = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaDestino = $directorioLogos . $nombreArchivo;
        
        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            // Guardar ruta relativa para la base de datos
            $datosActualizar['ruta_logo'] = 'imagenes/logos/' . $nombreArchivo;
            
            // Eliminar logo anterior si no es el predeterminado
            $logoActual = $configActual['ruta_logo'] ?? '';
            if (!empty($logoActual) && 
                $logoActual !== 'imagenes/gobernacion.png' &&
                file_exists(__DIR__ . '/../../' . $logoActual)) {
                @unlink(__DIR__ . '/../../' . $logoActual);
            }
        } else {
            throw new Exception("Error al subir el archivo. Verifique permisos.");
        }
    }
    
    // Si no se subió nuevo logo, mantener el actual
    if (!isset($datosActualizar['ruta_logo'])) {
        $datosActualizar['ruta_logo'] = $configActual['ruta_logo'] ?? 'imagenes/gobernacion.png';
    }
    
    // Actualizar en la base de datos usando el controlador
    $resultado = $controlador->actualizarDatos($datosActualizar);
    
    if ($resultado) {
        echo json_encode([
            "success" => true,
            "message" => "Configuración actualizada correctamente",
            "ruta_logo" => $datosActualizar['ruta_logo']
        ]);
    } else {
        throw new Exception("No se pudo actualizar la configuración");
    }
    
} catch (Exception $e) {
    error_log("Error en parametrizacion_actualizar_logo.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>