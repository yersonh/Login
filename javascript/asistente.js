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
            
            // Efecto hover mejorado
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
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
                handleClaveSubmit();
            }
        });
        
        // Cerrar modal haciendo clic fuera del contenido
        modalClave.addEventListener('click', function(e) {
            if (e.target === modalClave) {
                cerrarModalClave();
            }
        });
        
        // Limpiar error al empezar a escribir
        inputClave.addEventListener('input', function() {
            if (errorMessage.classList.contains('show')) {
                errorMessage.classList.remove('show');
                errorMessage.textContent = '';
            }
        });
        
        // Mostrar/ocultar contraseña
        inputClave.addEventListener('keydown', function(e) {
            // Permitir teclas de control
            if (e.key === 'Control' || e.key === 'Alt' || e.key === 'Shift' || 
                e.key === 'Tab' || e.key === 'CapsLock') {
                return;
            }
            
            // Efecto visual al presionar teclas
            if (e.key.length === 1 || e.key === 'Backspace' || e.key === 'Delete') {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            }
        });
    }
    
    /**
     * Abre el modal de clave
     */
    function abrirModalClave() {
        modalClave.classList.add('active');
        inputClave.value = '';
        inputClave.focus();
        errorMessage.classList.remove('show');
        errorMessage.textContent = '';
        
        // Efecto de entrada
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Cierra el modal de clave
     */
    function cerrarModalClave() {
        modalClave.classList.remove('active');
        inputClave.value = '';
        errorMessage.classList.remove('show');
        errorMessage.textContent = '';
        
        // Restaurar scroll
        document.body.style.overflow = '';
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
        
        // Mostrar carga
        btnIngresar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        btnIngresar.disabled = true;
        
        // Simular verificación (en producción sería una petición AJAX)
        setTimeout(() => {
            validarClave(clave);
        }, 800);
    }
    
    /**
     * Valida la clave ingresada
     * @param {string} clave - Clave a validar
     */
    function validarClave(clave) {
        // En una implementación real, esto sería una petición AJAX al servidor
        const claveCorrecta = 'admin123'; // Clave de ejemplo
        
        if (clave === claveCorrecta) {
            // Clave correcta
            claveCorrectaHandler();
        } else {
            // Clave incorrecta
            claveIncorrectaHandler();
        }
        
        // Restaurar botón
        btnIngresar.innerHTML = 'Ingresar';
        btnIngresar.disabled = false;
    }
    
    /**
     * Maneja la respuesta cuando la clave es correcta
     */
    function claveCorrectaHandler() {
        showNotification('✓ Clave correcta. Redirigiendo...', 'success');
        
        // Efecto visual de éxito
        inputClave.style.borderColor = '#10b981';
        inputClave.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
        
        setTimeout(() => {
            cerrarModalClave();
            
            // Redirigir
            setTimeout(() => {
                window.location.href = '../manage/parametrizacion.php';
            }, 500);
        }, 1000);
    }
    
    /**
     * Maneja la respuesta cuando la clave es incorrecta
     */
    function claveIncorrectaHandler() {
        mostrarError('❌ Clave incorrecta. Por favor intente nuevamente.');
        inputClave.select();
        inputClave.focus();
        
        // Efecto de error en el input
        inputClave.style.borderColor = '#ef4444';
        inputClave.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
        inputClave.style.animation = 'shake 0.5s';
        
        setTimeout(() => {
            inputClave.style.animation = '';
            inputClave.style.borderColor = '#e2e8f0';
            inputClave.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.05)';
        }, 500);
    }
    
    /**
     * Muestra un mensaje de error en el modal
     * @param {string} mensaje - Mensaje de error a mostrar
     */
    function mostrarError(mensaje) {
        errorMessage.textContent = mensaje;
        errorMessage.classList.add('show');
        
        // Scroll al mensaje de error
        errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    /**
     * Muestra una notificación en pantalla
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo de notificación (error, success, info)
     */
    function showNotification(message, type = 'info') {
        const colors = {
            'error': '#ef4444',
            'success': '#10b981',
            'info': '#3b82f6'
        };
        
        const icons = {
            'error': 'exclamation-circle',
            'success': 'check-circle',
            'info': 'info-circle'
        };
        
        // Eliminar notificaciones anteriores
        const oldNotifications = document.querySelectorAll('.notification');
        oldNotifications.forEach(notification => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        });
        
        // Crear la notificación
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Agregar ícono
        const icon = document.createElement('i');
        icon.className = `fas fa-${icons[type] || 'info-circle'}`;
        notification.appendChild(icon);
        
        // Agregar texto
        const text = document.createElement('span');
        text.textContent = message;
        notification.appendChild(text);
        
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
            z-index: 1000;
            animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
        
        // Cerrar al hacer clic
        notification.addEventListener('click', () => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        });
    }
});