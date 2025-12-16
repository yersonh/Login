<?php
session_start();
require_once __DIR__ . '/../../helpers/config_helper.php';
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
    $db = $database->conectar();
    
    $areaModel = new AreaModel($db);
    $municipioModel = new MunicipioModel($db);
    $tipoModel = new TipoVinculacionModel($db);
    
    $areas = $areaModel->obtenerAreasActivas();
    $municipios = $municipioModel->obtenerMunicipiosActivos();
    $tiposVinculacion = $tipoModel->obtenerTiposActivos();
    
    function generarConsecutivo($db) {
    try {
        // Obtener el máximo actual del id_detalle
        $sql = "SELECT MAX(id_detalle) AS ultimo FROM detalle_contrato";
        $stmt = $db->query($sql);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si no hay registros, empezamos en 1
        $ultimo = $fila['ultimo'];

        if ($ultimo === null) {
            return 1;
        }

        // De lo contrario sumamos +1 al máximo encontrado
        return $ultimo + 1;

    } catch (Exception $e) {
        error_log("Error al generar consecutivo: " . $e->getMessage());
        return 1;
    }
}

    $consecutivo = generarConsecutivo($db);
    
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
                <div class="consecutivo-display">
                    <i class="fa-solid fa-user"></i> <strong>Contratista N°:</strong>
                    <span class="consecutivo-number"><?php echo $consecutivo; ?></span>
                </div>
                
                <div class="datetime-display">
                    <i class="fas fa-clock"></i> Ahora: 
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

                        <div class="form-group">
                            <label class="form-label" for="adjuntar_cv">
                                <i class="fas fa-file-pdf"></i> Adjuntar CV
                            </label>
                            <div class="file-input-container">
                                <input type="file" 
                                    id="adjuntar_cv" 
                                    name="adjuntar_cv" 
                                    class="file-input-control" 
                                    accept=".pdf,.doc,.docx">
                                <div class="file-input-info">
                                    <span class="file-input-text">Seleccionar archivo...</span>
                                    <span class="file-input-icon">
                                        <i class="fas fa-paperclip"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i> Formatos permitidos: PDF, DOC, DOCX (Máx. 5MB)
                            </div>
                            
                            <!-- Vista previa simple (opcional) -->
                            <div class="file-preview-simple" id="cvPreview" style="display: none; margin-top: 8px;">
                                <div class="preview-content">
                                    <i class="fas fa-file-pdf preview-icon"></i>
                                    <span class="preview-filename" id="cvFilename"></span>
                                    <button type="button" class="preview-remove" onclick="removeCV()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
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
        
        <!-- Primera línea concatenada -->
        <p>
            © <?php echo $anio; ?> <?php echo $entidad; ?> <?php echo $version; ?>® desarrollado por 
            <strong><?php echo $desarrollador; ?></strong>
        </p>
        
        <!-- Segunda línea concatenada -->
        <p>
            <?php echo $direccion; ?> - Asesores e-Governance Solutions para Entidades Públicas <?php echo $anio; ?>® 
            By: Ing. Rubén Darío González García <?php echo $telefono; ?>. Contacto: <strong><?php echo $correo; ?></strong> - Reservados todos los derechos de autor.  
        </p>
        

    </div>
</footer>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="../../javascript/agregar_contratista.js"></script>
    
</body>
</html>