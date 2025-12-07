<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

// Verificar que sea administrador (ya sea administrador original o asistente con acceso)
if ($_SESSION['tipo_usuario'] !== 'administrador') {
    // Si no es administrador, redirigir según su rol
    if ($_SESSION['tipo_usuario'] === 'asistente') {
        header("Location: menuAsistente.php");
    } else if ($_SESSION['tipo_usuario'] === 'usuario') {
        header("Location: menu.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

// Datos del usuario
$nombreUsuario = $_SESSION['nombres'] ?? '';
$apellidoUsuario = $_SESSION['apellidos'] ?? '';
$nombreCompleto = trim($nombreUsuario . ' ' . $apellidoUsuario);
if (empty($nombreCompleto)) {
    $nombreCompleto = 'Administrador del Sistema';
}

$correoUsuario = $_SESSION['correo'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Secretaría de Minas y Energía</title>
    <link rel="icon" href="../imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="/../imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Enlace al CSS modularizado -->
    <link rel="stylesheet" href="styles/admin.css">
    <style>
        /* Botón para volver como asistente */
        .return-assistant-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px 22px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
            display: <?php echo isset($_SESSION['usuario_original']) ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            transition: all 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .return-assistant-btn:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);
        }
        
        .return-assistant-btn i {
            font-size: 18px;
        }
        
        /* Indicador en el sidebar */
        .session-info {
            margin-top: 15px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            border-left: 3px solid #f59e0b;
        }
        
        .session-info strong {
            color: white;
            display: block;
            margin-bottom: 3px;
            font-weight: 600;
        }
        
        .session-info span {
            display: block;
            font-size: 11px;
            margin-top: 5px;
        }
        
        /* Banner informativo en el header */
        .access-info-banner {
            margin-top: 15px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
            display: <?php echo isset($_SESSION['usuario_original']) ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 12px;
        }
        
        .access-info-banner i {
            color: #d97706;
            font-size: 18px;
        }
        
        .access-info-banner .banner-content {
            color: #92400e;
            font-weight: 500;
            flex: 1;
        }
        
        .access-info-banner .banner-link {
            color: #b45309;
            text-decoration: underline;
            cursor: pointer;
            font-weight: 600;
            transition: color 0.3s ease;
            margin-left: 8px;
        }
        
        .access-info-banner .banner-link:hover {
            color: #92400e;
        }
    </style>
</head>
<body>
    
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Logo Institucional -->
            <div class="logo-section">
                <img src="../imagenes/logo.png" alt="Logo Gobernación del Meta" class="admin-logo">
                <div class="department-name">GOBERNACIÓN DEL META</div>
                <div class="department-subtitle">Secretaría de Minas y Energía</div>
            </div>

            <!-- Perfil del Usuario -->
            <div class="user-profile-sidebar">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($nombreCompleto); ?></div>
                <div class="user-role">Administrador Principal</div>
                <div class="user-email">
                    <?php echo htmlspecialchars($correoUsuario); ?>
                </div>
                
                <?php if (isset($_SESSION['usuario_original'])): ?>
                <div class="session-info">
                    <strong><i class="fas fa-user-clock"></i> Acceso Especial</strong>
                    Has ingresado desde una cuenta de asistente
                    <span>
                        <i class="fas fa-history"></i> 
                        <?php 
                        if (isset($_SESSION['admin_login_timestamp'])) {
                            $hora = date('H:i', $_SESSION['admin_login_timestamp']);
                            echo "Ingresado a las $hora";
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Menú de Navegación -->
            <div class="nav-section">
                <div class="nav-title">Administración del Sistema</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="parametrizar.php" class="nav-link active">
                            <span class="nav-icon"><i class="fas fa-sliders-h"></i></span>
                            <span class="nav-text">Parametrización</span>
                            <span class="nav-badge">Nuevo</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="gestion_usuarios.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-users-cog"></i></span>
                            <span class="nav-text">Gestión de Usuarios</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="registros_sistema.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-history"></i></span>
                            <span class="nav-text">Registros del Sistema</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="configuracion.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-cog"></i></span>
                            <span class="nav-text">Configuración</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Sección de Reportes 
            <div class="nav-section" style="margin-top: 30px;">
                <div class="nav-title">Reportes y Análisis</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="reportes.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
                            <span class="nav-text">Reportes Generales</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="auditoria.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>
                            <span class="nav-text">Auditoría</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="estadisticas.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                            <span class="nav-text">Estadísticas</span>
                        </a>
                    </li>
                </ul>
            </div>-->

            <!-- Enlaces de Soporte 
            <div class="nav-section" style="margin-top: 30px;">
                <div class="nav-title">Soporte y Ayuda</div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="soporte.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-headset"></i></span>
                            <span class="nav-text">Soporte Técnico</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="manuales.php" class="nav-link">
                            <span class="nav-icon"><i class="fas fa-book"></i></span>
                            <span class="nav-text">Manuales</span>
                        </a>
                    </li>-->
                    <li class="nav-item">
                        <a href="../logout.php" class="nav-link logout-link">
                            <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                            <span class="nav-text">Cerrar Sesión</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Información de Versión -->
            <div class="version-info">
                v2.1.0 • Sistema Administrativo SME
            </div>
        </aside>

        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Encabezado -->
            <div class="main-header">
                <h1 class="welcome-title">Panel de Control Administrativo</h1>
                <p class="welcome-subtitle">Gestione todos los aspectos del sistema de la Secretaría de Minas y Energía</p>
                
                <?php if (isset($_SESSION['usuario_original'])): ?>
                <div class="access-info-banner">
                    <i class="fas fa-info-circle"></i>
                    <div class="banner-content">
                        Has ingresado con credenciales de administrador. 
                        <span class="banner-link" onclick="volverComoAsistente()">Volver como asistente</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Estadísticas 
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value">156</div>
                    <div class="stat-label">Usuarios Activos</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> 12% este mes
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-value">2,847</div>
                    <div class="stat-label">Procesos Activos</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> 8% este mes
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="stat-value">99.8%</div>
                    <div class="stat-label">Tiempo Activo del Sistema</div>
                    <div class="stat-change positive">
                        <i class="fas fa-check-circle"></i> Estable
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value">3</div>
                    <div class="stat-label">Alertas Activas</div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i> Requiere atención
                    </div>
                </div>
            </div>-->

            <!-- Actividad Reciente -->
            <div class="recent-activity">
                <div class="section-title">
                    <span>Actividad Reciente del Sistema</span>
                    <a href="#" class="view-all-link">
                        Ver todo <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon user">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Nuevo usuario registrado</div>
                            <div class="activity-time">Hace 15 minutos • María González</div>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon security">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Cambio de permisos de acceso</div>
                            <div class="activity-time">Hace 1 hora • Departamento de Licencias</div>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon system">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Backup automático completado</div>
                            <div class="activity-time">Hace 3 horas • Sistema automático</div>
                        </div>
                    </li>
                    
                    <li class="activity-item">
                        <div class="activity-icon user">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Verificación de asistente completada</div>
                            <div class="activity-time">Hoy 10:30 AM • Carlos Rodríguez</div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Acciones Rápidas -->
            <div class="quick-actions">
                <div class="section-title">
                    <span>Acciones Rápidas</span>
                </div>
                <div class="actions-grid">
                    <button class="action-btn" onclick="window.location.href='gestion_usuarios.php'">
                        <i class="fas fa-user-plus"></i>
                        <span>Agregar Usuario</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='reportes.php'">
                        <i class="fas fa-file-export"></i>
                        <span>Generar Reporte</span>
                    </button>
                    <button class="action-btn" onclick="window.location.href='configuracion.php'">
                        <i class="fas fa-backup"></i>
                        <span>Backup del Sistema</span>
                    </button>
                    <button class="action-btn" onclick="mostrarModalConfiguracion()">
                        <i class="fas fa-bell"></i>
                        <span>Configurar Alertas</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <footer class="admin-footer">
                <img src="../imagenes/logo.png" alt="Logo" class="footer-logo">
                <p>© <?php echo date('Y'); ?> Gobernación del Meta - Secretaría de Minas y Energía</p>
                <p class="footer-info">
                    SGEA • Versión 1.0.0 (Runtime) • Desarrollado por SisgonTech 
                    <a href="#" class="footer-link">Políticas de Uso</a>
                </p>
            </footer>
        </main>
        
        <!-- Botón flotante para volver como asistente -->
        <?php if (isset($_SESSION['usuario_original'])): ?>
        <button class="return-assistant-btn" onclick="volverComoAsistente()">
            <i class="fas fa-exchange-alt"></i>
            Volver como Asistente
        </button>
        <?php endif; ?>
    </div>

    <!-- Modal de Configuración 
    <div class="modal-overlay" id="configModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-cog"></i> Configuración del Sistema</h3>
                <button class="modal-close" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="systemName">Nombre del Sistema</label>
                    <input type="text" id="systemName" class="form-control" value="Sistema SME - Admin">
                </div>
                <div class="form-group">
                    <label for="sessionTimeout">Tiempo de Sesión (minutos)</label>
                    <input type="number" id="sessionTimeout" class="form-control" value="30" min="5" max="240">
                </div>
                <div class="form-group">
                    <label for="backupFrequency">Frecuencia de Backup</label>
                    <select id="backupFrequency" class="form-control">
                        <option value="daily">Diario</option>
                        <option value="weekly" selected>Semanal</option>
                        <option value="monthly">Mensual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="emailNotifications" checked>
                        <span>Notificaciones por Email</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarConfiguracion()">Guardar Cambios</button>
            </div>
        </div>
    </div>-->

    <script>
        // Función para volver como asistente
        function volverComoAsistente() {
            if (confirm('¿Desea volver a su sesión original como asistente?')) {
                // Mostrar mensaje de carga
                const btn = document.querySelector('.return-assistant-btn');
                if (btn) {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Regresando...';
                    btn.disabled = true;
                    
                    // Redirigir al script que maneja el cambio
                    window.location.href = '../ajax/volver_asistente.php';
                    
                    // Restaurar botón después de 3 segundos si no se redirige
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }, 3000);
                } else {
                    window.location.href = '../ajax/volver_asistente.php';
                }
            }
        }
        
        // Cerrar sesión de administrador después de inactividad (1 hora) - solo para asistentes
        <?php if (isset($_SESSION['usuario_original'])): ?>
        let inactivityTimer;
        
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            
            // Aplicar timeout solo si es un acceso desde asistente
            inactivityTimer = setTimeout(() => {
                if (confirm('Su sesión de administrador ha expirado por inactividad. ¿Volver como asistente?')) {
                    window.location.href = '../ajax/volver_asistente.php';
                }
            }, 3600000); // 1 hora = 3,600,000 ms
        }
        
        // Reiniciar timer en eventos de usuario
        ['mousemove', 'keypress', 'click', 'scroll'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer);
        });
        
        // Iniciar timer
        resetInactivityTimer();
        <?php endif; ?>
    </script>

    <!-- Enlace al archivo JavaScript modularizado -->
    <script src="../javascript/admin.js"></script>
</body>
</html>