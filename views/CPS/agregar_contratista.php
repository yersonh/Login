<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

require_once '../../config/database.php';
require_once '../../models/AreaModel.php';
require_once '../../models/MunicipioModel.php';
require_once '../../models/TipoVinculacionModel.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    $redireccion = match($_SESSION['tipo_usuario'] ?? '') {
        'administrador', 'usuario' => 'menu.php',
        default => '../index.php'
    };
    header("Location: $redireccion");
    exit();
}

$nombreUsuario = htmlspecialchars($_SESSION['nombres'] ?? '');
$apellidoUsuario = htmlspecialchars($_SESSION['apellidos'] ?? '');
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
$nombreCompleto = empty($nombreCompleto) ? 'Usuario del Sistema' : $nombreCompleto;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $areaModel = new AreaModel($db);
    $municipioModel = new MunicipioModel($db);
    $tipoModel = new TipoVinculacionModel($db);
    
    $areas = $areaModel->obtenerAreasActivas();
    $municipios = $municipioModel->obtenerMunicipiosActivos();
    $tiposVinculacion = $tipoModel->obtenerTiposActivos();
    
    function generarConsecutivo() {
        $anio = date('Y');
        $mes = date('m');
        $numero = rand(1, 999);
        return "SEJ-" . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }
    
    $consecutivo = generarConsecutivo();
    
} catch (Exception $e) {
    error_log("Error al cargar datos del formulario: " . $e->getMessage());
    die("Error al cargar el formulario. Por favor contacte al administrador.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Contratista - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../styles/agregar_contratista.css">
    <style>
        .form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 35px;
            box-shadow: var(--shadow);
            margin-top: 20px;
        }
        
        .form-title {
            color: var(--primary-color);
            font-size: 26px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eaeaea;
            text-align: center;
        }
        
        .form-subtitle {
            color: var(--secondary-color);
            font-size: 20px;
            margin: 30px 0 20px;
            padding-left: 10px;
            border-left: 4px solid var(--accent-color);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 15px;
        }
        
        .form-label .required {
            color: #dc3545;
            margin-left: 3px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: var(--transition);
            background-color: #fafafa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(0, 74, 141, 0.1);
        }
        
        .form-control.small {
            max-width: 200px;
        }
        
        .form-control.medium {
            max-width: 300px;
        }
        
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23004a8d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        .consecutivo-display {
            background-color: #f0f7ff;
            border: 2px dashed var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .consecutivo-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-left: 10px;
        }
        
        .datetime-display {
            background-color: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-family: monospace;
            font-size: 15px;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #eaeaea;
        }
        
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 74, 141, 0.25);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.25);
        }
        
        .info-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid var(--accent-color);
        }
        
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #eaeaea;
        }
        
        .form-help {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }
        
        .contract-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .form-control.small,
            .form-control.medium {
                max-width: 100%;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
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
            <div class="welcome-section">
                <h3>Registrar Contratista / CPS</h3>
            </div>
            
            <div class="form-container">
                <h2 class="form-title">FORMULARIO DE INGRESO DE DATOS CONTRATISTA</h2>
                
                <div class="consecutivo-display">
                    <strong>SEJ:</strong>
                    <span class="consecutivo-number"><?php echo $consecutivo; ?></span>
                </div>
                
                <div class="datetime-display">
                    <i class="fas fa-clock"></i> Fecha/Hora Actual: 
                    <?php echo date('d/m/Y h:i:s A'); ?>
                </div>
                
                <!-- Sección 1: Datos Personales -->
                <div class="form-section">
                    <h3 class="form-subtitle">DATOS PERSONALES</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="nombre_completo">
                                Nombre completo <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="nombre_completo" 
                                   name="nombre_completo" 
                                   class="form-control" 
                                   placeholder="Ingrese el nombre completo"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="cedula">
                                Cédula de ciudadanía <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="cedula" 
                                   name="cedula" 
                                   class="form-control medium" 
                                   placeholder="Número de identificación"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="correo">
                                Correo electrónico <span class="required">*</span>
                            </label>
                            <input type="email" 
                                   id="correo" 
                                   name="correo" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="celular">
                                Número de celular <span class="required">*</span>
                            </label>
                            <input type="tel" 
                                   id="celular" 
                                   name="celular" 
                                   class="form-control medium" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="direccion">
                                Dirección
                            </label>
                            <input type="text" 
                                   id="direccion" 
                                   name="direccion" 
                                   class="form-control" 
                                   placeholder="Dirección completa">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_tipo_vinculacion">
                                Tipo de vinculación <span class="required">*</span>
                            </label>
                            <select id="id_tipo_vinculacion" name="id_tipo_vinculacion" class="form-control" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($tiposVinculacion as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo['id_tipo']) ?>">
                                    <?= htmlspecialchars($tipo['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 2: Información Geográfica -->
                <div class="form-section">
                    <h3 class="form-subtitle">INFORMACIÓN GEOGRÁFICA</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="id_municipio_principal">
                                Municipio 1 (principal) <span class="required">*</span>
                            </label>
                            <select id="id_municipio_principal" name="id_municipio_principal" class="form-control" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($municipios as $municipio): ?>
                                <option value="<?= htmlspecialchars($municipio['id_municipio']) ?>" 
                                        <?= ($municipio['nombre'] == 'Villavicencio') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($municipio['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_municipio_secundario">
                                Municipio 2 (opcional)
                            </label>
                            <select id="id_municipio_secundario" name="id_municipio_secundario" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="0">Ninguno</option>
                                <?php foreach ($municipios as $municipio): ?>
                                <option value="<?= htmlspecialchars($municipio['id_municipio']) ?>">
                                    <?= htmlspecialchars($municipio['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_municipio_terciario">
                                Municipio 3 (opcional)
                            </label>
                            <select id="id_municipio_terciario" name="id_municipio_terciario" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="0">Ninguno</option>
                                <?php foreach ($municipios as $municipio): ?>
                                <option value="<?= htmlspecialchars($municipio['id_municipio']) ?>">
                                    <?= htmlspecialchars($municipio['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_area">
                                Área <span class="required">*</span>
                            </label>
                            <select id="id_area" name="id_area" class="form-control" required>
                                <option value="">Seleccione</option>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?= htmlspecialchars($area['id_area']) ?>">
                                    <?= htmlspecialchars($area['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 3: Información del Contrato -->
                <div class="form-section">
                    <h3 class="form-subtitle">INFORMACIÓN DEL CONTRATO</h3>
                    
                    <div class="info-section">
                        <p><strong>Nota:</strong> Complete la información del contrato según los documentos oficiales</p>
                    </div>
                    
                    <div class="contract-info-grid">
                        <div class="form-group">
                            <label class="form-label" for="numero_contrato">
                                Número de contrato <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="numero_contrato" 
                                   name="numero_contrato" 
                                   class="form-control medium" 
                                   placeholder="Número del contrato"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_contrato">
                                Fecha del contrato <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="fecha_contrato" 
                                   name="fecha_contrato" 
                                   class="form-control small" 
                                   placeholder="dd/mm/aaaa"
                                   required>
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_inicio">
                                Fecha inicio <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="fecha_inicio" 
                                   name="fecha_inicio" 
                                   class="form-control small" 
                                   placeholder="dd/mm/aaaa"
                                   required>
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="duracion_contrato">
                                Duración del contrato <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="duracion_contrato" 
                                   name="duracion_contrato" 
                                   class="form-control small" 
                                   placeholder="Ej: 12 meses"
                                   required>
                            <div class="form-help">Ejemplo: 6 meses, 1 año</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_final">
                                Fecha final <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="fecha_final" 
                                   name="fecha_final" 
                                   class="form-control small" 
                                   placeholder="dd/mm/aaaa"
                                   required>
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="numero_registro_presupuestal">
                                Número registro presupuestal
                            </label>
                            <input type="text" 
                                   id="numero_registro_presupuestal" 
                                   name="numero_registro_presupuestal" 
                                   class="form-control medium" 
                                   placeholder="Número RP">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="fecha_rp">
                                Fecha RP
                            </label>
                            <input type="text" 
                                   id="fecha_rp" 
                                   name="fecha_rp" 
                                   class="form-control small" 
                                   placeholder="dd/mm/aaaa">
                            <div class="form-help">Formato: dd/mm/aaaa</div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancelarBtn">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="guardarBtn">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </main>
        
        <button class="volver-btn" id="volverBtn">
            <i class="fas fa-arrow-left"></i>
            <span>Volver al Menú</span>
        </button>
        
        <footer class="app-footer">
            <div class="footer-left">
                <div class="footer-logo-container">
                    <img src="../../imagenes/logo.png" alt="Logo Gobernación del Meta" class="footer-logo">
                    <div class="developer-info">
                        <img src="../../imagenes/sisgoTech.png" alt="Logo SisgoTech" class="footer-logo">
                    </div>
                </div>
            </div>
            <div class="footer-right">
                <div class="contact-info">
                    <div>
                        <i class="fas fa-phone-alt"></i>
                        <span>Cel. (57 -608) 6 818503</span>
                    </div>
                    <div>
                        <i class="fas fa-envelope"></i>
                        <span>gobernaciondelmeta@meta.gov.co</span>
                    </div>
                    <div>
                        <i class="fas fa-mobile-alt"></i>
                        <span>+57 (310) 631 0227</span>
                    </div>
                </div>
                <div class="copyright">
                    © <?php echo date('Y'); ?> Gobernación del Meta • Todos los derechos reservados
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="/../../javascript/agregar_contratista.js"></script>
    
</body>
</html>