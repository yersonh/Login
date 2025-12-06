<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Servicios - Secretaría de Minas y Energía</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #004a8d;
            --secondary-color: #003366;
            --accent-color: #28a745;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-radius: 12px;
            --shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .app-container {
            width: 100%;
            max-width: 1200px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            min-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header moderno SIN LOGO */
        .app-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 40px;
            position: relative;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .department-info h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        
        .department-info h2 {
            font-size: 20px;
            font-weight: 400;
            opacity: 0.9;
        }
        
        .user-profile {
            text-align: right;
        }
        
        .user-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 14px;
            opacity: 0.8;
        }
        
        /* Contenido principal */
        .app-main {
            flex: 1;
            padding: 40px;
        }
        
        .welcome-section {
            margin-bottom: 40px;
            text-align: center;
        }
        
        .welcome-section h3 {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .welcome-section p {
            font-size: 18px;
            color: var(--gray-color);
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Grid de servicios moderno */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .service-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        
        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-color);
        }
        
        .service-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: white;
            font-size: 24px;
        }
        
        .service-card:hover .service-icon {
            transform: scale(1.1);
        }
        
        .service-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .service-desc {
            font-size: 14px;
            color: var(--gray-color);
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .service-status {
            display: inline-block;
            font-size: 12px;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .status-available {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--accent-color);
        }
        
        .status-unavailable {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--gray-color);
        }
        
        /* Footer CON LOGO - Fondo blanco */
        .app-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            padding: 30px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .footer-left {
            display: flex;
            align-items: center;
            gap: 25px;
            flex: 1;
        }
        
        .footer-logo-container {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .footer-logo {
            height: 80px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
        }
        
        .developer-info {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .developer-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .footer-right {
            text-align: right;
            flex: 1;
        }
        
        .contact-info {
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .contact-info div {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .copyright {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 10px;
        }
        
        /* ========== RESPONSIVE ========== */
        
        /* Tablets */
        @media (max-width: 992px) {
            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .app-footer {
                flex-direction: column;
                text-align: center;
                gap: 25px;
            }
            
            .footer-left, .footer-right {
                width: 100%;
                justify-content: center;
            }
            
            .contact-info div {
                justify-content: center;
            }
            
            .footer-left {
                flex-direction: column;
                gap: 20px;
            }
            
            .footer-logo-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
        
        /* Tablets pequeñas y móviles grandes */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .app-container {
                min-height: auto;
            }
            
            .app-header {
                padding: 20px;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .user-profile {
                text-align: center;
            }
            
            .app-main {
                padding: 25px;
            }
            
            .welcome-section h3 {
                font-size: 24px;
            }
            
            .welcome-section p {
                font-size: 16px;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .service-card {
                padding: 20px;
            }
            
            .app-footer {
                padding: 25px;
            }
        }
        
        /* Móviles */
        @media (max-width: 576px) {
            .app-header, .app-main, .app-footer {
                padding: 20px 15px;
            }
            
            .department-info h1 {
                font-size: 20px;
            }
            
            .department-info h2 {
                font-size: 18px;
            }
            
            .welcome-section h3 {
                font-size: 22px;
            }
            
            .welcome-section p {
                font-size: 15px;
            }
            
            .service-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .service-name {
                font-size: 16px;
            }
            
            .service-desc {
                font-size: 13px;
            }
            
            .footer-logo {
                height: 60px;
            }
            
            .developer-info {
                font-size: 13px;
            }
            
            .contact-info {
                font-size: 13px;
            }
            
            .contact-info div {
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }
            
            .copyright {
                font-size: 11px;
            }
        }
        
        /* Móviles muy pequeños */
        @media (max-width: 375px) {
            .department-info h1 {
                font-size: 18px;
            }
            
            .department-info h2 {
                font-size: 16px;
            }
            
            .welcome-section h3 {
                font-size: 20px;
                margin-bottom: 10px;
            }
            
            .service-card {
                padding: 15px;
            }
            
            .app-footer {
                padding: 20px 15px;
                gap: 20px;
            }
            
            .footer-logo-container {
                gap: 10px;
            }
            
            .footer-logo {
                height: 50px;
            }
        }
        
        /* Efecto de onda en hover para tarjetas */
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }
        
        .service-card:hover::before {
            transform: scaleX(1);
        }
        
        /* Animación para íconos */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }
        
        .service-card:hover .service-icon {
            animation: float 1.5s ease-in-out infinite;
        }
        
        /* Para centrar el último botón en 2 columnas en móviles */
        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Cabecera SIN LOGO -->
        <header class="app-header">
            <div class="header-content">
                <div class="department-info">
                    <h1>GOBERNACIÓN DEL META</h1>
                    <h2>Secretaría de Minas y Energía</h2>
                </div>
                <div class="user-profile">
                    <div class="user-name">Portal de Servicios</div>
                    <div class="user-role">Acceso a sistemas institucionales</div>
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
                    <div class="service-desc">Sistema de Control de Procesos y Seguimiento de trámites y actividades</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 2 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="service-name">Gestión Documental</div>
                    <div class="service-desc">Repositorio digital de archivos y documentos institucionales</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 3 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="service-name">Correo Institucional</div>
                    <div class="service-desc">Acceso al sistema de correo electrónico corporativo</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Servicio 4 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="service-name">Drive SME</div>
                    <div class="service-desc">Almacenamiento en la nube para documentos y recursos compartidos</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 5 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="service-name">APP RAI</div>
                    <div class="service-desc">Aplicación móvil para Reportes y Alertas Institucionales</div>
                    <div class="service-status status-available">Disponible</div>
                </div>
                
                <!-- Servicio 6 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="service-name">Reuniones Virtuales</div>
                    <div class="service-desc">Plataforma para videoconferencias y reuniones colaborativas</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 7 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="service-name">Agenda Digital</div>
                    <div class="service-desc">Gestión de calendarios, eventos y actividades institucionales</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 8 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="service-name">Sistema de Mapas</div>
                    <div class="service-desc">Herramientas de información geográfica y cartografía digital</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
                
                <!-- Servicio 9 -->
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="service-name">Gestor de Tareas</div>
                    <div class="service-desc">Sistema de seguimiento y asignación de actividades</div>
                    <div class="service-status status-unavailable">No disponible</div>
                </div>
            </div>
        </main>
        
        <!-- Footer CON LOGO e información - FONDO BLANCO -->
        <footer class="app-footer">
            <div class="footer-left">
                <div class="footer-logo-container">
                    <!-- LOGO AQUÍ EN EL FOOTER -->
                    <img src="/../../imagenes/logo.png" alt="Logo Gobernación del Meta" class="footer-logo">
                    <div class="developer-info">
                        <div class="developer-name">SisgonTech Solutions</div>
                        <div>Desarrollado por Ing. Rubén Darío González G.</div>
                    </div>
                </div>
            </div>
            <div class="footer-right">
                <div class="contact-info">
                    <div><i class="fas fa-phone-alt"></i> Cel. (57 -608) 6 818503</div>
                    <div><i class="fas fa-envelope"></i> gobernaciondelmeta@meta.gov.co</div>
                    <div><i class="fas fa-mobile-alt"></i> +57 (310) 631 0227</div>
                </div>
                <div class="copyright">
                    © 2026 Gobernación del Meta • Todos los derechos reservados
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir funcionalidad a las tarjetas de servicio
            const serviceCards = document.querySelectorAll('.service-card');
            
            serviceCards.forEach(card => {
                card.addEventListener('click', function() {
                    const serviceName = this.querySelector('.service-name').textContent;
                    const statusElement = this.querySelector('.service-status');
                    
                    if (statusElement.classList.contains('status-available')) {
                        // Aquí iría la lógica para redirigir al servicio
                        alert(`Accediendo a: ${serviceName}`);
                    } else {
                        // Mostrar mensaje de servicio no disponible
                        const unavailableMsg = document.createElement('div');
                        unavailableMsg.className = 'unavailable-message';
                        unavailableMsg.textContent = `El servicio "${serviceName}" se encuentra en mantenimiento.`;
                        unavailableMsg.style.cssText = `
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            background: #dc3545;
                            color: white;
                            padding: 15px 20px;
                            border-radius: 8px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                            z-index: 1000;
                            animation: slideIn 0.3s ease;
                            max-width: 90%;
                            font-size: 14px;
                        `;
                        
                        document.body.appendChild(unavailableMsg);
                        
                        setTimeout(() => {
                            unavailableMsg.style.animation = 'slideOut 0.3s ease';
                            setTimeout(() => {
                                document.body.removeChild(unavailableMsg);
                            }, 300);
                        }, 3000);
                    }
                });
            });
            
            // Añadir estilos CSS para animaciones
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                
                @media (max-width: 768px) {
                    .unavailable-message {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                        max-width: calc(100% - 20px);
                        text-align: center;
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Mejorar el responsive del logo
            const logo = document.querySelector('.footer-logo');
            if (logo) {
                logo.onerror = function() {
                    this.src = 'https://via.placeholder.com/200x80/004a8d/ffffff?text=Gobernación+del+Meta';
                    this.alt = 'Logo Gobernación del Meta (placeholder)';
                };
            }
        });
    </script>
</body>
</html>