// javascript/admin.js - Scripts Simplificados para Panel Administrativo

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar panel administrativo
    initAdminPanel();
    
    // Mostrar información de depuración
    console.log('Panel Administrativo - Usuario:', '<?php echo $_SESSION["correo"] ?? "No identificado"; ?>');
    console.log('Panel Administrativo - Rol:', '<?php echo $_SESSION["tipo_usuario"] ?? "No definido"; ?>');
    
    // Inicializar funcionalidades específicas
    initNavigation();
    initReturnAssistantButton();
});

/**
 * Inicializa el panel administrativo
 */
function initAdminPanel() {
    // Resaltar enlace activo basado en la página actual
    highlightActiveLink();
    
    // Aplicar animaciones de entrada
    applyEntranceAnimations();
}

/**
 * Resalta el enlace activo en el menú
 */
function highlightActiveLink() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage) {
            link.classList.add('active');
        }
    });
}

/**
 * Aplica animaciones de entrada a los elementos
 */
function applyEntranceAnimations() {
    const elementsToAnimate = [
        '.main-header',
        '.welcome-content'
    ];
    
    elementsToAnimate.forEach((selector, index) => {
        const element = document.querySelector(selector);
        if (element) {
            element.style.animationDelay = `${index * 0.2}s`;
        }
    });
}

/**
 * Inicializa la navegación
 */
function initNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Guardar la página seleccionada en localStorage
            const page = this.getAttribute('href');
            localStorage.setItem('lastAdminPage', page);
            
            // Añadir efecto visual de clic
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
}

/**
 * Inicializa el botón para volver como asistente
 */
function initReturnAssistantButton() {
    const returnBtn = document.getElementById('return-assistant-btn');
    
    if (returnBtn) {
        returnBtn.addEventListener('click', volverComoAsistente);
        
        // Iniciar timer de inactividad para asistentes
        if (window.location.pathname.includes('menuAdministrador.php')) {
            startInactivityTimer();
        }
    }
}

/**
 * Función para volver como asistente
 */
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

/**
 * Inicia el timer de inactividad para asistentes
 */
function startInactivityTimer() {
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
}

/**
 * Muestra una notificación en el panel
 */
function showAdminNotification(message, type = 'info') {
    const colors = {
        'success': '#10b981',
        'error': '#ef4444',
        'warning': '#f59e0b',
        'info': '#3b82f6'
    };
    
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    // Eliminar notificaciones anteriores
    const oldNotifications = document.querySelectorAll('.admin-notification');
    oldNotifications.forEach(notification => {
        notification.remove();
    });
    
    // Crear nueva notificación
    const notification = document.createElement('div');
    notification.className = `admin-notification ${type}`;
    
    notification.innerHTML = `
        <i class="fas fa-${icons[type] || 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Estilos
    notification.style.cssText = `
        position: fixed;
        top: 25px;
        right: 25px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideInRight 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        max-width: 350px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-left: 4px solid ${type === 'success' ? '#059669' : type === 'error' ? '#dc2626' : '#2563eb'};
    `;
    
    document.body.appendChild(notification);
    
    // Auto-eliminar después de 4 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
    
    // Cerrar al hacer clic
    notification.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    });
}

// Hacer funciones disponibles globalmente
window.volverComoAsistente = volverComoAsistente;
window.showAdminNotification = showAdminNotification;