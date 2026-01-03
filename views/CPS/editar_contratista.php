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
            'direccion' => trim($_POST['direccion_municipio_principal'] ?? '') // Para compatibilidad
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
                    
                    <!-- Foto de Perfil -->
                    <div class="form-section">
                        <h3><i class="fas fa-camera"></i> Foto de Perfil</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="foto_perfil">
                                    <i class="fas fa-image"></i> Cambiar Foto
                                </label>
                                
                                <!-- Mostrar foto actual si existe -->
                                <?php if (!empty($contratista['foto_contenido'])): ?>
                                <div class="foto-preview">
                                    <img src="data:<?php echo htmlspecialchars($contratista['foto_tipo_mime'] ?? 'image/jpeg'); ?>;base64,<?php echo base64_encode($contratista['foto_contenido']); ?>" 
                                         alt="Foto actual del contratista">
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
                                    <div class="foto-placeholder">
                                        <i class="fas fa-user"></i>
                                        <div>Sin foto</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <input type="file" 
                                       id="foto_perfil" 
                                       name="foto_perfil" 
                                       class="form-control-file"
                                       accept="image/jpeg,image/jpg,image/png,image/gif">
                                <span class="form-help">Formatos permitidos: JPG, PNG, GIF (Máx. 5MB)</span>
                                <span class="form-text">Dejar en blanco para mantener la foto actual</span>
                            </div>
                            
                            <div class="form-group">
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
                    </div>
                    
                    <!-- Datos Personales -->
                    <div class="form-section">
                        <h3><i class="fas fa-id-card"></i> Datos Personales</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
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
                            
                            <div class="form-group">
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
                            
                            <div class="form-group">
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
                            
                            <div class="form-group">
                                <label for="telefono">
                                    <i class="fas fa-phone"></i> Teléfono
                                </label>
                                <input type="tel" 
                                       id="telefono" 
                                       name="telefono" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($contratista['telefono'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
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
                                <select id="id_municipio_principal" name="id_municipio_principal" class="form-control" required>
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
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($contratista['direccion_municipio_principal'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="id_municipio_secundario">
                                    <i class="fas fa-map-marker"></i> Municipio Secundario
                                </label>
                                <select id="id_municipio_secundario" name="id_municipio_secundario" class="form-control">
                                    <option value="">Seleccione municipio secundario</option>
                                    <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?php echo $municipio['id_municipio']; ?>"
                                        <?php echo ($municipio['id_municipio'] == ($contratista['id_municipio_secundario'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($municipio['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion_municipio_secundario">
                                    <i class="fas fa-home"></i> Dirección Secundaria
                                </label>
                                <input type="text" 
                                       id="direccion_municipio_secundario" 
                                       name="direccion_municipio_secundario" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($contratista['direccion_municipio_secundario'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="id_municipio_terciario">
                                    <i class="fas fa-map-marker"></i> Municipio Terciario
                                </label>
                                <select id="id_municipio_terciario" name="id_municipio_terciario" class="form-control">
                                    <option value="">Seleccione municipio terciario</option>
                                    <?php foreach ($municipios as $municipio): ?>
                                    <option value="<?php echo $municipio['id_municipio']; ?>"
                                        <?php echo ($municipio['id_municipio'] == ($contratista['id_municipio_terciario'] ?? 0)) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($municipio['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion_municipio_terciario">
                                    <i class="fas fa-home"></i> Dirección Terciaria
                                </label>
                                <input type="text" 
                                       id="direccion_municipio_terciario" 
                                       name="direccion_municipio_terciario" 
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($contratista['direccion_municipio_terciario'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documentos Adjuntos -->
                    <div class="form-section">
                        <h3><i class="fas fa-paperclip"></i> Documentos Adjuntos</h3>
                        <p><i class="fas fa-info-circle"></i> Puedes actualizar los documentos. Deja en blanco para mantener el documento actual.</p>
                        
                        <div class="form-grid">
                            <!-- CV -->
                            <div class="form-group">
                                <label for="cv">
                                    <i class="fas fa-user-graduate doc-icon cv"></i> Hoja de Vida (CV)
                                </label>
                                
                                <?php if (!empty($contratista['cv_nombre_original'])): ?>
                                <div class="current-file">
                                    <div class="file-info">
                                        <i class="fas fa-file-pdf"></i>
                                        <div class="file-info-content">
                                            <div class="file-name"><?php echo htmlspecialchars($contratista['cv_nombre_original']); ?></div>
                                            <div class="file-size">Tamaño: <?php echo formatBytes($contratista['cv_tamano'] ?? 0); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <input type="file" 
                                       id="cv" 
                                       name="cv" 
                                       class="form-control-file"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                <span class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</span>
                            </div>
                            
                            <!-- Contrato -->
                            <div class="form-group">
                                <label for="contrato">
                                    <i class="fas fa-file-contract"></i> Contrato
                                </label>
                                
                                <?php if (!empty($contratista['contrato_nombre_original'])): ?>
                                <div class="current-file">
                                    <div class="file-info">
                                        <i class="fas fa-file-contract"></i>
                                        <div class="file-info-content">
                                            <div class="file-name"><?php echo htmlspecialchars($contratista['contrato_nombre_original']); ?></div>
                                            <div class="file-size">Tamaño: <?php echo formatBytes($contratista['contrato_tamano'] ?? 0); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <input type="file" 
                                       id="contrato" 
                                       name="contrato" 
                                       class="form-control-file"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                <span class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</span>
                            </div>
                            
                            <!-- Acta de Inicio -->
                            <div class="form-group">
                                <label for="acta_inicio">
                                    <i class="fas fa-file-signature"></i> Acta de Inicio
                                </label>
                                
                                <?php if (!empty($contratista['acta_inicio_nombre_original'])): ?>
                                <div class="current-file">
                                    <div class="file-info">
                                        <i class="fas fa-file-signature"></i>
                                        <div class="file-info-content">
                                            <div class="file-name"><?php echo htmlspecialchars($contratista['acta_inicio_nombre_original']); ?></div>
                                            <div class="file-size">Tamaño: <?php echo formatBytes($contratista['acta_inicio_tamano'] ?? 0); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <input type="file" 
                                       id="acta_inicio" 
                                       name="acta_inicio" 
                                       class="form-control-file"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                <span class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</span>
                            </div>
                            
                            <!-- Registro Presupuestal (RP) -->
                            <div class="form-group">
                                <label for="rp">
                                    <i class="fas fa-file-invoice-dollar"></i> Registro Presupuestal (RP)
                                </label>
                                
                                <?php if (!empty($contratista['rp_nombre_original'])): ?>
                                <div class="current-file">
                                    <div class="file-info">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        <div class="file-info-content">
                                            <div class="file-name"><?php echo htmlspecialchars($contratista['rp_nombre_original']); ?></div>
                                            <div class="file-size">Tamaño: <?php echo formatBytes($contratista['rp_tamano'] ?? 0); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <input type="file" 
                                       id="rp" 
                                       name="rp" 
                                       class="form-control-file"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,image/*">
                                <span class="form-help">Formatos: PDF, Word, Excel, imágenes (Máx. 5MB)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="form-actions">
                        <a href="ver_detalle.php?id_detalle=<?php echo $id_detalle; ?>" class="btn btn-cancelar">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-guardar">
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
    <script src="../../javascript/editar_contratista.js"></script>
    
</body>
</html>