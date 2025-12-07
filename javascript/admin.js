// javascript/admin.js - Scripts para el Panel Administrativo

class AdminPanel {
    constructor() {
        this.initializeElements();
        this.initializeEvents();
        this.initAnimations();
        this.initNotifications();
    }

    initializeElements() {
        // Elementos del DOM
        this.navLinks = document.querySelectorAll('.nav-link');
        this.statCards = document.querySelectorAll('.stat-card');
        this.activityItems = document.querySelectorAll('.activity-item');
        this.actionButtons = document.querySelectorAll('.action-btn');
        this.modal = document.getElementById('configModal');
        this.modalClose = document.querySelector('.modal-close');
        this.statValues = document.querySelectorAll('.stat-value');
        this.currentPage = window.location.pathname.split('/').pop();
    }

    initializeEvents() {
        // Activar enlace actual en el menú
        this.highlightCurrentPage();
        
        // Eventos de navegación
        this.navLinks.forEach(link => {
            link.addEventListener('click', (e) => this.handleNavClick(e));
        });

        // Eventos de tarjetas de estadísticas
        this.statCards.forEach(card => {
            card.addEventListener('mouseenter', () => this.handleCardHover(card, true));
            card.addEventListener('mouseleave', () => this.handleCardHover(card, false));
        });

        // Eventos de actividad
        this.activityItems.forEach(item => {
            item.addEventListener('click', () => this.handleActivityClick(item));
        });

        // Eventos de botones de acción
        this.actionButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        });

        // Eventos del modal
        if (this.modalClose) {
            this.modalClose.addEventListener('click', () => this.closeModal());
        }

        // Cerrar modal con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.closeModal();
            }
        });

        // Cerrar modal haciendo clic fuera
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
    }

    initAnimations() {
        // Animar valores de estadísticas
        this.animateStats();
        
        // Aplicar animaciones escalonadas
        this.applyStaggeredAnimations();
    }

    initNotifications() {
        // Mostrar notificación de bienvenida
        setTimeout(() => {
            this.showNotification(
                'Bienvenido al Panel Administrativo',
                'success',
                4000
            );
        }, 1000);

        // Simular notificaciones periódicas
        setInterval(() => {
            if (Math.random() > 0.7) {
                this.showRandomNotification();
            }
        }, 30000);
    }

    highlightCurrentPage() {
        this.navLinks.forEach(link => {
            link.classList.remove('active');
            const linkHref = link.getAttribute('href');
            if (linkHref === this.currentPage) {
                link.classList.add('active');
            }
        });
    }

    handleNavClick(e) {
        const link = e.currentTarget;
        const allLinks = document.querySelectorAll('.nav-link');
        
        // Remover clase active de todos los enlaces
        allLinks.forEach(l => l.classList.remove('active'));
        
        // Agregar clase active al enlace clickeado
        link.classList.add('active');
        
        // Guardar estado en localStorage
        const page = link.getAttribute('href');
        localStorage.setItem('lastAdminPage', page);
        
        // Efecto visual de clic
        link.style.transform = 'scale(0.95)';
        setTimeout(() => {
            link.style.transform = '';
        }, 150);
    }

    handleCardHover(card, isEntering) {
        if (isEntering) {
            card.style.zIndex = '10';
            card.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.15)';
        } else {
            card.style.zIndex = '';
            card.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.05)';
        }
    }

    handleActivityClick(item) {
        const title = item.querySelector('.activity-title').textContent;
        this.showNotification(`Accediendo a: ${title}`, 'info', 3000);
        
        // Efecto visual
        item.style.backgroundColor = '#f3f4f6';
        setTimeout(() => {
            item.style.backgroundColor = '';
        }, 500);
    }

    animateStats() {
        this.statValues.forEach(stat => {
            const originalText = stat.textContent;
            const cleanValue = originalText.replace(/[^0-9.]/g, '');
            
            if (cleanValue && !isNaN(parseFloat(cleanValue))) {
                const targetValue = parseFloat(cleanValue);
                const hasDecimal = originalText.includes('.');
                
                stat.textContent = '0';
                if (hasDecimal) stat.textContent = '0.0';
                
                let currentValue = 0;
                const increment = targetValue / 60;
                const precision = hasDecimal ? 1 : 0;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    
                    if (currentValue >= targetValue) {
                        stat.textContent = originalText;
                        clearInterval(timer);
                        
                        // Efecto de finalización
                        stat.style.color = '#10b981';
                        setTimeout(() => {
                            stat.style.color = '';
                        }, 500);
                    } else {
                        stat.textContent = hasDecimal 
                            ? currentValue.toFixed(1)
                            : Math.floor(currentValue).toLocaleString();
                    }
                }, 20);
            }
        });
    }

    applyStaggeredAnimations() {
        // Aplicar animaciones escalonadas a los elementos
        const animatedElements = [
            ...this.statCards,
            ...this.activityItems
        ];
        
        animatedElements.forEach((element, index) => {
            element.style.animationDelay = `${(index * 0.1) + 0.3}s`;
            element.style.animationName = 'fadeIn';
            element.style.animationDuration = '0.6s';
            element.style.animationFillMode = 'both';
        });
    }

    showNotification(message, type = 'info', duration = 4000) {
        // Eliminar notificaciones anteriores
        const oldNotifications = document.querySelectorAll('.admin-notification');
        oldNotifications.forEach(notification => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        });

        // Crear nueva notificación
        const notification = document.createElement('div');
        notification.className = `admin-notification ${type}`;
        
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${icons[type] || 'info-circle'}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">${message}</div>
                <div class="notification-time">${this.getCurrentTime()}</div>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Estilos
        notification.style.cssText = `
            position: fixed;
            top: 25px;
            right: 25px;
            background: ${this.getNotificationColor(type)};
            color: white;
            padding: 18px 22px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 15px;
            max-width: 380px;
            border-left: 5px solid ${this.getNotificationBorderColor(type)};
            transition: transform 0.3s ease, opacity 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Evento para cerrar
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.removeNotification(notification));

        // Auto-eliminar
        setTimeout(() => {
            this.removeNotification(notification);
        }, duration);

        return notification;
    }

    getNotificationColor(type) {
        const colors = {
            'success': '#10b981',
            'error': '#ef4444',
            'warning': '#f59e0b',
            'info': '#3b82f6'
        };
        return colors[type] || colors.info;
    }

    getNotificationBorderColor(type) {
        const colors = {
            'success': '#059669',
            'error': '#dc2626',
            'warning': '#d97706',
            'info': '#2563eb'
        };
        return colors[type] || colors.info;
    }

    removeNotification(notification) {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }

    showRandomNotification() {
        const notifications = [
            { message: 'Backup automático completado exitosamente', type: 'success' },
            { message: 'Nuevo usuario registrado en el sistema', type: 'info' },
            { message: 'Actualización de seguridad disponible', type: 'warning' },
            { message: 'Reporte mensual generado automáticamente', type: 'info' }
        ];
        
        const randomNotif = notifications[Math.floor(Math.random() * notifications.length)];
        this.showNotification(randomNotif.message, randomNotif.type, 5000);
    }

    getCurrentTime() {
        const now = new Date();
        return now.toLocaleTimeString('es-CO', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true 
        });
    }

    // Funciones del modal
    showModal() {
        if (this.modal) {
            this.modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    closeModal() {
        if (this.modal) {
            this.modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    saveConfiguration() {
        const systemName = document.getElementById('systemName')?.value;
        const sessionTimeout = document.getElementById('sessionTimeout')?.value;
        const backupFrequency = document.getElementById('backupFrequency')?.value;
        const emailNotifications = document.getElementById('emailNotifications')?.checked;

        // Aquí iría la lógica para guardar en el servidor
        console.log('Guardando configuración:', {
            systemName,
            sessionTimeout,
            backupFrequency,
            emailNotifications
        });

        this.showNotification('Configuración guardada exitosamente', 'success');
        this.closeModal();
    }

    // Función para exportar datos
    exportData(type) {
        this.showNotification(`Exportando datos ${type}...`, 'info');
        
        // Simular exportación
        setTimeout(() => {
            this.showNotification('Datos exportados exitosamente', 'success');
        }, 2000);
    }

    // Función para generar reporte
    generateReport() {
        this.showNotification('Generando reporte del sistema...', 'info');
        
        // Simular generación de reporte
        setTimeout(() => {
            this.showNotification('Reporte generado exitosamente', 'success');
        }, 3000);
    }
}

// Funciones globales para uso en HTML
function mostrarModalConfiguracion() {
    if (window.adminPanel) {
        window.adminPanel.showModal();
    }
}

function cerrarModal() {
    if (window.adminPanel) {
        window.adminPanel.closeModal();
    }
}

function guardarConfiguracion() {
    if (window.adminPanel) {
        window.adminPanel.saveConfiguration();
    }
}

function exportarDatos(tipo) {
    if (window.adminPanel) {
        window.adminPanel.exportData(tipo);
    }
}

function generarReporte() {
    if (window.adminPanel) {
        window.adminPanel.generateReport();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.adminPanel = new AdminPanel();
    
    // Cargar última página visitada
    const lastPage = localStorage.getItem('lastAdminPage');
    if (lastPage) {
        const navLink = document.querySelector(`.nav-link[href="${lastPage}"]`);
        if (navLink) {
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            navLink.classList.add('active');
        }
    }
    
    // Mostrar información de depuración
    console.log('Panel Administrativo - Usuario:', '<?php echo $_SESSION["correo"] ?? "No identificado"; ?>');
    console.log('Panel Administrativo - Rol:', '<?php echo $_SESSION["tipo_usuario"] ?? "No definido"; ?>');
});