<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Principal - Secretaría de Minas y Energía</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container {
            margin-bottom: 25px;
        }
        
        .logo {
            max-height: 140px; /* Logo más grande */
            max-width: 100%;
        }
        
        .title {
            color: #000000;
            margin-bottom: 10px;
            font-size: 32px;
            font-weight: 700;
        }
        
        .subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .menu-item {
            background-color: white;
            border-radius: 10px;
            padding: 18px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border-left: 5px solid #004a8d;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            height: 100%;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        .menu-item i {
            font-size: 32px;
            margin-bottom: 12px;
            color: #004a8d;
        }
        
        .menu-item-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .menu-item-desc {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .status-indicator {
            margin-top: 5px;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-active {
            background-color: #e6f7e9;
            color: #2e7d32;
        }
        
        .status-inactive {
            background-color: #f5f5f5;
            color: #757575;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 14px;
        }
        
        .programmer-info {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .copyright {
            font-size: 13px;
            color: #888;
        }
        
        .copyright-year {
            color: #004a8d;
            font-weight: bold;
        }
        
        /* Tablet y pantallas medianas */
        @media (max-width: 992px) {
            .container {
                max-width: 95%;
            }
            
            .menu-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 columnas en tablets */
            }
            
            .title {
                font-size: 28px;
            }
            
            .logo {
                max-height: 120px;
            }
        }
        
        /* Móviles */
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 columnas en móviles */
                gap: 12px;
            }
            
            .title {
                font-size: 26px;
            }
            
            .subtitle {
                font-size: 16px;
            }
            
            .logo {
                max-height: 100px; /* Logo más grande en móviles */
            }
            
            .menu-item {
                padding: 15px 12px;
            }
            
            .menu-item i {
                font-size: 28px;
            }
            
            .menu-item-name {
                font-size: 15px;
            }
            
            .menu-item-desc {
                font-size: 12px;
            }
        }
        
        /* Móviles muy pequeños */
        @media (max-width: 480px) {
            .menu-grid {
                grid-template-columns: repeat(2, 1fr); /* Mantenemos 2 columnas */
                gap: 10px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .subtitle {
                font-size: 15px;
                margin-bottom: 25px;
            }
            
            .logo {
                max-height: 90px; /* Logo ajustado para pantallas muy pequeñas */
            }
            
            .menu-item {
                padding: 12px 10px;
                border-left-width: 4px;
            }
            
            .menu-item i {
                font-size: 26px;
                margin-bottom: 8px;
            }
            
            .menu-item-name {
                font-size: 14px;
            }
            
            .menu-item-desc {
                font-size: 11px;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 8px;
            }
            
            .footer {
                margin-top: 30px;
                padding-top: 15px;
            }
        }
        
        /* Móviles extremadamente pequeños */
        @media (max-width: 360px) {
            body {
                padding: 15px;
            }
            
            .menu-grid {
                grid-template-columns: 1fr; /* 1 columna solo en pantallas muy pequeñas */
            }
            
            .logo {
                max-height: 80px;
            }
            
            .title {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <!-- Logo de la Gobernación del Meta -->
                <img src="/../imagenes/logo.php" alt="Logo Gobernación del Meta" class="logo">
            </div>
            <h1 class="title">Secretaría de Minas y Energía</h1>
            <p class="subtitle">Selecciona una de las opciones disponibles</p>
        </div>
        
        <div class="menu-grid">
            <!-- Fila 1 con 3 botones -->
            <div class="menu-item">
                <i class="fas fa-file-contract"></i>
                <div class="menu-item-name">Gestión CPS</div>
                <div class="menu-item-desc">Control de procesos y seguimiento</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
            
            <div class="menu-item">
                <i class="fas fa-folder-open"></i>
                <div class="menu-item-name">Documentos</div>
                <div class="menu-item-desc">Acceso a archivos y documentos</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
            
            <div class="menu-item">
                <i class="fas fa-envelope"></i>
                <div class="menu-item-name">Correo</div>
                <div class="menu-item-desc">Acceso al sistema de correo</div>
                <div class="status-indicator status-active">Disponible</div>
            </div>
            
            <!-- Fila 2 con 3 botones -->
            <div class="menu-item">
                <i class="fas fa-hdd"></i>
                <div class="menu-item-name">Drive SME</div>
                <div class="menu-item-desc">Almacenamiento en la nube</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
            
            <div class="menu-item">
                <i class="fas fa-mobile-alt"></i>
                <div class="menu-item-name">APP RAI</div>
                <div class="menu-item-desc">Aplicación móvil RAI</div>
                <div class="status-indicator status-active">Disponible</div>
            </div>
            
            <div class="menu-item">
                <i class="fas fa-video"></i>
                <div class="menu-item-name">Reuniones Meet</div>
                <div class="menu-item-desc">Videoconferencias y reuniones</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
            
            <!-- Fila 3 con 3 botones -->
            <div class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <div class="menu-item-name">Agenda</div>
                <div class="menu-item-desc">Gestión de calendarios</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
            
            <div class="menu-item">
                <i class="fas fa-map-marked-alt"></i>
                <div class="menu-item-name">Mapas</div>
                <div class="menu-item-desc">Sistemas de información geográfica</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
            
            <div class="menu-item">
                <i class="fas fa-tasks"></i>
                <div class="menu-item-name">Tareas</div>
                <div class="menu-item-desc">Gestión de tareas y asignaciones</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
        </div>
        
        <div class="footer">
            <div class="programmer-info">Programador Ing. Rubén Darío González G.</div>
            <div class="contact-info">
                <span><i class="fas fa-phone-alt"></i> Cel. +57 (310) 631 0227</span>
                <span><i class="fas fa-envelope"></i> Email: sisgonnet@gmail.com</span>
            </div>
            <div class="copyright">
                Reconocidos todos los derechos de autor • <span class="copyright-year">Gobernación del Meta 2026</span>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir funcionalidad a los botones del menú
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    const itemName = this.querySelector('.menu-item-name').textContent;
                    const status = this.querySelector('.status-indicator');
                    
                    if (status.classList.contains('status-active')) {
                        alert(`Accediendo a: ${itemName}`);
                        // Aquí iría la lógica para redirigir a la funcionalidad correspondiente
                    } else {
                        alert(`${itemName} no está disponible en este momento`);
                    }
                });
            });
        });
    </script>
</body>
</html>