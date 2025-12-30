<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/ContratistaModel.php';

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
    header("Location: visor_registrados.php");
    exit();
}

$id_detalle = (int)$_GET['id_detalle'];

// Obtener datos completos del contratista
try {
    $database = new Database();
    $db = $database->conectar();
    $contratistaModel = new ContratistaModel($db);
    
    $contratista = $contratistaModel->obtenerContratistaPorId($id_detalle);
    
    if (!$contratista) {
        $_SESSION['error'] = "Contratista no encontrado";
        header("Location: visor_registrados.php");
        exit();
    }
    
    // Formatear fechas
    $fecha_contrato = !empty($contratista['fecha_contrato']) ? date('d/m/Y', strtotime($contratista['fecha_contrato'])) : 'N/A';
    $fecha_inicio = !empty($contratista['fecha_inicio']) ? date('d/m/Y', strtotime($contratista['fecha_inicio'])) : 'N/A';
    $fecha_final = !empty($contratista['fecha_final']) ? date('d/m/Y', strtotime($contratista['fecha_final'])) : 'N/A';
    $fecha_rp = !empty($contratista['fecha_rp']) ? date('d/m/Y', strtotime($contratista['fecha_rp'])) : 'N/A';
    $created_at = !empty($contratista['created_at']) ? date('d/m/Y H:i:s', strtotime($contratista['created_at'])) : 'N/A';
    
    // Determinar estado
    $estado = 'indefinido';
    if (!empty($contratista['fecha_final'])) {
        try {
            $fechaFin = new DateTime($contratista['fecha_final']);
            $hoy = new DateTime();
            $estado = $fechaFin > $hoy ? 'vigente' : 'vencido';
        } catch (Exception $e) {
            $estado = 'indefinido';
        }
    }
    
    // Tamaños de archivos en formato legible
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    $cv_size = !empty($contratista['cv_tamano']) ? formatBytes($contratista['cv_tamano']) : 'N/A';
    $contrato_size = !empty($contratista['contrato_tamano']) ? formatBytes($contratista['contrato_tamano']) : 'N/A';
    $acta_size = !empty($contratista['acta_inicio_tamano']) ? formatBytes($contratista['acta_inicio_tamano']) : 'N/A';
    $rp_size = !empty($contratista['rp_tamano']) ? formatBytes($contratista['rp_tamano']) : 'N/A';
    
    // Obtener foto del contratista si existe
    $foto_base64 = null;
    if (!empty($contratista['foto_contenido'])) {
        $foto_base64 = 'data:' . ($contratista['foto_tipo_mime'] ?? 'image/jpeg') . ';base64,' . base64_encode($contratista['foto_contenido']);
    }
    
} catch (Exception $e) {
    error_log("Error al cargar detalles del contratista: " . $e->getMessage());
    $_SESSION['error'] = "Error al cargar los datos del contratista";
    header("Location: visor_registrados.php");
    exit();
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
    <title>Detalles del Contratista - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/visor_registrados.css">
    <link rel="stylesheet" href="../styles/ver_detalle_contratista.css">
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
            <div class="detail-container">
                <!-- ENCABEZADO -->
                <div class="detail-header">
                    <div class="detail-title">
                        <h1>
                            <i class="fas fa-user-tie"></i>
                            Detalles del Contratista
                        </h1>
                        <span class="status-badge status-<?php echo $estado; ?>">
                            <i class="fas fa-<?php echo $estado === 'vigente' ? 'check-circle' : ($estado === 'vencido' ? 'exclamation-circle' : 'question-circle'); ?>"></i>
                            <?php echo ucfirst($estado); ?>
                        </span>
                    </div>
                    <p style="margin: 0; font-size: 0.95rem; opacity: 0.9;">
                        <i class="fas fa-info-circle"></i>
                        Información completa del contratista <strong><?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?></strong>
                    </p>
                </div>
                
                <!-- LAYOUT PRINCIPAL - DOS COLUMNAS -->
                <div class="main-content-grid">
                    <!-- COLUMNA IZQUIERDA - INFORMACIÓN -->
                    <div class="left-column">
                        <!-- INFORMACIÓN PERSONAL -->
                        <div class="info-card">
                            <h3><i class="fas fa-id-card"></i> Información Personal</h3>
                            <div class="info-grid-compact">
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Nombre Completo</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Cédula</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['cedula'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Teléfono / Celular</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['telefono'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Correo Electrónico</div>
                                        <div class="info-value-compact"><?php echo !empty($contratista['correo_personal']) ? htmlspecialchars($contratista['correo_personal']) : '<span style="color:#999; font-style:italic">No registrado</span>'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Dirección</div>
                                        <div class="info-value-compact"><?php echo !empty($contratista['direccion']) ? htmlspecialchars($contratista['direccion']) : '<span style="color:#999; font-style:italic">No registrada</span>'; ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($contratista['profesion'])): ?>
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Profesión</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['profesion']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- INFORMACIÓN DEL CONTRATO -->
                        <div class="info-card">
                            <h3><i class="fas fa-file-contract"></i> Información del Contrato</h3>
                            <div class="info-grid-compact">
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Número de Contrato</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['numero_contrato'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Fecha del Contrato</div>
                                        <div class="info-value-compact"><?php echo $fecha_contrato; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Fecha de Inicio</div>
                                        <div class="info-value-compact"><?php echo $fecha_inicio; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-flag-checkered"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Fecha Final</div>
                                        <div class="info-value-compact"><?php echo $fecha_final; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Duración</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['duracion_contrato'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($contratista['numero_registro_presupuestal'])): ?>
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Registro Presupuestal</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['numero_registro_presupuestal']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($fecha_rp) && $fecha_rp !== 'N/A'): ?>
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Fecha RP</div>
                                        <div class="info-value-compact"><?php echo $fecha_rp; ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- INFORMACIÓN LABORAL Y UBICACIÓN -->
                        <div class="info-card">
                            <h3><i class="fas fa-briefcase"></i> Información Laboral y Ubicación</h3>
                            <div class="info-grid-compact">
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-sitemap"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Área</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['area_nombre'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-link"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Tipo de Vinculación</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['tipo_vinculacion_nombre'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Municipio Principal</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['municipio_principal_nombre'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($contratista['direccion_municipio_principal'])): ?>
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Dirección Principal</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['direccion_municipio_principal']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($contratista['municipio_secundario_nombre'])): ?>
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-map-marker"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Municipio Secundario</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['municipio_secundario_nombre']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($contratista['direccion_municipio_secundario'])): ?>
                                <div class="info-item-compact">
                                    <div class="info-icon-compact">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="info-content-compact">
                                        <div class="info-label-compact">Dirección Secundaria</div>
                                        <div class="info-value-compact"><?php echo htmlspecialchars($contratista['direccion_municipio_secundario']); ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- COLUMNA DERECHA - FOTO Y DOCUMENTOS -->
                    <div class="right-column">
                        <!-- FOTO Y PROFESIÓN -->
                        <div class="foto-profesion-card">
                            <h3 style="text-align: left; margin-bottom: 15px;">
                                <i class="fas fa-camera"></i> Foto del Contratista
                            </h3>
                            
                            <div class="foto-contratista">
                                <?php if ($foto_base64): ?>
                                    <img src="<?php echo $foto_base64; ?>" alt="Foto de <?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?>">
                                <?php else: ?>
                                    <div class="foto-placeholder">
                                        <i class="fas fa-user-circle"></i>
                                        <span>Sin foto</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                        
                        <!-- DOCUMENTOS ADJUNTOS -->
                        <div class="documentos-card">
                            <h3><i class="fas fa-paperclip"></i> Documentos Adjuntos</h3>
                            
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
                                            <div><i class="fas fa-weight"></i> <?php echo $cv_size; ?></div>
                                            <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['cv_tipo_mime'] ?? 'N/A'); ?></div>
                                        <?php else: ?>
                                            <div class="empty-doc">No se ha cargado hoja de vida</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="btn-descargar" 
                                            onclick="descargarCV(<?php echo $id_detalle; ?>)"
                                            <?php echo empty($contratista['cv_nombre_original']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-download"></i> Descargar CV
                                    </button>
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
                                            <div><i class="fas fa-weight"></i> <?php echo $contrato_size; ?></div>
                                            <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['contrato_tipo_mime'] ?? 'N/A'); ?></div>
                                        <?php else: ?>
                                            <div class="empty-doc">No se ha cargado contrato</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="btn-descargar" 
                                            onclick="descargarContrato(<?php echo $id_detalle; ?>)"
                                            <?php echo empty($contratista['contrato_nombre_original']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-download"></i> Descargar Contrato
                                    </button>
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
                                            <div><i class="fas fa-weight"></i> <?php echo $acta_size; ?></div>
                                            <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['acta_inicio_tipo_mime'] ?? 'N/A'); ?></div>
                                        <?php else: ?>
                                            <div class="empty-doc">No se ha cargado acta de inicio</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="btn-descargar" 
                                            onclick="descargarActa(<?php echo $id_detalle; ?>)"
                                            <?php echo empty($contratista['acta_inicio_nombre_original']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-download"></i> Descargar Acta
                                    </button>
                                </div>
                                
                                <!-- REGISTRO PRESUPUESTAL (SIEMPRE VISIBLE) -->
                                <div class="documento-item">
                                    <div class="documento-header">
                                        <div class="documento-title">
                                            <div class="documento-icon rp">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </div>
                                            Registro Presupuestal
                                        </div>
                                    </div>
                                    
                                    <div class="documento-meta">
                                        <?php if (!empty($contratista['rp_nombre_original'])): ?>
                                            <div><i class="fas fa-file"></i> <?php echo htmlspecialchars($contratista['rp_nombre_original']); ?></div>
                                            <div><i class="fas fa-weight"></i> <?php echo $rp_size; ?></div>
                                            <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['rp_tipo_mime'] ?? 'N/A'); ?></div>
                                        <?php else: ?>
                                            <div class="empty-doc">No se ha cargado registro presupuestal</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="btn-descargar" 
                                            onclick="descargarRP(<?php echo $id_detalle; ?>)"
                                            <?php echo empty($contratista['rp_nombre_original']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-download"></i> Descargar RP
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DEL SISTEMA -->
                <div class="info-card" style="margin-top: 20px;">
                    <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                    <div class="info-grid-compact">
                        <div class="info-item-compact">
                            <div class="info-icon-compact">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div class="info-content-compact">
                                <div class="info-label-compact">Fecha de Registro</div>
                                <div class="info-value-compact"><?php echo $created_at; ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item-compact">
                            <div class="info-icon-compact">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <div class="info-content-compact">
                                <div class="info-label-compact">ID Persona</div>
                                <div class="info-value-compact"><?php echo htmlspecialchars($contratista['id_persona'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item-compact">
                            <div class="info-icon-compact">
                                <i class="fas fa-id-badge"></i>
                            </div>
                            <div class="info-content-compact">
                                <div class="info-label-compact">ID Detalle</div>
                                <div class="info-value-compact"><?php echo htmlspecialchars($contratista['id_detalle'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-item-compact">
                            <div class="info-icon-compact">
                                <i class="fas fa-code"></i>
                            </div>
                            <div class="info-content-compact">
                                <div class="info-label-compact">Referencia</div>
                                <div class="info-value-compact">CT-<?php echo str_pad($contratista['id_detalle'] ?? '0', 5, '0', STR_PAD_LEFT); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- BOTÓN VOLVER -->
                 <button class="btn-volver" id="volverBtn" onclick="window.location.href='visor_registrados.php'";>
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver al Menú</span>
                </button>
                <!--<a href="visor_registrados.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver al Listado
                </a>-->
            </div>
        </main>
        
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
    
    <!-- Scripts para descargar documentos -->
   <script src="../../javascript/ver_detalle_contratista.js"></script>
    
</body>
</html>