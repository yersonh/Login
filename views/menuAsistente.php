<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// CORREGIDO: Verificar que sea SOLO asistente
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'asistente') {
    // Si no es asistente, redirigir según su rol
    if (isset($_SESSION['tipo_usuario'])) {
        if ($_SESSION['tipo_usuario'] === 'administrador') {
            header("Location: menu.php");
        } else if ($_SESSION['tipo_usuario'] === 'usuario') {
            header("Location: menu.php");
        } else {
            // Rol desconocido
            header("Location: ../index.php");
        }
    } else {
        header("Location: ../index.php");
    }
    exit();
}

$nombreUsuario = isset($_SESSION['nombres']) ? $_SESSION['nombres'] : '';
$apellidoUsuario = isset($_SESSION['apellidos']) ? $_SESSION['apellidos'] : '';

$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);

if (empty($nombreCompleto)) {
    $nombreCompleto = 'Usuario del Sistema';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Servicios - Secretaría de Minas y Energía</title>
    <link rel="icon" href="/imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/imagenes/logo.png" type="image/png">
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
        
        .welcome-user {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .welcome-user i {
            color: #ffd700;
        }
        
        .user-role {
            font-size: 14px;
            opacity: 0.8;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        /* Contenido principal - ESPACIOS REDUCIDOS */
        .app-main {
            flex: 1;
            padding: 25px 40px;
        }
        
        .welcome-section {
            margin-bottom: 25px;
            text-align: center;
        }
        
        .welcome-section h3 {
            font-size: 28px;
            color: #000000;
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        .welcome-section p {
            font-size: 18px;
            color: #000000;
            max-width: 800px;
            margin: 0 auto;
            font-weight: 500;
        }
        
        /* Grid de servicios moderno - 4 columnas para escritorio */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .service-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
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
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: white;
            font-size: 22px;
        }
        
        .service-card:hover .service-icon {
            transform: scale(1.1);
        }
        
        .service-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .service-desc {
            font-size: 13px;
            color: var(--gray-color);
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .service-status {
            display: inline-block;
            font-size: 11px;
            padding: 4px 12px;
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
        
        /* ========== MODAL DE CLAVE ========== */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(3px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-clave {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transform: translateY(-20px);
            transition: transform 0.4s ease;
        }
        
        .modal-overlay.active .modal-clave {
            transform: translateY(0);
        }
        
        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .modal-header h3 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .modal-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-body p {
            text-align: center;
            color: #555;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.5;
        }
        
        .input-group {
            margin-bottom: 25px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .clave-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 3px;
            transition: all 0.3s;
            background: #f9f9f9;
        }
        
        .clave-input:focus {
            border-color: var(--primary-color);
            background: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 74, 141, 0.1);
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .btn-modal {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 140px;
        }
        
        .btn-ingresar {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn-ingresar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 74, 141, 0.3);
        }
        
        .btn-cancelar {
            background: #f8f9fa;
            color: var(--gray-color);
            border: 2px solid #e0e0e0;
        }
        
        .btn-cancelar:hover {
            background: #e9ecef;
            border-color: #ced4da;
        }
        
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            min-height: 20px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .error-message.show {
            opacity: 1;
        }
        
        /* ========== RESPONSIVE ========== */
        
        /* Tablets */
        @media (max-width: 992px) {
            .services-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 18px;
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
        
        /* MÓVILES - 2 BOTONES POR FILA */
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
            
            .welcome-user {
                justify-content: center;
            }
            
            .app-main {
                padding: 20px;
            }
            
            .welcome-section {
                margin-bottom: 20px;
            }
            
            .welcome-section h3 {
                font-size: 24px;
                margin-bottom: 10px;
            }
            
            .welcome-section p {
                font-size: 16px;
            }
            
            /* 2 columnas en móviles */
            .services-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .service-card {
                padding: 18px;
            }
            
            .service-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-bottom: 15px;
            }
            
            .service-name {
                font-size: 15px;
                margin-bottom: 8px;
            }
            
            .service-desc {
                font-size: 12px;
                margin-bottom: 10px;
            }
            
            .service-status {
                font-size: 10px;
                padding: 3px 10px;
            }
            
            .app-footer {
                padding: 25px;
            }
            
            /* Responsive para modal */
            .modal-clave {
                width: 95%;
                max-width: 400px;
            }
            
            .modal-header {
                padding: 20px;
            }
            
            .modal-header h3 {
                font-size: 22px;
            }
            
            .modal-body {
                padding: 25px 20px;
            }
            
            .modal-buttons {
                flex-direction: column;
                gap: 12px;
            }
            
            .btn-modal {
                width: 100%;
                min-width: auto;
            }
        }
        
        @media (max-width: 576px) {
            .app-header, .app-main, .app-footer {
                padding: 15px;
            }
            
            .department-info h1 {
                font-size: 20px;
            }
            
            .department-info h2 {
                font-size: 18px;
            }
            
            .welcome-user {
                font-size: 16px;
            }
            
            .welcome-section {
                margin-bottom: 18px;
            }
            
            .welcome-section h3 {
                font-size: 22px;
                margin-bottom: 8px;
            }
            
            .welcome-section p {
                font-size: 15px;
            }
            
            .services-grid {
                gap: 12px;
            }
            
            .service-card {
                padding: 15px;
            }
            
            .app-footer {
                flex-direction: column;
                gap: 20px;
                padding: 20px 15px;
            }
            
            .footer-left {
                flex-direction: row;
                gap: 15px;
                width: 100%;
                justify-content: flex-start;
            }
            
            .footer-logo-container {
                flex-direction: row;
                gap: 15px;
                text-align: left;
                align-items: center;
            }
            
            .footer-logo {
                height: 60px;
                min-width: 60px;
            }
            
            .developer-info {
                font-size: 13px;
                text-align: left;
            }
            
            .developer-name {
                margin-bottom: 3px;
            }
            
            .footer-right {
                width: 100%;
                text-align: left;
            }
            
            .contact-info {
                font-size: 13px;
            }
            
            .contact-info div {
                flex-direction: row;
                justify-content: flex-start;
                gap: 8px;
                margin-bottom: 10px;
                text-align: left;
            }
            
            .contact-info i {
                min-width: 20px;
                text-align: center;
            }
            
            .copyright {
                font-size: 11px;
                text-align: left;
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px solid #eee;
            }
            
            /* Modal responsive móvil */
            .modal-header h3 {
                font-size: 20px;
            }
            
            .modal-body p {
                font-size: 15px;
            }
            
            .clave-input {
                padding: 12px;
                font-size: 16px;
            }
        }
        
        @media (max-width: 375px) {
            .department-info h1 {
                font-size: 18px;
            }
            
            .department-info h2 {
                font-size: 16px;
            }
            
            .welcome-user {
                font-size: 15px;
            }
            
            .welcome-section {
                margin-bottom: 15px;
            }
            
            .welcome-section h3 {
                font-size: 20px;
                margin-bottom: 6px;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .service-card {
                padding: 15px;
            }
            
            .app-footer {
                gap: 15px;
                padding: 15px;
            }
            
            .footer-logo-container {
                gap: 10px;
            }
            
            .footer-logo {
                height: 50px;
                min-width: 50px;
            }
            
            .developer-info {
                font-size: 12px;
            }
            
            .contact-info {
                font-size: 12px;
            }
            
            .contact-info div {
                margin-bottom: 8px;
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
        
        /* Para centrar el último botón cuando haya número impar en 2 columnas */
        @media (max-width: 768px) and (min-width: 376px) {
            .services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .service-card:last-child:nth-child(odd) {
                grid-column: span 2;
                justify-self: center;
                width: 50%;
                min-width: 200px;
            }
        }
        
        /* Estilos para verificación de permisos */
        .permission-checking {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-radius: var(--border-radius);
            z-index: 10;
            display: none;
            backdrop-filter: blur(2px);
        }
        
        .permission-checking i {
            font-size: 30px;
            color: var(--primary-color);
            margin-bottom: 10px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Estilo especial para botón de parametrización */
        .parametrizacion-card {
            position: relative;
        }
        
        .admin-only-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, #ffc107, #ff9800);
            color: #856404;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: bold;
            z-index: 5;
        }
        
        /* Animaciones de notificación */
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
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
                
                <!-- Servicio 10: PARAMETRIZACIÓN -->
                <div class="service-card parametrizacion-card" id="parametrizacion-card">
                    <div class="admin-only-badge">ADMIN</div>
                    <div class="service-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div class="service-name">Parametrización</div>
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
                <h3>Accesso restringido</h3>
                <p>Verificación de seguridad requerida</p>
            </div>
            <div class="modal-body">
                <p>Ingrese la clave autorizada para crear un contratista nuevo / CPS:</p>
                <div class="input-group">
                    <label for="inputClave">Clave de autorización:</label>
                    <input type="password" id="inputClave" class="clave-input" placeholder="Digite la clave..." maxlength="20" autocomplete="off">
                </div>
                <div class="error-message" id="errorMessage"></div>
                <div class="modal-buttons">
                    <button class="btn-modal btn-ingresar" id="btnIngresarClave">Ingresar</button>
                    <button class="btn-modal btn-cancelar" id="btnCancelarClave">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir funcionalidad a TODAS las tarjetas de servicio
            const serviceCards = document.querySelectorAll('.service-card');
            const parametrizacionCard = document.getElementById('parametrizacion-card');
            const modalClave = document.getElementById('modalClave');
            const inputClave = document.getElementById('inputClave');
            const btnIngresar = document.getElementById('btnIngresarClave');
            const btnCancelar = document.getElementById('btnCancelarClave');
            const errorMessage = document.getElementById('errorMessage');
            
            serviceCards.forEach(card => {
                card.addEventListener('click', function() {
                    const serviceName = this.querySelector('.service-name').textContent;
                    const statusElement = this.querySelector('.service-status');
                    
                    // Si es la tarjeta de Parametrización
                    if (this === parametrizacionCard && statusElement.classList.contains('status-available')) {
                        abrirModalClave();
                        return;
                    }
                    
                    // Para otros servicios
                    if (statusElement.classList.contains('status-available')) {
                        showNotification(`Accediendo a: ${serviceName}`, 'info');
                        // Aquí iría la redirección a otros servicios
                    } else {
                        showNotification(`El servicio "${serviceName}" se encuentra en mantenimiento.`, 'error');
                    }
                });
            });
            
            // Función para abrir el modal de clave
            function abrirModalClave() {
                modalClave.classList.add('active');
                inputClave.focus();
                errorMessage.classList.remove('show');
                errorMessage.textContent = '';
            }
            
            // Función para cerrar el modal de clave
            function cerrarModalClave() {
                modalClave.classList.remove('active');
                inputClave.value = '';
                errorMessage.classList.remove('show');
                errorMessage.textContent = '';
            }
            
            // Evento para el botón Ingresar
            btnIngresar.addEventListener('click', function() {
                const clave = inputClave.value.trim();
                
                if (!clave) {
                    mostrarError('Por favor ingrese la clave de autorización.');
                    inputClave.focus();
                    return;
                }
                
                // Aquí deberías hacer la validación con el backend
                // Por ahora, uso una clave de ejemplo: "admin123"
                if (clave === 'admin123') {
                    // Clave correcta - Redirigir a parametrización
                    showNotification('Clave correcta. Redirigiendo...', 'success');
                    cerrarModalClave();
                    
                    // Redirigir después de un breve momento
                    setTimeout(() => {
                        window.location.href = '../manage/parametrizacion.php';
                    }, 1000);
                } else {
                    // Clave incorrecta
                    mostrarError('Clave incorrecta. Por favor intente nuevamente.');
                    inputClave.select();
                    inputClave.focus();
                    
                    // Efecto de vibración en el input
                    inputClave.style.animation = 'shake 0.5s';
                    setTimeout(() => {
                        inputClave.style.animation = '';
                    }, 500);
                }
            });
            
            // Evento para el botón Cancelar
            btnCancelar.addEventListener('click', cerrarModalClave);
            
            // Cerrar modal con tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modalClave.classList.contains('active')) {
                    cerrarModalClave();
                }
                
                // Permitir enviar con Enter
                if (e.key === 'Enter' && modalClave.classList.contains('active')) {
                    btnIngresar.click();
                }
            });
            
            // Cerrar modal haciendo clic fuera del contenido
            modalClave.addEventListener('click', function(e) {
                if (e.target === modalClave) {
                    cerrarModalClave();
                }
            });
            
            // Función para mostrar errores
            function mostrarError(mensaje) {
                errorMessage.textContent = mensaje;
                errorMessage.classList.add('show');
            }
            
            // Función para mostrar notificaciones
            function showNotification(message, type = 'info') {
                const colors = {
                    'error': '#dc3545',
                    'success': '#28a745',
                    'info': '#17a2b8'
                };
                
                const icons = {
                    'error': 'exclamation-circle',
                    'success': 'check-circle',
                    'info': 'info-circle'
                };
                
                // Eliminar notificaciones anteriores
                const oldNotifications = document.querySelectorAll('.notification');
                oldNotifications.forEach(notification => notification.remove());
                
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${colors[type] || colors.info};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 1000;
                    animation: slideIn 0.3s ease;
                    max-width: 90%;
                    font-size: 14px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                `;
                
                // Agregar ícono
                const icon = document.createElement('i');
                icon.className = `fas fa-${icons[type] || 'info-circle'}`;
                notification.appendChild(icon);
                
                // Agregar texto
                const text = document.createElement('span');
                text.textContent = message;
                notification.appendChild(text);
                
                document.body.appendChild(notification);
                
                // Auto-eliminar después de 3 segundos
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            document.body.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }
            
            // Mostrar información de depuración en consola
            console.log('Menu Asistente - Usuario:', '<?php echo $_SESSION["correo"] ?? "No identificado"; ?>');
            console.log('Menu Asistente - Rol:', '<?php echo $_SESSION["tipo_usuario"] ?? "No definido"; ?>');
        });
    </script>
</body>
</html>