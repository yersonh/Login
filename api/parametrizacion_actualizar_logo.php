<?php
header("Content-Type: application/json; charset=utf-8");
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Acceso no autorizado. Se requiere iniciar sesión como administrador."]);
    exit;
}

try {
    require_once __DIR__ . '/../controllers/ConfiguracionControlador.php';
    $controlador = new ConfiguracionControlador();
    
    $configActual = $controlador->obtenerDatos();
    
    if (empty($_POST['entidad']) || empty($_POST['enlace_web'])) {
        throw new Exception("Datos POST incompletos (entidad y enlace web son requeridos).");
    }

    // Logo predeterminado
    $datosActualizar = [
        'entidad'    => trim($_POST['entidad']),
        'enlace_web' => trim($_POST['enlace_web']),
        'ruta_logo'  => $configActual['ruta_logo'] ?? '/imagenes/gobernacion.png'
    ];

    $logoAntiguoPath = $configActual['ruta_logo'] ?? '';
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['logo'];
        
        $extensionesPermitidas = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $extensionesPermitidas)) {
            throw new Exception("Formato de archivo no permitido. Use PNG, JPG, SVG o GIF.");
        }
        
        if ($archivo['size'] > 2 * 1024 * 1024) { 
            throw new Exception("El archivo es demasiado grande. Máximo 2MB permitido.");
        }
        
        // ✅ SOLUCIÓN SIMPLE: Guardar en /imagenes/ con nombre fijo
        $directorioImagenes = __DIR__ . '/../../imagenes/';
        
        // Nombre fijo para el logo (siempre será el mismo)
        $nombreArchivo = 'logo_entidad.' . $extension;
        $rutaDestinoServidor = $directorioImagenes . $nombreArchivo;
        
        // Ruta para la BD
        $rutaDestinoDB = '/imagenes/' . $nombreArchivo;
        
        // DEBUG
        error_log("Subiendo logo a: " . $rutaDestinoServidor);
        error_log("Ruta en BD será: " . $rutaDestinoDB);
        
        if (move_uploaded_file($archivo['tmp_name'], $rutaDestinoServidor)) {
            $datosActualizar['ruta_logo'] = $rutaDestinoDB;
            
            // Eliminar logo antiguo solo si no es el predeterminado
            if (!empty($logoAntiguoPath) && 
                $logoAntiguoPath !== '/imagenes/gobernacion.png' &&
                $logoAntiguoPath !== '/imagenes/logo_entidad.' . $extension) {
                
                $rutaCompletaAntigua = __DIR__ . '/../..' . $logoAntiguoPath;
                if (file_exists($rutaCompletaAntigua)) {
                    @unlink($rutaCompletaAntigua);
                    error_log("Logo antiguo eliminado: " . $rutaCompletaAntigua);
                }
            }
            
            error_log("✅ Logo subido exitosamente");
        } else {
            throw new Exception("Error al subir el archivo. Verifique permisos.");
        }
    }
    
    $resultado = $controlador->actualizarLogo(
        $datosActualizar['ruta_logo'],
        $datosActualizar['entidad'],
        $datosActualizar['enlace_web']
    );
    
    if ($resultado) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Configuración y logo actualizados correctamente",
            "new_logo_url" => $datosActualizar['ruta_logo']
        ]);
    } else {
        http_response_code(500); 
        echo json_encode([
            "success" => false,
            "error" => "La actualización en la base de datos falló. Contacte a soporte."
        ]);
    }
    
} catch (Exception $e) {
    error_log("❌ Error en actualizar_logo.php: " . $e->getMessage());
    http_response_code(400); 
    echo json_encode([
        "success" => false,
        "error" => "Error al procesar: " . $e->getMessage()
    ]);
}
?>