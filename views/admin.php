<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
?>
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
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container {
            margin-bottom: 20px;
        }
        
        .logo {
            max-height: 80px;
            max-width: 100%;
        }
        
        .title {
            color: #004a8d;
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }
        
        .subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .menu-item {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
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
            font-size: 36px;
            margin-bottom: 15px;
            color: #004a8d;
        }
        
        .menu-item-name {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .menu-item-desc {
            font-size: 14px;
            color: #666;
        }
        
        .status-indicator {
            margin-top: 10px;
            font-size: 12px;
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
        
        /* Responsive design */
        @media (max-width: 600px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .title {
                font-size: 24px;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <!-- Aquí se cargaría el logo.png -->
                <img src="imagenes/logo.png" alt="Logo Gobernación del Meta" class="logo">
            </div>
            <h1 class="title">Secretaría de Minas y Energía</h1>
            <p class="subtitle">Selecciona una de las opciones disponibles</p>
        </div>
        
        <div class="menu-grid">
            <!-- Fila 1 -->
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
            
            <!-- Fila 2 -->
            <div class="menu-item">
                <i class="fas fa-envelope"></i>
                <div class="menu-item-name">Correo</div>
                <div class="menu-item-desc">Acceso al sistema de correo</div>
                <div class="status-indicator status-active">Disponible</div>
            </div>
            
            <div class="menu-item">
                <i class="fas fa-hdd"></i>
                <div class="menu-item-name">Drive SME</div>
                <div class="menu-item-desc">Almacenamiento en la nube</div>
                <div class="status-indicator status-inactive">No disponible</div>
            </div>
            
            <!-- Fila 3 -->
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
            
            <!-- Fila 4 -->
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
            
            <!-- Fila 5 - Solo un botón en esta fila -->
            <div class="menu-item" style="grid-column: span 2;">
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
        // Simulación de carga del logo real
        document.addEventListener('DOMContentLoaded', function() {
            // En una implementación real, aquí se cargaría el logo.png
            // document.querySelector('.logo').src = 'logo.png';
            
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