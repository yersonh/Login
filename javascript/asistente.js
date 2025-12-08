document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    const adminCard = document.getElementById('admin-card');
    const modalClave = document.getElementById('modalClave');
    const inputClave = document.getElementById('inputClave');
    const btnIngresar = document.getElementById('btnIngresarClave');
    const btnCancelar = document.getElementById('btnCancelarClave');
    const errorMessage = document.getElementById('errorMessage');
    
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

    // Inicializar todas las funciones
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
            
            // Redirigir a menuAdministrador.php
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
    
    /**
     * ====================================================
     * FUNCIONES DE CIERRE DE SESIÓN
     * ====================================================
     */
    
    /**
     * Inicializa el botón de cerrar sesión
     */
    function initLogoutButton() {
        const logoutBtn = document.getElementById('logoutBtn');
        
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showLogoutConfirmation();
            });
            
            console.log('Botón de cerrar sesión inicializado correctamente');
        } else {
            console.error('ERROR: No se encontró el botón de cerrar sesión con id="logoutBtn"');
        }
    }
    
    /**
     * Muestra la confirmación para cerrar sesión
     */
    function showLogoutConfirmation() {
        // Crear modal de confirmación
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'modal-overlay';
        modalOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2001;
            animation: fadeIn 0.3s ease;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-clave';
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-top: 4px solid #ef4444;
        `;
        
        modalContent.innerHTML = `
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                <h3 style="color: white; margin-bottom: 8px; font-size: 22px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-sign-out-alt" style="font-size: 24px;"></i>
                    Cerrar sesión
                </h3>
                <p style="color: rgba(255, 255, 255, 0.9); margin-bottom: 25px; font-size: 15px; line-height: 1.5;">
                    ¿Está seguro que desea salir del sistema?<br>
                    <small style="color: rgba(255, 255, 255, 0.7); font-size: 13px;">Se eliminará su sesión activa y los datos temporales.</small>
                </p>
            </div>
            <div class="modal-body">
                <div style="background: #fef2f2; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 3px solid #ef4444;">
                    <p style="margin: 0; color: #991b1b; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Si tiene la opción "Recordar mi sesión" activada, deberá volver a iniciar sesión.
                    </p>
                </div>
                <div class="modal-buttons" style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 25px;">
                    <button class="btn-modal btn-cancelar" id="confirmCancelLogout" 
                        style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 12px 25px; border-radius: 8px; 
                               font-weight: 500; cursor: pointer; transition: all 0.2s; font-size: 14px; flex: 1;">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button class="btn-modal btn-logout" id="confirmLogout" 
                        style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; border: none; padding: 12px 25px; 
                               border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 14px; flex: 1;">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </button>
                </div>
            </div>
        `;
        
        modalOverlay.appendChild(modalContent);
        document.body.appendChild(modalOverlay);
        document.body.style.overflow = 'hidden';
        
        // Agregar animaciones CSS si no existen
        if (!document.querySelector('#logout-animations')) {
            const style = document.createElement('style');
            style.id = 'logout-animations';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes slideUp {
                    from { transform: translateY(30px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
                
                .btn-modal {
                    transition: all 0.2s !important;
                }
                
                .btn-modal:hover {
                    transform: translateY(-2px) !important;
                    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15) !important;
                }
                
                .btn-logout:hover {
                    background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
                    box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3) !important;
                }
                
                .btn-cancelar:hover {
                    background: #e2e8f0 !important;
                    border-color: #cbd5e1 !important;
                }
            `;
            document.head.appendChild(style);
        }
        
        // Event listeners para los botones del modal
        const confirmLogoutBtn = document.getElementById('confirmLogout');
        const cancelLogoutBtn = document.getElementById('confirmCancelLogout');
        
        confirmLogoutBtn.addEventListener('click', performLogout);
        cancelLogoutBtn.addEventListener('click', function() {
            closeLogoutModal();
        });
        
        // Cerrar modal al hacer clic fuera
        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeLogoutModal();
            }
        });
        
        // Cerrar modal con tecla Escape
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
        function closeLogoutModal() {
            if (modalOverlay.parentNode) {
                document.body.removeChild(modalOverlay);
            }
            document.body.style.overflow = '';
            document.removeEventListener('keydown', escapeHandler);
        }
    }
    
    /**
     * Ejecuta el cierre de sesión via AJAX
     */
    function performLogout() {
        const logoutBtn = document.getElementById('confirmLogout');
        const originalContent = logoutBtn.innerHTML;
        
        // Mostrar loading
        logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerrando sesión...';
        logoutBtn.disabled = true;
        
        // Hacer petición AJAX
        fetch('../ajax/logout.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'logout=true'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mostrar notificación de éxito
                showNotification('✓ Sesión cerrada correctamente', 'success');
                
                // Limpiar localStorage/sessionStorage si es necesario
                localStorage.removeItem('session_activity');
                sessionStorage.clear();
                
                // Redirigir después de un breve delay
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 1200);
            } else {
                // Mostrar error específico
                logoutBtn.innerHTML = originalContent;
                logoutBtn.disabled = false;
                
                const errorMsg = data.message || 'Error al cerrar sesión';
                showNotification(`❌ ${errorMsg}`, 'error');
                
                // Si hay error de CSRF, recargar la página
                if (data.csrf_error) {
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            }
        })
        .catch(error => {
            console.error('Error en logout:', error);
            logoutBtn.innerHTML = originalContent;
            logoutBtn.disabled = false;
            
            showNotification('❌ Error de conexión. Intentando redirección directa...', 'error');
            
            // Como fallback, intentar redirección directa
            setTimeout(() => {
                window.location.href = '../index.php?logout=direct';
            }, 1500);
        });
    }
});