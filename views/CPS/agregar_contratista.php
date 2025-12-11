<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

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

// Generar consecutivo (ejemplo, normalmente vendría de BD)
$consecutivo = rand(100, 999); // Temporal
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
        
        <!-- Contenido principal -->
        <main class="app-main">
            <div class="welcome-section">
                <h3>Registrar Contratista / CPS</h3>
            </div>
            
            <div class="form-container">
                <h2 class="form-title">FORMULARIO DE INGRESO DE DATOS CONTRATISTA</h2>
                
                <div class="consecutivo-display">
                    <strong>SEI:</strong>
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
                            <label class="form-label" for="tipo_vinculacion">
                                Tipo de vinculación <span class="required">*</span>
                            </label>
                            <select id="tipo_vinculacion" name="tipo_vinculacion" class="form-control" required>
                                <option value="">Seleccione</option>
                                <option value="contratista">Contratista</option>
                                <option value="cps">CPS</option>
                                <option value="prestacion_servicios">Prestación de Servicios</option>
                                <option value="consultor">Consultor</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Sección 2: Información Geográfica -->
                <div class="form-section">
                    <h3 class="form-subtitle">INFORMACIÓN GEOGRÁFICA</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="municipio_principal">
                                Municipio 1 (principal) <span class="required">*</span>
                            </label>
                            <select id="municipio_principal" name="municipio_principal" class="form-control" required>
                                <option value="">Seleccione</option>
                                <option value="villavicencio">Villavicencio</option>
                                <option value="acacias">Acacías</option>
                                <option value="granada">Granada</option>
                                <option value="san_martin">San Martín</option>
                                <option value="puerto_lopez">Puerto López</option>
                                <option value="otro">Otro municipio</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="municipio_secundario">
                                Municipio 2 (opcional)
                            </label>
                            <select id="municipio_secundario" name="municipio_secundario" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="villavicencio">Villavicencio</option>
                                <option value="acacias">Acacías</option>
                                <option value="granada">Granada</option>
                                <option value="san_martin">San Martín</option>
                                <option value="puerto_lopez">Puerto López</option>
                                <option value="ninguno">Ninguno</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="municipio_terciario">
                                Municipio 3 (opcional)
                            </label>
                            <select id="municipio_terciario" name="municipio_terciario" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="villavicencio">Villavicencio</option>
                                <option value="acacias">Acacías</option>
                                <option value="granada">Granada</option>
                                <option value="san_martin">San Martín</option>
                                <option value="puerto_lopez">Puerto López</option>
                                <option value="ninguno">Ninguno</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="area">
                                Área
                            </label>
                            <select id="area" name="area" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="minas">Minas</option>
                                <option value="energia">Energía</option>
                                <option value="administrativa">Administrativa</option>
                                <option value="juridica">Jurídica</option>
                                <option value="tecnica">Técnica</option>
                                <option value="otra">Otra</option>
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