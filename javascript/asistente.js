// Scripts para el Portal de Asistente

document.addEventListener('DOMContentLoaded', function() {
    // Elementos DOM
    const serviceCards = document.querySelectorAll('.service-card');
    const parametrizacionCard = document.getElementById('parametrizacion-card');
    const modalClave = document.getElementById('modalClave');
    const inputClave = document.getElementById('inputClave');
    const btnIngresar = document.getElementById('btnIngresarClave');
    const btnCancelar = document.getElementById('btnCancelarClave');
    const errorMessage = document.getElementById('errorMessage');
    
    // Inicializar eventos de las tarjetas de servicio
    initServiceCards();
    
    // Inicializar eventos del modal
    initModalEvents();
    
    // Mostrar información de depuración en consola
    console.log('Menu Asistente - Usuario:', '<?php echo $_SESSION["correo"] ?? "No identificado"; ?>');
    console.log('Menu Asistente - Rol:', '<?php echo $_SESSION["tipo_usuario"] ?? "No definido"; ?>');
    
    /**
     * Inicializa los eventos para todas las tarjetas de servicio
     */
    function initServiceCards() {
        serviceCards.forEach(card => {
            card.addEventListener('click', function() {
                handleServiceClick(this);
            });
        });
    }
    
    /**
     * Maneja el clic en una tarjeta de servicio
     * @param {HTMLElement} card - Elemento de la tarjeta clickeada
     */
    function handleServiceClick(card) {
        const serviceName = card.querySelector('.service-name').textContent;
        const statusElement = card.querySelector('.service-status');
        
        // Si es la tarjeta de Parametrización
        if (card === parametrizacionCard && statusElement.classList.contains('status-available')) {
            abrirModalClave();
            return;
        }
        
        // Para otros servicios
        if (statusElement.classList.contains('status-available')) {
            showNotification(`Accediendo a: ${serviceName}`, 'info');
            // Aquí iría la redirección a otros servicios
            // Por ejemplo: window.location.href = `servicio-${serviceName.toLowerCase().replace(/\s+/g, '-')}.php`;
        } else {
            showNotification(`El servicio "${serviceName}" se encuentra en mantenimiento.`, 'error');
        }
    }
    
    /**
     * Inicializa los eventos del modal de clave
     */
    function initModalEvents() {
        // Evento para el botón Ingresar
        btnIngresar.addEventListener('click', handleClaveSubmit);
        
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
    }
    
    /**
     * Abre el modal de clave
     */
    function abrirModalClave() {
        modalClave.classList.add('active');
        inputClave.focus();
        errorMessage.classList.remove('show');
        errorMessage.textContent = '';
    }
    
    /**
     * Cierra el modal de clave
     */
    function cerrarModalClave() {
        modalClave.classList.remove('active');
        inputClave.value = '';
        errorMessage.classList.remove('show');
        errorMessage.textContent = '';
    }
    
    /**
     * Maneja el envío de la clave
     */
    function handleClaveSubmit() {
        const clave = inputClave.value.trim();
        
        if (!clave) {
            mostrarError('Por favor ingrese la clave de autorización.');
            inputClave.focus();
            return;
        }
        
        // Validar la clave
        validarClave(clave);
    }
    
    /**
     * Valida la clave ingresada
     * @param {string} clave - Clave a validar
     */
    function validarClave(clave) {
        // En una implementación real, esto sería una petición AJAX al servidor
        // Por ahora, usamos una clave de ejemplo
        const claveCorrecta = 'admin123';
        
        if (clave === claveCorrecta) {
            // Clave correcta
            claveCorrectaHandler();
        } else {
            // Clave incorrecta
            claveIncorrectaHandler();
        }
    }
    
    /**
     * Maneja la respuesta cuando la clave es correcta
     */
    function claveCorrectaHandler() {
        showNotification('Clave correcta. Redirigiendo...', 'success');
        cerrarModalClave();
        
        // Redirigir después de un breve momento
        setTimeout(() => {
            window.location.href = '../manage/parametrizacion.php';
        }, 1000);
    }
    
    /**
     * Maneja la respuesta cuando la clave es incorrecta
     */
    function claveIncorrectaHandler() {
        mostrarError('Clave incorrecta. Por favor intente nuevamente.');
        inputClave.select();
        inputClave.focus();
        
        // Efecto de vibración en el input
        inputClave.style.animation = 'shake 0.5s';
        setTimeout(() => {
            inputClave.style.animation = '';
        }, 500);
    }
    
    /**
     * Muestra un mensaje de error en el modal
     * @param {string} mensaje - Mensaje de error a mostrar
     */
    function mostrarError(mensaje) {
        errorMessage.textContent = mensaje;
        errorMessage.classList.add('show');
    }
    
    /**
     * Muestra una notificación en pantalla
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de notificación (error, success, info)
     */
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
        
        // Crear la notificación
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
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
        removeNotificationAfterDelay(notification);
    }
    
    /**
     * Elimina la notificación después de un tiempo
     * @param {HTMLElement} notification - Elemento de notificación
     */
    function removeNotificationAfterDelay(notification) {
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
});