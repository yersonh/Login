document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    const adminCard = document.getElementById('admin-card');
    const modalClave = document.getElementById('modalClave');
    const inputClave = document.getElementById('inputClave');
    const btnIngresar = document.getElementById('btnIngresarClave');
    const btnCancelar = document.getElementById('btnCancelarClave');
    const errorMessage = document.getElementById('errorMessage');
    const logoutBtn = document.getElementById('logoutBtn');
    
    // Función para mostrar/ocultar contraseña
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('inputClave');
        const toggleButton = document.getElementById('togglePassword');
        
        if (passwordInput && toggleButton) {
            const eyeIcon = toggleButton.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                toggleButton.classList.add('active');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                toggleButton.classList.remove('active');
            }
        }
    }

    initServiceCards();
    initModalEvents();
    initTogglePassword();
    initLogoutButton();

    console.log('Menu Asistente - Usuario:', '<?php echo $_SESSION["correo"] ?? "No identificado"; ?>');
    console.log('Menu Asistente - Rol:', '<?php echo $_SESSION["tipo_usuario"] ?? "No definido"; ?>');

    function initServiceCards() {
        serviceCards.forEach(card => {
            card.addEventListener('click', function() {
                handleServiceClick(this);
            });

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
     * @param {HTMLElement} card 
     */
    function handleServiceClick(card) {
        const serviceName = card.querySelector('.service-name').textContent;
        const statusElement = card.querySelector('.service-status');

        if (card === adminCard && statusElement.classList.contains('status-available')) {
            abrirModalClave();
            return;
        }

        if (statusElement.classList.contains('status-available')) {
            showNotification(`Accediendo a: ${serviceName}`, 'info');
            // Aquí iría la redirección a otros servicios
        } else {
            showNotification(`El servicio "${serviceName}" se encuentra en mantenimiento.`, 'error');
        }
    }
    
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

        inputClave.addEventListener('input', function() {
            if (errorMessage.classList.contains('show')) {
                errorMessage.classList.remove('show');
                errorMessage.textContent = '';
            }
        });
    }
    
    function initTogglePassword() {
        const toggleButton = document.getElementById('togglePassword');
        if (toggleButton) {
            toggleButton.addEventListener('click', togglePasswordVisibility);
        }
        
        // También usar event delegation por si el botón se carga después
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.id === 'togglePassword' || e.target.closest('#togglePassword'))) {
                e.preventDefault();
                togglePasswordVisibility();
            }
        });
    }

    function initLogoutButton() {
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showLogoutConfirmation();
            });
        }
    }
    
    function showLogoutConfirmation() {
        const confirmModal = document.createElement('div');
        confirmModal.className = 'modal-overlay active';
        confirmModal.innerHTML = `
            <div class="modal-clave">
                <div class="modal-header">
                    <h3>¿Cerrar sesión?</h3>
                    <p>Confirmación requerida</p>
                </div>
                <div class="modal-body">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-sign-out-alt" style="font-size: 48px; color: #004a8d; margin-bottom: 15px;"></i>
                        <p>¿Está seguro que desea cerrar la sesión actual?</p>
                        <p style="font-size: 14px; color: #6c757d; margin-top: 10px;">Será redirigido a la página de inicio de sesión.</p>
                    </div>
                    <div class="modal-buttons">
                        <button class="btn-modal btn-ingresar" id="confirmLogout">
                            Sí, cerrar sesión
                        </button>
                        <button class="btn-modal btn-cancelar" id="cancelLogout">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(confirmModal);
        
        // Evento para confirmar cierre de sesión
        document.getElementById('confirmLogout').addEventListener('click', function() {
            window.location.href = '/logout.php';
        });
        ar
        document.getElementById('cancelLogout').addEventListener('click', function() {
            document.body.removeChild(confirmModal);
        });
        
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                document.body.removeChild(confirmModal);
            }
        });
        
        // También cerrar con Escape
        const handleEscape = function(e) {
            if (e.key === 'Escape') {
                document.body.removeChild(confirmModal);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }

    function abrirModalClave() {
        modalClave.classList.add('active');
        inputClave.value = '';
        inputClave.focus();
        errorMessage.classList.remove('show');
        errorMessage.textContent = '';

        document.body.style.overflow = 'hidden';

        const toggleButton = document.getElementById('togglePassword');
        if (toggleButton) {
            const eyeIcon = toggleButton.querySelector('i');
            if (eyeIcon) {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
            toggleButton.classList.remove('active');
            inputClave.type = 'password';
        }
    }
    
    function cerrarModalClave() {
        modalClave.classList.remove('active');
        inputClave.value = '';
        errorMessage.classList.remove('show');
        errorMessage.textContent = '';
        
        document.body.style.overflow = '';

        const toggleButton = document.getElementById('togglePassword');
        if (toggleButton) {
            const eyeIcon = toggleButton.querySelector('i');
            if (eyeIcon) {
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
            toggleButton.classList.remove('active');
        }

        inputClave.type = 'password';
    }
    
    function handleClaveSubmit() {
        const clave = inputClave.value.trim();
        
        if (!clave) {
            mostrarError('Por favor ingrese la clave de autorización.');
            inputClave.focus();
            return;
        }

        if (clave.length < 4) {
            mostrarError('La clave debe tener al menos 4 caracteres.');
            inputClave.focus();
            return;
        }

        btnIngresar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        btnIngresar.disabled = true;

        verificarClaveAdministrador(clave);
    }
    
    /**
     * Verifica la clave de administrador con el servidor
     * @param {string} clave - Clave a verificar
     */
    function verificarClaveAdministrador(clave) {
        // Crear FormData para enviar la clave
        const formData = new FormData();
        formData.append('clave', clave);
        formData.append('tipo_verificacion', 'clave_admin_parametrizacion');
        
        // Hacer petición AJAX al servidor
        fetch('../ajax/verificar_clave.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            btnIngresar.innerHTML = 'Ingresar';
            btnIngresar.disabled = false;
            
            if (data.success) {
                claveCorrectaHandler();
            } else {
                claveIncorrectaHandler(data.message || 'Clave incorrecta.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btnIngresar.innerHTML = 'Ingresar';
            btnIngresar.disabled = false;
            mostrarError('Error de conexión. Intente nuevamente.');
        });
    }

    function claveCorrectaHandler() {
        showNotification('✓ Clave de administrador verificada. Redirigiendo...', 'success');

        inputClave.style.borderColor = '#10b981';
        inputClave.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
        
        setTimeout(() => {
            cerrarModalClave();
            
            // Redirigir a parametrizacion.php
            setTimeout(() => {
                window.location.href = '../views/menuAdministrador.php';
            }, 500);
        }, 1000);
    }
    
    /**
     * Maneja la respuesta cuando la clave es incorrecta
     * @param {string} mensajeError - Mensaje de error específico
     */
    function claveIncorrectaHandler(mensajeError = 'Clave incorrecta.') {
        mostrarError(`❌ ${mensajeError}`);
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

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);

        notification.addEventListener('click', () => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        });
    }

    if (!document.querySelector('#shake-animation')) {
        const style = document.createElement('style');
        style.id = 'shake-animation';
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
});