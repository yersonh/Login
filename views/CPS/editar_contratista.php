<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ContratistaModel.php';
require_once __DIR__ . '/../../models/AreaModel.php';
require_once __DIR__ . '/../../models/MunicipioModel.php';
require_once __DIR__ . '/../../models/TipoVinculacionModel.php';

// Función para formatear bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    if ($bytes <= 0) {
        return '0 B';
    }
    
    $pow = floor(log($bytes, 1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    $redireccion = match($_SESSION['tipo_usuario'] ?? '') {
        'usuario' => '../menu.php',
        default => '../../index.php'
    };
    header("Location: $redireccion");
    exit();
}

// Obtener ID del contratista
if (!isset($_GET['id_detalle']) || empty($_GET['id_detalle'])) {
    $_SESSION['error'] = "ID de contratista no especificado";
    header("Location: visor_registrados.php");
    exit();
}

$id_detalle = (int)$_GET['id_detalle'];

// Inicializar variables
$contratista = null;
$areas = [];
$municipios = [];
$tiposVinculacion = [];
$error = '';
$success = '';

// Configuración de archivos
$max_file_size = 5 * 1024 * 1024; // 5MB
$allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$allowed_doc_types = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

try {
    $database = new Database();
    $db = $database->conectar();
    
    // Obtener modelos
    $contratistaModel = new ContratistaModel($db);
    $areaModel = new AreaModel($db);
    $municipioModel = new MunicipioModel($db);
    $tipoVinculacionModel = new TipoVinculacionModel($db);
    
    // Obtener datos del contratista
    $contratista = $contratistaModel->obtenerContratistaPorId($id_detalle);
    
    if (!$contratista) {
        $_SESSION['error'] = "Contratista no encontrado";
        header("Location: visor_registrados.php");
        exit();
    }
    
    // Obtener listas para formulario
    $areas = $areaModel->obtenerAreasActivas();
    $municipios = $municipioModel->obtenerMunicipiosActivos();
    $tiposVinculacion = $tipoVinculacionModel->obtenerTiposActivos();
    
    // Procesar POST si se envió el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validar y procesar datos del formulario
        $datosActualizados = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'cedula' => trim($_POST['cedula'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'correo_personal' => trim($_POST['correo_personal'] ?? ''),
            'profesion' => trim($_POST['profesion'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'id_area' => (int)($_POST['id_area'] ?? 0),
            'id_tipo_vinculacion' => (int)($_POST['id_tipo_vinculacion'] ?? 0),
            'id_municipio_principal' => (int)($_POST['id_municipio_principal'] ?? 0),
            'id_municipio_secundario' => !empty($_POST['id_municipio_secundario']) ? (int)$_POST['id_municipio_secundario'] : null,
            'id_municipio_terciario' => !empty($_POST['id_municipio_terciario']) ? (int)$_POST['id_municipio_terciario'] : null,
            'numero_contrato' => trim($_POST['numero_contrato'] ?? ''),
            'fecha_contrato' => trim($_POST['fecha_contrato'] ?? ''),
            'fecha_inicio' => trim($_POST['fecha_inicio'] ?? ''),
            'fecha_final' => trim($_POST['fecha_final'] ?? ''),
            'duracion_contrato' => trim($_POST['duracion_contrato'] ?? ''),
            'numero_registro_presupuestal' => trim($_POST['numero_registro_presupuestal'] ?? ''),
            'fecha_rp' => trim($_POST['fecha_rp'] ?? ''),
            'direccion_municipio_principal' => trim($_POST['direccion_municipio_principal'] ?? ''),
            'direccion_municipio_secundario' => trim($_POST['direccion_municipio_secundario'] ?? ''),
            'direccion_municipio_terciario' => trim($_POST['direccion_municipio_terciario'] ?? ''),
        ];
        
        // Validaciones básicas
        if (empty($datosActualizados['nombres']) || empty($datosActualizados['apellidos'])) {
            $error = "El nombre y apellidos son obligatorios";
        } elseif (empty($datosActualizados['cedula'])) {
            $error = "La cédula es obligatoria";
        } elseif (empty($datosActualizados['numero_contrato'])) {
            $error = "El número de contrato es obligatorio";
        } else {
            // Preparar archivos para actualización
            $archivos = [];
            
            // Manejar foto de perfil
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $foto = $_FILES['foto_perfil'];
                
                // Validar tipo de imagen
                if (!in_array($foto['type'], $allowed_image_types)) {
                    $error = "Tipo de archivo no permitido para foto. Solo se permiten JPG, PNG y GIF.";
                } elseif ($foto['size'] > $max_file_size) {
                    $error = "La foto es demasiado grande. Tamaño máximo: 5MB";
                } else {
                    $archivos['foto_perfil'] = $foto;
                }
            }
            
            // Manejar documentos si no hay error
            if (empty($error)) {
                // CV
                if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                    $cv = $_FILES['cv'];
                    if (!in_array($cv['type'], $allowed_doc_types) && !in_array($cv['type'], $allowed_image_types)) {
                        $error = "Tipo de archivo no permitido para CV. Solo PDF, Word, Excel e imágenes.";
                    } elseif ($cv['size'] > $max_file_size) {
                        $error = "El CV es demasiado grande. Tamaño máximo: 5MB";
                    } else {
                        $datosActualizados['cv_nombre_original'] = $cv['name'];
                        $archivos['adjuntar_cv'] = $cv;
                    }
                }
                
                // Contrato
                if (isset($_FILES['contrato']) && $_FILES['contrato']['error'] === UPLOAD_ERR_OK) {
                    $contrato = $_FILES['contrato'];
                    if (!in_array($contrato['type'], $allowed_doc_types) && !in_array($contrato['type'], $allowed_image_types)) {
                        $error = "Tipo de archivo no permitido para contrato. Solo PDF, Word, Excel e imágenes.";
                    } elseif ($contrato['size'] > $max_file_size) {
                        $error = "El contrato es demasiado grande. Tamaño máximo: 5MB";
                    } else {
                        $datosActualizados['contrato_nombre_original'] = $contrato['name'];
                        $archivos['adjuntar_contrato'] = $contrato;
                    }
                }
                
                // Acta de Inicio
                if (isset($_FILES['acta_inicio']) && $_FILES['acta_inicio']['error'] === UPLOAD_ERR_OK) {
                    $acta = $_FILES['acta_inicio'];
                    if (!in_array($acta['type'], $allowed_doc_types) && !in_array($acta['type'], $allowed_image_types)) {
                        $error = "Tipo de archivo no permitido para acta de inicio. Solo PDF, Word, Excel e imágenes.";
                    } elseif ($acta['size'] > $max_file_size) {
                        $error = "El acta de inicio es demasiado grande. Tamaño máximo: 5MB";
                    } else {
                        $datosActualizados['acta_inicio_nombre_original'] = $acta['name'];
                        $archivos['adjuntar_acta_inicio'] = $acta;
                    }
                }
                
                // Registro Presupuestal (RP)
                if (isset($_FILES['rp']) && $_FILES['rp']['error'] === UPLOAD_ERR_OK) {
                    $rp = $_FILES['rp'];
                    if (!in_array($rp['type'], $allowed_doc_types) && !in_array($rp['type'], $allowed_image_types)) {
                        $error = "Tipo de archivo no permitido para RP. Solo PDF, Word, Excel e imágenes.";
                    } elseif ($rp['size'] > $max_file_size) {
                        $error = "El RP es demasiado grande. Tamaño máximo: 5MB";
                    } else {
                        $datosActualizados['rp_nombre_original'] = $rp['name'];
                        $archivos['adjuntar_rp'] = $rp;
                    }
                }
            }
            
            // Si no hay errores, actualizar en la base de datos
            if (empty($error)) {
                // Mantener nombres originales si no se subieron nuevos archivos
                if (empty($datosActualizados['cv_nombre_original'])) {
                    $datosActualizados['cv_nombre_original'] = $contratista['cv_nombre_original'] ?? '';
                }
                if (empty($datosActualizados['contrato_nombre_original'])) {
                    $datosActualizados['contrato_nombre_original'] = $contratista['contrato_nombre_original'] ?? '';
                }
                if (empty($datosActualizados['acta_inicio_nombre_original'])) {
                    $datosActualizados['acta_inicio_nombre_original'] = $contratista['acta_inicio_nombre_original'] ?? '';
                }
                if (empty($datosActualizados['rp_nombre_original'])) {
                    $datosActualizados['rp_nombre_original'] = $contratista['rp_nombre_original'] ?? '';
                }
                
                // Actualizar en la base de datos
                $resultado = $contratistaModel->actualizarContratista($id_detalle, $datosActualizados, $archivos);
                
                if ($resultado['success']) {
                    header("Location: ver_detalle.php?id_detalle=$id_detalle");
                    exit();
                } else {
                    $error = $resultado['error'] ?? "Error al actualizar el contratista";
                }
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error en editar_contratista: " . $e->getMessage());
    $error = "Error al cargar los datos del contratista: " . $e->getMessage();
}

$nombreUsuario = htmlspecialchars($_SESSION['nombres'] ?? '');
$apellidoUsuario = htmlspecialchars($_SESSION['apellidos'] ?? '');
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
$nombreCompleto = empty($nombreCompleto) ? 'Usuario del Sistema' : $nombreCompleto;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contratista - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/visor_registrados.css">
    <link rel="stylesheet" href="../styles/editar_contratista.css">
    <style>
        /* Estilos para el modal de confirmación */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-confirmacion {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            background: #4a6cf7;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-mensaje {
            background: #f8f9fa;
            border-left: 4px solid #4a6cf7;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 0 4px 4px 0;
        }
        
        .modal-mensaje p {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .modal-mensaje i {
            color: #4a6cf7;
            font-size: 1.2rem;
        }
        
        .cambios-container {
            margin-bottom: 25px;
        }
        
        .cambios-container h4 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cambios-container h4 i {
            color: #4a6cf7;
        }
        
        .lista-cambios {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .cambio-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        
        .cambio-item:hover {
            background: #f8f9fa;
        }
        
        .cambio-item:last-child {
            border-bottom: none;
        }
        
        .campo-nombre {
            font-weight: 600;
            color: #333;
            min-width: 150px;
        }
        
        .valores-cambio {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            margin: 0 15px;
        }
        
        .valor-anterior {
            color: #dc3545;
            font-size: 0.9rem;
            text-decoration: line-through;
            padding: 2px 8px;
            background: #ffeaea;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .valor-nuevo {
            color: #28a745;
            font-size: 0.9rem;
            padding: 2px 8px;
            background: #e8f5e9;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .valor-anterior::before {
            content: "Anterior:";
            font-size: 0.8rem;
            color: #999;
        }
        
        .valor-nuevo::before {
            content: "Nuevo:";
            font-size: 0.8rem;
            color: #999;
        }
        
        .sin-cambios {
            text-align: center;
            color: #6c757d;
            padding: 20px;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .modal-footer {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        .btn-modal {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .btn-modal-cancelar {
            background: #6c757d;
            color: white;
        }
        
        .btn-modal-cancelar:hover {
            background: #5a6268;
        }
        
        .btn-modal-confirmar {
            background: #28a745;
            color: white;
        }
        
        .btn-modal-confirmar:hover {
            background: #218838;
        }
        
        .btn-modal-confirmar:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4a6cf7;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Estilos para archivos */
        .archivo-cambio {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .archivo-info {
            font-size: 0.85rem;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .archivo-info i {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    
    <div class="app-container">
        <header class="app-header">
            <div class="header-content">
                <div class="department-info">
                    <h1>GOBERNACIÓN DEL META</h1>
                    <h2>Secretaría de Minas y Energía</h2>
                </div>
                <div class="user-profile">
                    <div class="welcome-user">
                        <i class="fas fa-user-circle"></i>
                        <span>Bienvenido(a) </span>
                        <strong style="font-size: 25px; font-weight: bold;">
                            <?php echo htmlspecialchars($nombreCompleto); ?>
                        </strong>
                    </div>
                    <div class="user-role">Asistente</div>
                </div>
            </div>
        </header>
        
        <main class="app-main">
            <div class="edit-container">
                <!-- Encabezado -->
                <div class="edit-header">
                    <h1>
                        <i class="fas fa-user-edit"></i>
                        Editar Contratista
                    </h1>
                    <p>
                        <i class="fas fa-info-circle"></i>
                        Modifica la información del contratista <?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos'] ?? ''); ?>
                    </p>
                </div>
                
                <!-- Mensajes de error/success -->
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>
                
                <!-- Formulario -->
                <form method="POST" action="" class="form-container" id="formEditarContratista" enctype="multipart/form-data">
                    
                    <!-- Foto de Perfil y Datos Personales - Con datos en 2 columnas -->
                    <div class="form-section">
                        <h3><i class="fas fa-user-circle"></i> Información Personal</h3>
                        
                        <div class="form-grid personal-info-grid">
                            <!-- Columna Izquierda: Datos Personales en 2 columnas -->
                            <div class="personal-data-two-columns">
                                <!-- Fila 1: Nombres y Apellidos -->
                                <div class="two-column-row">
                                    <div class="form-group two-column-item">
                                        <label for="nombres">
                                            <i class="fas fa-user"></i> Nombres <span class="required">*</span>
                                        </label>
                                        <input type="text" 
                                               id="nombres" 
                                               name="nombres" 
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($contratista['nombres'] ?? ''); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="form-group two-column-item">
                                        <label for="apellidos">
                                            <i class="fas fa-user"></i> Apellidos <span class="required">*</span>
                                        </label>
                                        <input type="text" 
                                               id="apellidos" 
                                               name="apellidos" 
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($contratista['apellidos'] ?? ''); ?>"
                                               required>
                                    </div>
                                </div>
                                
                                <!-- Fila 2: Cédula y Profesión -->
                                <div class="two-column-row">
                                    <div class="form-group two-column-item">
                                        <label for="cedula">
                                            <i class="fas fa-id-card"></i> Cédula <span class="required">*</span>
                                        </label>
                                        <input type="text" 
                                               id="cedula" 
                                               name="cedula" 
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($contratista['cedula'] ?? ''); ?>"
                                               required>
                                    </div>
                                    
                                    <div class="form-group two-column-item">
                                        <label for="profesion">
                                            <i class="fas fa-graduation-cap"></i> Profesión
                                        </label>
                                        <input type="text" 
                                               id="profesion" 
                                               name="profesion" 
                                               class="form-control"
                                               placeholder="Ej: Ingeniero Civil, Arquitecto, Abogado"
                                               value="<?php echo htmlspecialchars($contratista['profesion'] ?? ''); ?>">
                                        <span class="form-text">Profesión u oficio del contratista</span>
                                    </div>
                                </div>
                                
                                <!-- Fila 3: Dirección y Teléfono -->
                                <div class="two-column-row">
                                    <div class="form-group two-column-item">
                                        <label for="direccion">
                                            <i class="fas fa-home"></i> Dirección
                                        </label>
                                        <input type="text" 
                                               id="direccion" 
                                               name="direccion" 
                                               class="form-control"
                                               placeholder="Ej: Calle 123 #45-67"
                                               value="<?php echo htmlspecialchars($contratista['direccion'] ?? ''); ?>">
                                        <span class="form-text">Dirección de residencia del contratista</span>
                                    </div>
                                    
                                    <div class="form-group two-column-item">
                                        <label for="telefono">
                                            <i class="fas fa-phone"></i> Teléfono
                                        </label>
                                        <input type="tel" 
                                               id="telefono" 
                                               name="telefono" 
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($contratista['telefono'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <!-- Fila 4: Correo solo en columna izquierda -->
                                <div class="two-column-row">
                                    <div class="form-group two-column-item" style="flex: 0 0 50%;">
                                        <label for="correo_personal">
                                            <i class="fas fa-envelope"></i> Correo Personal
                                        </label>
                                        <input type="email" 
                                               id="correo_personal" 
                                               name="correo_personal" 
                                               class="form-control"
                                               value="<?php echo htmlspecialchars($contratista['correo_personal'] ?? ''); ?>">
                                        <span class="form-text">Correo para contactar al contratista</span>
                                    </div>
                                    <!-- La columna derecha queda vacía automáticamente -->
                                </div>
                            </div>
                            
                            <!-- Columna Derecha: Foto de Perfil -->
                            <div class="photo-column">
                                <div class="photo-container">
                                    <div class="photo-header">
                                        <h4><i class="fas fa-camera"></i> Foto de Perfil</h4>
                                    </div>
                                    
                                    <div class="photo-preview-area">
                                        <!-- Mostrar foto actual si existe -->
                                        <?php if (!empty($contratista['foto_contenido'])): ?>
                                        <div class="foto-preview">
                                            <img src="data:<?php echo htmlspecialchars($contratista['foto_tipo_mime'] ?? 'image/jpeg'); ?>;base64,<?php echo base64_encode($contratista['foto_contenido']); ?>" 
                                                 alt="Foto actual del contratista"
                                                 id="fotoPreview">
                                        </div>
                                        <div class="current-file">
                                            <div class="file-info">
                                                <i class="fas fa-image"></i>
                                                <div class="file-info-content">
                                                    <div class="file-name">Foto actual</div>
                                                    <div class="file-size">Subida: <?php echo date('d/m/Y', strtotime($contratista['foto_fecha_subida'] ?? '')); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="foto-preview">
                                            <div class="foto-placeholder" id="fotoPreviewPlaceholder">
                                                <i class="fas fa-user"></i>
                                                <div>Sin foto</div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="photo-upload-area">
                                        <div class="form-group">
                                            <label for="foto_perfil" class="photo-upload-label">
                                                <i class="fas fa-upload"></i> Cambiar Foto
                                            </label>
                                            <input type="file" 
                                                   id="foto_perfil" 
                                                   name="foto_perfil" 
                                                   class="form-control-file photo-upload-input"
                                                   accept="image/jpeg,image/jpg,image/png,image/gif"
                                                   onchange="previewFoto(this)">
                                            <div class="photo-upload-help">
                                                <i class="fas fa-info-circle"></i>
                                                Formatos: JPG, PNG, GIF (Máx. 5MB)
                                            </div>
                                            <div class="photo-upload-note">
                                                Dejar en blanco para mantener la foto actual
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Contrato -->
                    <div class="form-section">
                        <h3><i class="fas fa-file-contract"></i> Información del Contrato</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="numero_contrato">
                                    <i class="fas fa-file-invoice"></i> Número de Contrato <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       id="numero_contrato" 
                                       name="numero_contrato" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($contratista['numero_contrato'] ?? ''); ?>"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha_contrato">
                                    <i class="fas fa-calendar-check"></i> Fecha del Contrato
                                </label>
                                <input type="date" 
                                       id="fecha_contrato" 
                                       name="fecha_contrato" 
                                       class="form-control"
                                       value="<?php echo !empty($contratista['fecha_contrato']) ? date('Y-m-d', strtotime($contratista['fecha_contrato'])) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha_inicio">
                                    <i class="fas fa-play-circle"></i> Fecha de Inicio
                                </label>
                                <input type="date" 
                                       id="fecha_inicio" 
                                       name="fecha_inicio" 
                                       class="form-control"
                                       value="<?php echo !empty($contratista['fecha_inicio']) ? date('Y-m-d', strtotime($contratista['fecha_inicio'])) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha_final">
                                    <i class="fas fa-flag-checkered"></i> Fecha Final
                                </label>
                                <input type="date" 
                                       id="fecha_final" 
                                       name="fecha_final" 
                                       class="form-control"
                                       value="<?php echo !empty($contratista['fecha_final']) ? date('Y-m-d', strtotime($contratista['fecha_final'])) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="duracion_contrato">
                                    <i class="fas fa-clock"></i> Duración
                                </label>
                                <input type="text" 
                                       id="duracion_contrato" 
                                       name="duracion_contrato" 
                                       class="form-control"
                                       placeholder="Ej: 6 meses, 1 año"
                                       value="<?php echo htmlspecialchars($contratista['duracion_contrato'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="numero_registro_presupuestal">
                                    <i class="fas fa-file-invoice-dollar"></i> Registro Presupuestal
                                </label>
                                <input type="text" 
                                       id="numero_registro_presupuestal" 
                                       name="numero_registro_presupuestal" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($contratista['numero_registro_presupuestal'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha_rp">
                                    <i class="fas fa-calendar-alt"></i> Fecha RP
                                </label>
                                <input type="date" 
                                       id="fecha_rp" 
                                       name="fecha_rp" 
                                       class="form-control"
                                       value="<?php echo !empty($contratista['fecha_rp']) ? date('Y-m-d', strtotime($contratista['fecha_rp'])) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información Laboral -->
                    <div class="form-section">
                        <h3><i class="fas fa-briefcase"></i> Información Laboral</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="id_area">
                                    <i class="fas fa-sitemap"></i> Área <span class="required">*</span>
                                </label>
                                <select id="id_area" name="id_area" class="form-control" required>
                                    <option value="">Seleccione un área</option>
                                    <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['id_area']; ?>"
                                        <?php echo ($area['id_area'] == ($contratista['id_area'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="id_tipo_vinculacion">
                                    <i class="fas fa-link"></i> Tipo de Vinculación <span class="required">*</span>
                                </label>
                                <select id="id_tipo_vinculacion" name="id_tipo_vinculacion" class="form-control" required>
                                    <option value="">Seleccione un tipo</option>
                                    <?php foreach ($tiposVinculacion as $tipo): ?>
                                    <option value="<?php echo $tipo['id_tipo']; ?>"
                                        <?php echo ($tipo['id_tipo'] == ($contratista['id_tipo_vinculacion'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ubicación -->
                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Ubicación</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="id_municipio_principal">
                                    <i class="fas fa-map-pin"></i> Municipio Principal <span class="required">*</span>
                                </label>
                                <select id="id_municipio_principal" name="id_municipio_principal" class="form-control ubicacion-select" required data-target="direccion_municipio_principal">
                                    <option value="">Seleccione municipio principal</option>
                                    <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?php echo $municipio['id_municipio']; ?>"
                                        <?php echo ($municipio['id_municipio'] == ($contratista['id_municipio_principal'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($municipio['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion_municipio_principal">
                                    <i class="fas fa-home"></i> Dirección Principal
                                </label>
                                <input type="text" 
                                       id="direccion_municipio_principal" 
                                       name="direccion_municipio_principal" 
                                       class="form-control direccion-campo"
                                       value="<?php echo htmlspecialchars($contratista['direccion_municipio_principal'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group municipio-secundario-container">
                                <label for="id_municipio_secundario">
                                    <i class="fas fa-map-marker"></i> Municipio Secundario
                                </label>
                                <select id="id_municipio_secundario" name="id_municipio_secundario" class="form-control ubicacion-select" data-target="direccion_municipio_secundario">
                                    <option value="">Seleccione municipio secundario</option>
                                    <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?php echo $municipio['id_municipio']; ?>"
                                        <?php echo ($municipio['id_municipio'] == ($contratista['id_municipio_secundario'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($municipio['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group direccion-secundaria-container" style="display: none;">
                                <label for="direccion_municipio_secundario">
                                    <i class="fas fa-home"></i> Dirección Secundaria
                                </label>
                                <input type="text" 
                                       id="direccion_municipio_secundario" 
                                       name="direccion_municipio_secundario" 
                                       class="form-control direccion-campo"
                                       value="<?php echo htmlspecialchars($contratista['direccion_municipio_secundario'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group municipio-terciario-container">
                                <label for="id_municipio_terciario">
                                    <i class="fas fa-map-marker"></i> Municipio Terciario
                                </label>
                                <select id="id_municipio_terciario" name="id_municipio_terciario" class="form-control ubicacion-select" data-target="direccion_municipio_terciario">
                                    <option value="">Seleccione municipio terciario</option>
                                    <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?php echo $municipio['id_municipio']; ?>"
                                        <?php echo ($municipio['id_municipio'] == ($contratista['id_municipio_terciario'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($municipio['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group direccion-terciaria-container" style="display: none;">
                                <label for="direccion_municipio_terciario">
                                    <i class="fas fa-home"></i> Dirección Terciaria
                                </label>
                                <input type="text" 
                                       id="direccion_municipio_terciario" 
                                       name="direccion_municipio_terciario" 
                                       class="form-control direccion-campo"
                                       value="<?php echo htmlspecialchars($contratista['direccion_municipio_terciario'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documentos Adjuntos (ESTILO IGUAL A VER DETALLE) -->
                    <div class="documentos-card">
                        <h3><i class="fas fa-paperclip"></i> Documentos Adjuntos</h3>
                        <p><i class="fas fa-info-circle"></i> Puedes actualizar los documentos. Deja en blanco para mantener el documento actual.</p>
                        
                        <div class="documentos-grid">
                            <!-- CV -->
                            <div class="documento-item">
                                <div class="documento-header">
                                    <div class="documento-title">
                                        <div class="documento-icon cv">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        Hoja de Vida (CV)
                                    </div>
                                </div>
                                
                                <div class="documento-meta">
                                    <?php if (!empty($contratista['cv_nombre_original'])): ?>
                                        <div><i class="fas fa-file"></i> <?php echo htmlspecialchars($contratista['cv_nombre_original']); ?></div>
                                        <div><i class="fas fa-weight"></i> <?php echo formatBytes($contratista['cv_tamano'] ?? 0); ?></div>
                                        <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['cv_tipo_mime'] ?? 'N/A'); ?></div>
                                    <?php else: ?>
                                        <div class="empty-doc">No se ha cargado hoja de vida</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="documento-upload">
                                    <input type="file" 
                                           id="cv" 
                                           name="cv" 
                                           class="form-control-file"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                    <div class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</div>
                                </div>
                            </div>
                            
                            <!-- CONTRATO -->
                            <div class="documento-item">
                                <div class="documento-header">
                                    <div class="documento-title">
                                        <div class="documento-icon contrato">
                                            <i class="fas fa-file-contract"></i>
                                        </div>
                                        Contrato
                                    </div>
                                </div>
                                
                                <div class="documento-meta">
                                    <?php if (!empty($contratista['contrato_nombre_original'])): ?>
                                        <div><i class="fas fa-file"></i> <?php echo htmlspecialchars($contratista['contrato_nombre_original']); ?></div>
                                        <div><i class="fas fa-weight"></i> <?php echo formatBytes($contratista['contrato_tamano'] ?? 0); ?></div>
                                        <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['contrato_tipo_mime'] ?? 'N/A'); ?></div>
                                    <?php else: ?>
                                        <div class="empty-doc">No se ha cargado contrato</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="documento-upload">
                                    <input type="file" 
                                           id="contrato" 
                                           name="contrato" 
                                           class="form-control-file"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                    <div class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</div>
                                </div>
                            </div>
                            
                            <!-- ACTA DE INICIO -->
                            <div class="documento-item">
                                <div class="documento-header">
                                    <div class="documento-title">
                                        <div class="documento-icon acta">
                                            <i class="fas fa-file-signature"></i>
                                        </div>
                                        Acta de Inicio
                                    </div>
                                </div>
                                
                                <div class="documento-meta">
                                    <?php if (!empty($contratista['acta_inicio_nombre_original'])): ?>
                                        <div><i class="fas fa-file"></i> <?php echo htmlspecialchars($contratista['acta_inicio_nombre_original']); ?></div>
                                        <div><i class="fas fa-weight"></i> <?php echo formatBytes($contratista['acta_inicio_tamano'] ?? 0); ?></div>
                                        <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['acta_inicio_tipo_mime'] ?? 'N/A'); ?></div>
                                    <?php else: ?>
                                        <div class="empty-doc">No se ha cargado acta de inicio</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="documento-upload">
                                    <input type="file" 
                                           id="acta_inicio" 
                                           name="acta_inicio" 
                                           class="form-control-file"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                    <div class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</div>
                                </div>
                            </div>
                            
                            <!-- REGISTRO PRESUPUESTAL -->
                            <div class="documento-item">
                                <div class="documento-header">
                                    <div class="documento-title">
                                        <div class="documento-icon rp">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                        Registro Presupuestal (RP)
                                    </div>
                                </div>
                                
                                <div class="documento-meta">
                                    <?php if (!empty($contratista['rp_nombre_original'])): ?>
                                        <div><i class="fas fa-file"></i> <?php echo htmlspecialchars($contratista['rp_nombre_original']); ?></div>
                                        <div><i class="fas fa-weight"></i> <?php echo formatBytes($contratista['rp_tamano'] ?? 0); ?></div>
                                        <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['rp_tipo_mime'] ?? 'N/A'); ?></div>
                                    <?php else: ?>
                                        <div class="empty-doc">No se ha cargado registro presupuestal</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="documento-upload">
                                    <input type="file" 
                                           id="rp" 
                                           name="rp" 
                                           class="form-control-file"
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                    <div class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="form-actions">
                        <a href="ver_detalle.php?id_detalle=<?php echo $id_detalle; ?>" class="btn btn-cancelar">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="button" class="btn btn-guardar" id="btnMostrarCambios">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </main>
        
        <button class="btn-volver" onclick="window.location.href='visor_registrados.php'">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </button>
        
        <footer class="app-footer">
            <div class="footer-center">
                <?php
                $logoUrl = ConfigHelper::obtenerLogoUrl();
                $entidad = htmlspecialchars(ConfigHelper::obtener('entidad', 'Gobernación del Meta'));
                $version = htmlspecialchars(ConfigHelper::obtenerVersionCompleta());
                $desarrollador = htmlspecialchars(ConfigHelper::obtener('desarrollado_por', 'SisgonTech'));
                $direccion = htmlspecialchars(ConfigHelper::obtener('direccion'));
                $correo = htmlspecialchars(ConfigHelper::obtener('correo_contacto'));
                $telefono = htmlspecialchars(ConfigHelper::obtener('telefono'));
                $anio = date('Y');
                ?>
                
                <div class="footer-logo-container">
                    <img src="<?php echo htmlspecialchars($logoUrl); ?>" 
                        alt="<?php echo $entidad; ?>" 
                        class="license-logo"
                        onerror="this.onerror=null; this.src='/imagenes/gobernacion.png'">
                </div>
                
                <p>
                    © <?php echo $anio; ?> <?php echo $entidad; ?> <?php echo $version; ?>® desarrollado por 
                    <strong><?php echo $desarrollador; ?></strong>
                </p>
                
                <p>
                    <?php echo $direccion; ?> - Asesores e-Governance Solutions para Entidades Públicas <?php echo $anio; ?>® 
                    By: Ing. Rubén Darío González García <?php echo $telefono; ?>. Contacto: <strong><?php echo $correo; ?></strong> - Reservados todos los derechos de autor.  
                </p>
            </div>
        </footer>
    </div>
    
    <!-- Modal de Confirmación de Cambios -->
    <div class="modal-overlay" id="modalConfirmacion">
        <div class="modal-confirmacion">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-check"></i> Confirmar Cambios</h3>
                <button class="modal-close" id="btnCerrarModal">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="modal-mensaje">
                    <p>
                        <strong><?php echo htmlspecialchars($nombreCompleto); ?></strong>, estás a punto de guardar los siguientes cambios en el contratista. Por favor, revisa cuidadosamente antes de confirmar.
                    </p>
                </div>
                
                <div class="cambios-container">
                    <h4><i class="fas fa-list-check"></i> Cambios Detectados</h4>
                    
                    <div id="listaCambios" class="lista-cambios">
                        <!-- Los cambios se insertarán aquí dinámicamente -->
                        <div class="sin-cambios">
                            <i class="fas fa-info-circle"></i>
                            No se detectaron cambios
                        </div>
                    </div>
                </div>
                
                <div class="loading" id="loadingGuardar">
                    <div class="loading-spinner"></div>
                    <p>Guardando cambios, por favor espera...</p>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-modal btn-modal-cancelar" id="btnCancelarCambios">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button class="btn-modal btn-modal-confirmar" id="btnConfirmarCambios" disabled>
                    <i class="fas fa-check"></i> Confirmar Cambios
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // Datos originales del contratista (para comparar)
    const datosOriginales = {
        nombres: "<?php echo htmlspecialchars($contratista['nombres'] ?? ''); ?>",
        apellidos: "<?php echo htmlspecialchars($contratista['apellidos'] ?? ''); ?>",
        cedula: "<?php echo htmlspecialchars($contratista['cedula'] ?? ''); ?>",
        profesion: "<?php echo htmlspecialchars($contratista['profesion'] ?? ''); ?>",
        direccion: "<?php echo htmlspecialchars($contratista['direccion'] ?? ''); ?>",
        telefono: "<?php echo htmlspecialchars($contratista['telefono'] ?? ''); ?>",
        correo_personal: "<?php echo htmlspecialchars($contratista['correo_personal'] ?? ''); ?>",
        numero_contrato: "<?php echo htmlspecialchars($contratista['numero_contrato'] ?? ''); ?>",
        fecha_contrato: "<?php echo !empty($contratista['fecha_contrato']) ? date('Y-m-d', strtotime($contratista['fecha_contrato'])) : ''; ?>",
        fecha_inicio: "<?php echo !empty($contratista['fecha_inicio']) ? date('Y-m-d', strtotime($contratista['fecha_inicio'])) : ''; ?>",
        fecha_final: "<?php echo !empty($contratista['fecha_final']) ? date('Y-m-d', strtotime($contratista['fecha_final'])) : ''; ?>",
        duracion_contrato: "<?php echo htmlspecialchars($contratista['duracion_contrato'] ?? ''); ?>",
        numero_registro_presupuestal: "<?php echo htmlspecialchars($contratista['numero_registro_presupuestal'] ?? ''); ?>",
        fecha_rp: "<?php echo !empty($contratista['fecha_rp']) ? date('Y-m-d', strtotime($contratista['fecha_rp'])) : ''; ?>",
        id_area: "<?php echo $contratista['id_area'] ?? 0; ?>",
        id_tipo_vinculacion: "<?php echo $contratista['id_tipo_vinculacion'] ?? 0; ?>",
        id_municipio_principal: "<?php echo $contratista['id_municipio_principal'] ?? 0; ?>",
        direccion_municipio_principal: "<?php echo htmlspecialchars($contratista['direccion_municipio_principal'] ?? ''); ?>",
        id_municipio_secundario: "<?php echo $contratista['id_municipio_secundario'] ?? ''; ?>",
        direccion_municipio_secundario: "<?php echo htmlspecialchars($contratista['direccion_municipio_secundario'] ?? ''); ?>",
        id_municipio_terciario: "<?php echo $contratista['id_municipio_terciario'] ?? ''; ?>",
        direccion_municipio_terciario: "<?php echo htmlspecialchars($contratista['direccion_municipio_terciario'] ?? ''); ?>"
    };
    
    // Nombres de campos para mostrar
    const nombresCampos = {
        nombres: "Nombres",
        apellidos: "Apellidos",
        cedula: "Cédula",
        profesion: "Profesión",
        direccion: "Dirección",
        telefono: "Teléfono",
        correo_personal: "Correo Personal",
        numero_contrato: "Número de Contrato",
        fecha_contrato: "Fecha del Contrato",
        fecha_inicio: "Fecha de Inicio",
        fecha_final: "Fecha Final",
        duracion_contrato: "Duración del Contrato",
        numero_registro_presupuestal: "Registro Presupuestal",
        fecha_rp: "Fecha RP",
        id_area: "Área",
        id_tipo_vinculacion: "Tipo de Vinculación",
        id_municipio_principal: "Municipio Principal",
        direccion_municipio_principal: "Dirección Principal",
        id_municipio_secundario: "Municipio Secundario",
        direccion_municipio_secundario: "Dirección Secundaria",
        id_municipio_terciario: "Municipio Terciario",
        direccion_municipio_terciario: "Dirección Terciaria"
    };
    
    // Mapeo de IDs a nombres (para selects)
    const areasMap = {
        <?php foreach ($areas as $area): ?>
        "<?php echo $area['id_area']; ?>": "<?php echo htmlspecialchars($area['nombre']); ?>",
        <?php endforeach; ?>
    };
    
    const tiposMap = {
        <?php foreach ($tiposVinculacion as $tipo): ?>
        "<?php echo $tipo['id_tipo']; ?>": "<?php echo htmlspecialchars($tipo['nombre']); ?>",
        <?php endforeach; ?>
    };
    
    const municipiosMap = {
        <?php foreach ($municipios as $municipio): ?>
        "<?php echo $municipio['id_municipio']; ?>": "<?php echo htmlspecialchars($municipio['nombre']); ?>",
        <?php endforeach; ?>
    };
    
    // Función para previsualizar la foto seleccionada
    function previewFoto(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                var existingImg = document.getElementById('fotoPreview');
                var placeholder = document.getElementById('fotoPreviewPlaceholder');
                
                if (existingImg) {
                    existingImg.src = e.target.result;
                } else if (placeholder) {
                    placeholder.parentElement.innerHTML = '<img src="' + e.target.result + '" id="fotoPreview" alt="Previsualización de foto" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">';
                }
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Función para comparar valores y detectar cambios
    function detectarCambios() {
        const cambios = [];
        
        // Función para comparar valores
        function compararValor(campo, valorOriginal, valorNuevo, esSelect = false) {
            if (esSelect) {
                valorOriginal = valorOriginal.toString();
                valorNuevo = valorNuevo.toString();
            }
            
            return valorOriginal !== valorNuevo;
        }
        
        // Campos de texto e inputs
        const campos = [
            'nombres', 'apellidos', 'cedula', 'profesion', 'direccion', 'telefono', 'correo_personal',
            'numero_contrato', 'duracion_contrato', 'numero_registro_presupuestal',
            'direccion_municipio_principal', 'direccion_municipio_secundario', 'direccion_municipio_terciario'
        ];
        
        campos.forEach(campo => {
            const input = document.getElementById(campo);
            if (input) {
                const valorOriginal = datosOriginales[campo] || '';
                const valorNuevo = input.value.trim();
                
                if (compararValor(campo, valorOriginal, valorNuevo)) {
                    cambios.push({
                        campo: campo,
                        nombre: nombresCampos[campo],
                        anterior: valorOriginal || '(vacío)',
                        nuevo: valorNuevo || '(vacío)',
                        tipo: 'texto'
                    });
                }
            }
        });
        
        // Campos de fecha
        const camposFecha = ['fecha_contrato', 'fecha_inicio', 'fecha_final', 'fecha_rp'];
        camposFecha.forEach(campo => {
            const input = document.getElementById(campo);
            if (input) {
                const valorOriginal = datosOriginales[campo] || '';
                const valorNuevo = input.value;
                
                if (compararValor(campo, valorOriginal, valorNuevo)) {
                    cambios.push({
                        campo: campo,
                        nombre: nombresCampos[campo],
                        anterior: valorOriginal ? formatFecha(valorOriginal) : '(sin fecha)',
                        nuevo: valorNuevo ? formatFecha(valorNuevo) : '(sin fecha)',
                        tipo: 'fecha'
                    });
                }
            }
        });
        
        // Campos select
        const selects = [
            {campo: 'id_area', map: areasMap},
            {campo: 'id_tipo_vinculacion', map: tiposMap},
            {campo: 'id_municipio_principal', map: municipiosMap},
            {campo: 'id_municipio_secundario', map: municipiosMap},
            {campo: 'id_municipio_terciario', map: municipiosMap}
        ];
        
        selects.forEach(({campo, map}) => {
            const select = document.getElementById(campo);
            if (select) {
                const valorOriginal = datosOriginales[campo]?.toString() || '';
                const valorNuevo = select.value;
                
                if (compararValor(campo, valorOriginal, valorNuevo, true)) {
                    cambios.push({
                        campo: campo,
                        nombre: nombresCampos[campo],
                        anterior: valorOriginal ? (map[valorOriginal] || valorOriginal) : '(sin selección)',
                        nuevo: valorNuevo ? (map[valorNuevo] || valorNuevo) : '(sin selección)',
                        tipo: 'select'
                    });
                }
            }
        });
        
        // Campos de archivos
        const archivos = ['foto_perfil', 'cv', 'contrato', 'acta_inicio', 'rp'];
        archivos.forEach(archivo => {
            const input = document.getElementById(archivo);
            if (input && input.files.length > 0) {
                const nombreArchivo = input.files[0].name;
                const tamanoArchivo = (input.files[0].size / 1024 / 1024).toFixed(2); // MB
                
                cambios.push({
                    campo: archivo,
                    nombre: getNombreArchivo(archivo),
                    anterior: 'Archivo actual se mantendrá',
                    nuevo: `Nuevo archivo: ${nombreArchivo} (${tamanoArchivo} MB)`,
                    tipo: 'archivo'
                });
            }
        });
        
        return cambios;
    }
    
    // Función para formatear fecha
    function formatFecha(fecha) {
        if (!fecha) return '';
        const date = new Date(fecha);
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
    
    // Función para obtener nombre de archivo
    function getNombreArchivo(campo) {
        const nombres = {
            'foto_perfil': 'Foto de Perfil',
            'cv': 'Hoja de Vida (CV)',
            'contrato': 'Contrato',
            'acta_inicio': 'Acta de Inicio',
            'rp': 'Registro Presupuestal (RP)'
        };
        return nombres[campo] || campo;
    }
    
    // Función para mostrar cambios en el modal
    function mostrarCambiosEnModal(cambios) {
        const listaCambios = document.getElementById('listaCambios');
        const btnConfirmar = document.getElementById('btnConfirmarCambios');
        
        if (cambios.length === 0) {
            listaCambios.innerHTML = `
                <div class="sin-cambios">
                    <i class="fas fa-info-circle"></i>
                    No se detectaron cambios. Todos los valores se mantendrán igual.
                </div>
            `;
            btnConfirmar.disabled = true;
        } else {
            let html = '';
            cambios.forEach(cambio => {
                html += `
                    <div class="cambio-item">
                        <div class="campo-nombre">${cambio.nombre}</div>
                        <div class="valores-cambio">
                            <div class="valor-anterior">
                                <i class="fas fa-arrow-left"></i>
                                ${cambio.anterior}
                            </div>
                            <div class="valor-nuevo">
                                <i class="fas fa-arrow-right"></i>
                                ${cambio.nuevo}
                            </div>
                        </div>
                    </div>
                `;
            });
            listaCambios.innerHTML = html;
            btnConfirmar.disabled = false;
        }
    }
    
    // Función para validar formulario antes de mostrar cambios
    function validarFormulario() {
        const form = document.getElementById('formEditarContratista');
        const inputsRequeridos = form.querySelectorAll('[required]');
        let valido = true;
        
        inputsRequeridos.forEach(input => {
            if (!input.value.trim()) {
                valido = false;
                input.style.borderColor = '#dc3545';
            } else {
                input.style.borderColor = '';
            }
        });
        
        return valido;
    }
    
    // Manejar clic en "Guardar Cambios"
    document.getElementById('btnMostrarCambios').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!validarFormulario()) {
            alert('Por favor, completa todos los campos obligatorios marcados con *.');
            return;
        }
        
        // Detectar cambios
        const cambios = detectarCambios();
        
        // Mostrar modal con cambios
        mostrarCambiosEnModal(cambios);
        
        // Mostrar modal
        document.getElementById('modalConfirmacion').style.display = 'flex';
    });
    
    // Manejar cierre del modal
    document.getElementById('btnCerrarModal').addEventListener('click', function() {
        document.getElementById('modalConfirmacion').style.display = 'none';
    });
    
    document.getElementById('btnCancelarCambios').addEventListener('click', function() {
        document.getElementById('modalConfirmacion').style.display = 'none';
    });
    
    // Cerrar modal al hacer clic fuera de él
    document.getElementById('modalConfirmacion').addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
    
    // Manejar confirmación de cambios
    document.getElementById('btnConfirmarCambios').addEventListener('click', function() {
        const btnConfirmar = this;
        const loading = document.getElementById('loadingGuardar');
        const modalBody = document.querySelector('.modal-body');
        
        // Mostrar loading
        btnConfirmar.disabled = true;
        loading.style.display = 'block';
        modalBody.style.opacity = '0.5';
        
        // Enviar formulario después de 1 segundo (para mostrar el loading)
        setTimeout(() => {
            document.getElementById('formEditarContratista').submit();
        }, 1000);
    });
    
    // Inicializar manejo de municipios
    document.addEventListener('DOMContentLoaded', function() {
        const municipioSelects = document.querySelectorAll('.ubicacion-select');
        
        municipioSelects.forEach(select => {
            select.addEventListener('change', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                const containerId = targetId + 'Container';
                const container = document.querySelector('.' + containerId);
                
                if (this.value && this.value !== '') {
                    if (container) container.style.display = 'block';
                    if (targetInput) targetInput.required = true;
                } else {
                    if (container) container.style.display = 'none';
                    if (targetInput) {
                        targetInput.required = false;
                        targetInput.value = '';
                    }
                }
            });
            
            // Disparar evento change al cargar la página
            select.dispatchEvent(new Event('change'));
        });
    });
    </script>
    
    <script src="../../javascript/editar_contratista.js"></script>
    
</body>
</html>