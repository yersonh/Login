<?php

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Secretaría de Minas y Energía</title>
    <link rel="icon" href="../../imagenes/logo.png" type="image/png">
    <link rel="shortcut icon" href="../../imagenes/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Enlace al CSS modularizado -->
    <link rel="stylesheet" href="/styles/admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Logo Institucional -->
            <div class="logo-section">
                <img src="../../imagenes/logo.png" alt="Logo Gobernación del Meta" class="admin-logo">
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

            <!-- Sección de Reportes -->
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
            </div>

            <!-- Enlaces de Soporte -->
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
                    </li>
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
            </div>

            <!-- Estadísticas -->
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
            </div>

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
                <img src="../../imagenes/logo.png" alt="Logo" class="footer-logo">
                <p>© <?php echo date('Y'); ?> Gobernación del Meta - Secretaría de Minas y Energía</p>
                <p class="footer-info">
                    Sistema de Gestión Administrativa • Versión 2.1.0 • 
                    <a href="#" class="footer-link">Políticas de Uso</a>
                </p>
            </footer>
        </main>
    </div>

    <!-- Modal de Configuración -->
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
    </div>

    <!-- Enlace al archivo JavaScript modularizado -->
    <script src="../javascript/admin.js"></script>
</body>
</html>