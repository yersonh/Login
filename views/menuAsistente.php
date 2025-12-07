<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Servicios - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Enlace al archivo CSS modularizado -->
    <link rel="stylesheet" href="styles/asistente.css">
    <style>
        /* Estilos específicos para el campo de contraseña */
        .clave-container {
            position: relative;
            width: 100%;
        }
        
        .clave-input {
            width: 100%;
            padding: 12px 45px 12px 15px; /* Padding solo a la derecha para el ojo */
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: white;
        }
        
        .clave-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .clave-eye {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
            z-index: 2;
        }
        
        .clave-eye:hover {
            color: #475569;
            background-color: #f1f5f9;
        }
        
        .clave-eye:active {
            transform: translateY(-50%) scale(0.95);
        }
        
        .clave-eye.active {
            color: #3b82f6;
        }
    </style>
</head>
<body>
    
    <div class="app-container">
        <!-- Cabecera SIN LOGO CON BIENVENIDA PERSONALIZADA -->
        <header class="app-header">
            <div class="header-content">
                <div class="department-info">
                    <h1>GOBERNACIÓN DEL META</h1>
                    <h2>Secretaría de Minas y Energía</h2>
                </div>
                <div class="user-profile">
                    <!-- Mensaje personalizado de bienvenida con PHP -->
                    <div class="welcome-user">
                        <i class="fas fa-user-circle"></i>
                        <span>Bienvenido(a) <?php echo htmlspecialchars($nombreCompleto); ?></span>
                    </div>
                    <div class="user-role">Asistente</div>
                </div>
            </div>
        </header>
        
        <!-- Contenido principal -->
        <main class="app-main">
            <div class="welcome-section">
                <h3>Portal de Servicios Digitales</h3>
                <p>Seleccione uno de los servicios disponibles para acceder a las herramientas y recursos del sistema</p>
            </div>
            
            <!-- Grid de servicios -->
            <div class="services-grid">
                <!-- Servicio 1 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="service-name">Gestión CPS</div>
                    <div class="service-desc">Sistema de Control de Procesos y Seguimiento</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 2 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="service-name">Gestión Documental</div>
                    <div class="service-desc">Repositorio digital de archivos</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 3 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="service-name">Correo Institucional</div>
                    <div class="service-desc">Correo electrónico corporativo</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Servicio 4 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="service-name">Drive SME</div>
                    <div class="service-desc">Almacenamiento en la nube</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 5 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="service-name">APP RAI</div>
                    <div class="service-desc">Aplicación móvil para Reportes</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Servicio 6 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="service-name">Reuniones Virtuales</div>
                    <div class="service-desc">Videoconferencias colaborativas</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 7 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="service-name">Agenda Digital</div>
                    <div class="service-desc">Calendarios y eventos</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 8 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="service-name">Sistema de Mapas</div>
                    <div class="service-desc">Información geográfica</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 9 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="service-name">Gestor de Tareas</div>
                    <div class="service-desc">Seguimiento de actividades</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 10: Gestion del sistema -->
                <div class="service-card admin-card" id="admin-card">
                    <div class="admin-only-badge">ADMIN</div>
                    <div class="service-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div class="service-name">Gestión del Sistema</div>
                    <div class="service-desc">Configuración del sistema y parámetros</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
            </div>
        </main>
        
        <!-- Footer CON LOGO e información - FONDO BLANCO -->
        <footer class="app-footer">
            <div class="footer-left">
                <div class="footer-logo-container">
                    <!-- LOGO AQUÍ EN EL FOOTER -->
                    <img src="../../imagenes/logo.png" alt="Logo Gobernación del Meta" class="footer-logo">
                    <div class="developer-info">
                        <div class="developer-name">SisgonTech</div>
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
    
    <!-- MODAL PARA INGRESAR CLAVE -->
    <div class="modal-overlay" id="modalClave">
        <div class="modal-clave">
            <div class="modal-header">
                <h3>Acceso restringido</h3>
                <p>Verificación de seguridad requerida</p>
            </div>
            <div class="modal-body">
                <p>Ingrese la clave autorizada para parametrizar:</p>
                <div class="input-group">
                    <label for="inputClave">Clave de autorización</label>
                    <div class="clave-container">
                        <input type="password" id="inputClave" class="clave-input" placeholder="Digite la clave..." maxlength="20" autocomplete="off">
                        <button type="button" class="clave-eye" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="error-message" id="errorMessage"></div>
                <div class="modal-buttons">
                    <button class="btn-modal btn-ingresar" id="btnIngresarClave">
                        Ingresar
                    </button>
                    <button class="btn-modal btn-cancelar" id="btnCancelarClave">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript para el ojo -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('inputClave');
            
            if (toggleButton && passwordInput) {
                toggleButton.addEventListener('click', function() {
                    const eyeIcon = this.querySelector('i');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                        this.classList.add('active');
                    } else {
                        passwordInput.type = 'password';
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                        this.classList.remove('active');
                    }
                    
                    // Mantener el foco en el campo
                    passwordInput.focus();
                });
                
                // Resetear cuando se muestre el modal
                const modal = document.getElementById('modalClave');
                if (modal) {
                    // Si usas algún sistema para mostrar/ocultar el modal, agrega el evento allí
                    // Por ejemplo, si usas una función para mostrar el modal:
                    // modal.addEventListener('show', function() {
                    //     passwordInput.type = 'password';
                    //     const eyeIcon = toggleButton.querySelector('i');
                    //     eyeIcon.classList.remove('fa-eye-slash');
                    //     eyeIcon.classList.add('fa-eye');
                    //     toggleButton.classList.remove('active');
                    // });
                }
            }
        });
    </script>
    
    <!-- Enlace al archivo JavaScript modularizado -->
    <script src="../javascript/asistente.js"></script>
</body>
</html>