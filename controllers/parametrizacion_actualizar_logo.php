<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    echo json_encode([
        "success" => false,
        "error" => "Acceso no autorizado"
    ]);
    exit;
}

try {
    // Incluir el controlador
    require_once 'ConfiguracionControlador.php';
    $controlador = new ConfiguracionControlador();
    
    // Obtener la configuración actual primero
    $configActual = $controlador->obtenerDatos();
    
    // Preparar datos para actualizar (mantener lo que ya existe)
    $datosActualizar = [
        'entidad' => trim($_POST['entidad'] ?? ($configActual['entidad'] ?? '')),
        'enlace_web' => trim($_POST['enlace_web'] ?? ($configActual['enlace_web'] ?? '')),
        'ruta_logo' => $configActual['ruta_logo'] ?? '' // Mantener actual por defecto
    ];
    
    // Manejar subida de archivo si existe
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['logo'];
        
        // Validaciones
        $extensionesPermitidas = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception("Formato de archivo no permitido. Use PNG, JPG, SVG o GIF.");
        }
        
        if ($archivo['size'] > 2 * 1024 * 1024) { // 2MB
            throw new Exception("El archivo es demasiado grande. Máximo 2MB permitido.");
        }
        
        // Crear directorio si no existe
        $directorioLogos = '../../imagenes/logos/';
        if (!file_exists($directorioLogos)) {
            mkdir($directorioLogos, 0777, true);
        }
        
        // Generar nombre único
        $nombreArchivo = 'logo_' . time() . '_' . uniqid() . '.' . $extension;
        $rutaDestino = $directorioLogos . $nombreArchivo;
        
        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $datosActualizar['ruta_logo'] = $rutaDestino;
            
            // Opcional: Eliminar logo anterior si no es el predeterminado
            if (!empty($configActual['ruta_logo']) && 
                $configActual['ruta_logo'] !== '../../imagenes/gobernacion.png' &&
                file_exists($configActual['ruta_logo'])) {
                @unlink($configActual['ruta_logo']);
            }
        } else {
            throw new Exception("Error al subir el archivo.");
        }
    }
    
    // También necesitamos mantener los otros datos existentes
    if (!empty($configActual)) {
        $datosActualizar['version_sistema'] = $configActual['version_sistema'] ?? '';
        $datosActualizar['tipo_licencia'] = $configActual['tipo_licencia'] ?? '';
        $datosActualizar['valida_hasta'] = $configActual['valida_hasta'] ?? null;
        $datosActualizar['desarrollado_por'] = $configActual['desarrollado_por'] ?? '';
        $datosActualizar['direccion'] = $configActual['direccion'] ?? '';
        $datosActualizar['correo_contacto'] = $configActual['correo_contacto'] ?? '';
        $datosActualizar['telefono'] = $configActual['telefono'] ?? '';
    }
    
    // Actualizar en la base de datos
    $resultado = $controlador->actualizarDatos($datosActualizar);
    
    if ($resultado) {
        $respuesta = [
            "success" => true,
            "message" => "Configuración del logo actualizada correctamente"
        ];
        
        // Incluir la nueva ruta del logo en la respuesta si se cambió
        if (isset($datosActualizar['ruta_logo'])) {
            $respuesta["ruta_logo"] = $datosActualizar['ruta_logo'];
        }
        
        echo json_encode($respuesta);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "No se pudo actualizar la configuración del logo"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>