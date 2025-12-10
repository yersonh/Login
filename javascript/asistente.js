document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    const adminCard = document.getElementById('admin-card');
    const CPSCard = document.getElementById('CPS-card');
    const modalClave = document.getElementById('modalClave');
    const inputClave = document.getElementById('inputClave');
    const btnIngresar = document.getElementById('btnIngresarClave');
    const btnCancelar = document.getElementById('btnCancelarClave');
    const errorMessage = document.getElementById('errorMessage');
    const logoutBtn = document.getElementById('logoutBtn');
    const togglePassword = document.getElementById('togglePassword');
    const iconoPassword = togglePassword.querySelector('i');


    initServiceCards();
    initModalEvents();
    initLogoutButton();
    initPasswordToggle();


    console.log('Menu Asistente - Usuario:', USER_CORREO);
    console.log('Menu Asistente - Rol:', USER_TIPO);


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

    function handleServiceClick(card) {
        const serviceName = card.querySelector('.service-name').textContent;
        const statusElement = card.querySelector('.service-status');

        if (card === adminCard && statusElement.classList.contains('status-available')) {
            abrirModalClave();
            return;
        }

        if (card === CPSCard && statusElement.classList.contains('status-available')) {
            irCPS();
            return;
        }

        if (statusElement.classList.contains('status-available')) {
            showNotification(`Accediendo a: ${serviceName}`, 'info');
        } else {
            showNotification(`El servicio "${serviceName}" se encuentra en mantenimiento.`, 'error');
        }
    }

    function initModalEvents() {
        btnIngresar.addEventListener('click', handleClaveSubmit);
        btnCancelar.addEventListener('click', cerrarModalClave);

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalClave.classList.contains('active')) {
                cerrarModalClave();
            }
            if (e.key === 'Enter' && modalClave.classList.contains('active')) {
                handleClaveSubmit();
            }
        });

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

    function initLogoutButton() {
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showLogoutConfirmation();
            });
        }
    }

    function showLogoutConfirmation() {
        const nombreMostrar = (typeof USER_NOMBRE_COMPLETO !== 'undefined' && USER_NOMBRE_COMPLETO) 
                                ? USER_NOMBRE_COMPLETO 
                                : 'Usuario';
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
                        
                        <p style="margin-bottom: 5px;">
                            <strong style="font-size: 22px; font-weight: bold; color: #333;">${nombreMostrar}</strong>
                        </p>
                        
                        <p style="margin-top: 10px; margin-bottom: 5px;">¿Confirma cerrar la sesión actual?</p> 
                        
                        <p style="font-size: 14px; color: #6c757d; margin-top: 0; margin-bottom: 0;">Será redirigido a la página de inicio de sesión.</p>
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

       document.getElementById('confirmLogout').addEventListener('click', function() {
    fetch('../ajax/logout.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
        // Si tu back y front están en distintos orígenes necesitarás: , credentials: 'include'
    })
    .then(async response => {
        const text = await response.text();
        // Intentar parsear JSON, si falla mostrar el html recibido
        try {
            const data = JSON.parse(text);
            return data;
        } catch (err) {
            console.error('logout AJAX - servidor devolvió algo distinto de JSON:');
            console.log(text); // <<-- aquí verás el HTML / error completo
            throw new Error('Respuesta inválida del servidor, revisar console.log(text)');
        }
    })
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect || '../index.php';
        } else {
            console.error('Error al cerrar sesión:', data.message);
            alert('Hubo un problema al cerrar sesión: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error de logout AJAX:', error);
        alert('Error de red o respuesta inválida. Revisa la consola para más detalles.');
    });
});


        document.getElementById('cancelLogout').addEventListener('click', function() {
            document.body.removeChild(confirmModal);
        });

        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                document.body.removeChild(confirmModal);
            }
        });

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

        inputClave.type = 'password';
    }
    function initPasswordToggle() {
        togglePassword.addEventListener('click', function () {
            const esPassword = inputClave.type === 'password';

            // Cambiar tipo del input
            inputClave.type = esPassword ? 'text' : 'password';

            // Cambiar icono
            iconoPassword.classList.toggle('fa-eye');
            iconoPassword.classList.toggle('fa-eye-slash');

            // Volver a enfocar el input
            inputClave.focus();
        });
    }

    function cerrarModalClave() {
        modalClave.classList.remove('active');
        inputClave.value = '';
        errorMessage.classList.remove('show');
        errorMessage.textContent = '';
        document.body.style.overflow = '';
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

    function verificarClaveAdministrador(clave) {
        const formData = new FormData();
        formData.append('clave', clave);
        formData.append('tipo_verificacion', 'clave_admin_parametrizacion');
        
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

        inputClave.style.borderColor = '#10b981';
        inputClave.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
        
        setTimeout(() => {
            cerrarModalClave();
            setTimeout(() => {
                window.location.href = '../views/menuAdministrador.php';
            }, 500);
        }, 1000);
    }

    function claveIncorrectaHandler(mensajeError = 'Clave incorrecta.') {
        mostrarError(`${mensajeError}`);
        inputClave.select();
        inputClave.focus();

        inputClave.style.borderColor = '#ef4444';
        inputClave.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
        inputClave.style.animation = 'shake 0.5s';
        
        setTimeout(() => {
            inputClave.style.animation = '';
            inputClave.style.borderColor = '#e2e8f0';
            inputClave.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.05)';
        }, 500);
    }

    function mostrarError(mensaje) {
        errorMessage.textContent = mensaje;
        errorMessage.classList.add('show');
        errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function irCPS() {
        window.location.href = 'CPS/OpcionesCPS.php';
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
