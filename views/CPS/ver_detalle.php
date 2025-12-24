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
    if (!empty($contratista['foto'])) {
        $foto_base64 = 'data:' . ($contratista['foto_tipo_mime'] ?? 'image/jpeg') . ';base64,' . base64_encode($contratista['foto']);
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
    <style>
        .detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .detail-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .detail-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .detail-title h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-vigente { background-color: #28a745; color: white; }
        .status-vencido { background-color: #dc3545; color: white; }
        .status-indefinido { background-color: #ffc107; color: #212529; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #1e3c72;
        }
        
        .info-card h3 {
            color: #1e3c72;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
            font-size: 1.3rem;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .info-icon {
            width: 24px;
            color: #1e3c72;
            margin-right: 12px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
        
        .info-value {
            color: #333;
            font-size: 1rem;
        }
        
        .documentos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .documento-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }
        
        .documento-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .documento-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .documento-icon.cv { background-color: #dc3545; color: white; }
        .documento-icon.contrato { background-color: #007bff; color: white; }
        .documento-icon.acta { background-color: #28a745; color: white; }
        .documento-icon.rp { background-color: #fd7e14; color: white; }
        .documento-icon.foto { background-color: #6f42c1; color: white; }
        
        .documento-info {
            flex: 1;
        }
        
        .documento-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .documento-meta {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 8px;
        }
        
        .btn-descargar {
            background-color: #1e3c72;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-descargar:hover {
            background-color: #2a5298;
        }
        
        .btn-descargar:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-volver {
            position: fixed;
            bottom: 25px;
            left: 25px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 22px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 74, 141, 0.4);
        }
        
        .empty-doc {
            color: #999;
            font-style: italic;
        }
        
        /* NUEVOS ESTILOS PARA FOTO Y PROFESIÓN */
        .foto-profesion-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .foto-contratista {
            width: 120px;
            height: 120px;
            border-radius: 10px;
            overflow: hidden;
            border: 3px solid #1e3c72;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .foto-contratista img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .foto-placeholder {
            text-align: center;
            color: #6c757d;
            padding: 10px;
        }
        
        .foto-placeholder i {
            font-size: 2.5rem;
            color: #adb5bd;
            margin-bottom: 5px;
            display: block;
        }
        
        .profesion-container {
            flex: 1;
        }
        
        .profesion-badge {
            background-color: #6f42c1;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }
        
        .profesion-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .info-card-con-foto {
            position: relative;
        }
        
        @media (max-width: 768px) {
            .detail-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .foto-profesion-container {
                flex-direction: column;
                text-align: center;
            }
            
            .foto-contratista {
                width: 150px;
                height: 150px;
            }
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
            <div class="detail-container">
                <!-- Encabezado -->
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
                    <p>
                        <i class="fas fa-info-circle"></i>
                        Información completa del contratista <?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?>
                    </p>
                </div>
                
                <!-- Sección de Foto y Profesión -->
                <div class="info-card info-card-con-foto">
                    <h3><i class="fas fa-id-card"></i> Información Personal</h3>
                    
                    <div class="foto-profesion-container">
                        <!-- Foto del Contratista -->
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
                        
                        <!-- Profesión -->
                        <div class="profesion-container">
                            <?php if (!empty($contratista['profesion'])): ?>
                                <div class="profesion-label">Profesión / Ocupación:</div>
                                <div class="profesion-badge">
                                    <i class="fas fa-graduation-cap"></i>
                                    <?php echo htmlspecialchars($contratista['profesion']); ?>
                                </div>
                            <?php else: ?>
                                <div class="profesion-label">Profesión / Ocupación:</div>
                                <div style="color: #6c757d; font-style: italic;">
                                    <i class="fas fa-graduation-cap"></i> No especificada
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Datos Personales -->
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Nombre Completo</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Cédula</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['cedula'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Teléfono / Celular</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['telefono'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Correo Electrónico</div>
                            <div class="info-value"><?php echo !empty($contratista['correo']) ? htmlspecialchars($contratista['correo']) : '<span class="empty-doc">No registrado</span>'; ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($contratista['direccion'])): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Dirección</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['direccion']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Información del Contrato -->
                <div class="info-card">
                    <h3><i class="fas fa-file-contract"></i> Información del Contrato</h3>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Número de Contrato</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['numero_contrato'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fecha del Contrato</div>
                            <div class="info-value"><?php echo $fecha_contrato; ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-play-circle"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fecha de Inicio</div>
                            <div class="info-value"><?php echo $fecha_inicio; ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fecha Final</div>
                            <div class="info-value"><?php echo $fecha_final; ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Duración</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['duracion_contrato'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($contratista['numero_registro_presupuestal'])): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Registro Presupuestal</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['numero_registro_presupuestal']); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($fecha_rp) && $fecha_rp !== 'N/A'): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fecha RP</div>
                            <div class="info-value"><?php echo $fecha_rp; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Información Laboral y Ubicación -->
                <div class="info-card">
                    <h3><i class="fas fa-briefcase"></i> Información Laboral y Ubicación</h3>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Área</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['area_nombre'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Tipo de Vinculación</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['tipo_vinculacion_nombre'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Municipio Principal</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['municipio_principal_nombre'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($contratista['direccion_municipio_principal'])): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Dirección Principal</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['direccion_municipio_principal']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($contratista['municipio_secundario_nombre'])): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Municipio Secundario</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['municipio_secundario_nombre']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($contratista['direccion_municipio_secundario'])): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Dirección Secundaria</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['direccion_municipio_secundario']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($contratista['municipio_terciario_nombre'])): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Municipio Terciario</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['municipio_terciario_nombre']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($contratista['direccion_municipio_terciario'])): ?>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Dirección Terciaria</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['direccion_municipio_terciario']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Información del Sistema -->
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Información del Sistema</h3>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Fecha de Registro</div>
                            <div class="info-value"><?php echo $created_at; ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">ID Persona</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['id_persona'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">ID Detalle</div>
                            <div class="info-value"><?php echo htmlspecialchars($contratista['id_detalle'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Referencia</div>
                            <div class="info-value">CT-<?php echo str_pad($contratista['id_detalle'] ?? '0', 5, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Documentos Adjuntos -->
                <h2 style="color: #1e3c72; margin-bottom: 20px;">
                    <i class="fas fa-paperclip"></i> Documentos Adjuntos
                </h2>
                
                <div class="documentos-grid">
                    <!-- CV -->
                    <div class="documento-card">
                        <div class="documento-icon cv">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="documento-info">
                            <h4>Hoja de Vida (CV)</h4>
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
                    </div>
                    
                    <!-- Contrato -->
                    <div class="documento-card">
                        <div class="documento-icon contrato">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="documento-info">
                            <h4>Contrato</h4>
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
                    </div>
                    
                    <!-- Acta de Inicio -->
                    <div class="documento-card">
                        <div class="documento-icon acta">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div class="documento-info">
                            <h4>Acta de Inicio</h4>
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
                    </div>
                    
                    <!-- RP (Opcional) -->
                    <?php if (!empty($contratista['rp_nombre_original'])): ?>
                    <div class="documento-card">
                        <div class="documento-icon rp">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="documento-info">
                            <h4>Registro Presupuestal</h4>
                            <div class="documento-meta">
                                <div><i class="fas fa-file"></i> <?php echo htmlspecialchars($contratista['rp_nombre_original']); ?></div>
                                <div><i class="fas fa-weight"></i> <?php echo $rp_size; ?></div>
                                <div><i class="fas fa-code"></i> <?php echo htmlspecialchars($contratista['rp_tipo_mime'] ?? 'N/A'); ?></div>
                            </div>
                            <button class="btn-descargar" onclick="descargarRP(<?php echo $id_detalle; ?>)">
                                <i class="fas fa-download"></i> Descargar RP
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Foto del Contratista (si existe) -->
                    <?php if ($foto_base64): ?>
                    <div class="documento-card">
                        <div class="documento-icon foto">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div class="documento-info">
                            <h4>Foto de Perfil</h4>
                            <div class="documento-meta">
                                <div><i class="fas fa-image"></i> Foto del contratista</div>
                                <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($contratista['nombres'] . ' ' . $contratista['apellidos']); ?></div>
                            </div>
                            <div style="color: #666; font-size: 0.85rem;">
                                <i class="fas fa-info-circle"></i> La foto se muestra arriba en la sección de información personal
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Botones de acción -->
                <div class="action-buttons">
                    <a href="visor_registrados.php" class="btn-volver">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>
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
    <script>
        function descargarCV(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_cv.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarContrato(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_contrato.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarActa(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_acta.php?id=${idDetalle}`, '_blank');
        }
        
        function descargarRP(idDetalle) {
            if (!idDetalle || idDetalle === '0') return;
            window.open(`../../controllers/descargar_rp.php?id=${idDetalle}`, '_blank');
        }
    </script>
    
</body>
</html>