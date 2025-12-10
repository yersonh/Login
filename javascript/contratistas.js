// javascript/aom.js
document.addEventListener('DOMContentLoaded', function() {
    const volverBtn = document.getElementById('volverBtn');
    
    initButtons();
    
    console.log('AOM Contratistas - Usuario:', USER_CORREO);
    console.log('AOM Contratistas - Rol:', USER_TIPO);

    function initButtons() {
        // Botón volver
        if (volverBtn) {
            volverBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showVolverConfirmation();
            });
        }
        
        // Opciones del menú
        const opciones = [
            'agregar-contratista',
            'modificar-contratista',
            'parametrizar-obligaciones',
            'dashboard-estadistico',
            'visor-registrados'
        ];
        
        opciones.forEach(opcionId => {
            const elemento = document.getElementById(opcionId);
            if (elemento) {
                elemento.addEventListener('click', function() {
                    handleOptionClick(opcionId);
                });
            }
        });
    }
    
    function handleOptionClick(opcionId) {
        console.log('Opción seleccionada:', opcionId);
        
        // Aquí puedes agregar la lógica para cada opción
        switch(opcionId) {
            case 'agregar-contratista':
                window.location.href = 'agregar_contratista.php';
                break;
            case 'modificar-contratista':
                window.location.href = 'modificar_contratista.php';
                break;
            case 'parametrizar-obligaciones':
                // Mostrar modal de clave para esta opción
                document.getElementById('modalClave').classList.add('active');
                break;
            case 'dashboard-estadistico':
                window.location.href = 'dashboard.php';
                break;
            case 'visor-registrados':
                window.location.href = 'visor_registrados.php';
                break;
        }
    }
    
    function showVolverConfirmation() {
        const confirmModal = document.createElement('div');
        confirmModal.className = 'modal-overlay active';
        
        confirmModal.innerHTML = `
            <div class="modal-clave">
                <div class="modal-header">
                    <h3>¿Volver al Menú Anterior?</h3>
                    <p>Confirmación requerida</p>
                </div>
                <div class="modal-body">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-arrow-circle-left" style="font-size: 48px; color: #004a8d; margin-bottom: 15px;"></i>
                        <p style="margin-top: 10px; margin-bottom: 5px;">¿Confirma que desea regresar al menú anterior?</p> 
                        <p style="font-size: 14px; color: #6c757d; margin-top: 0; margin-bottom: 0;">Será redirigido(a) al menú de servicios CPS.</p>
                    </div>
                    <div class="modal-buttons">
                        <button class="btn-modal btn-ingresar" id="confirmVolver">
                            Sí, Volver
                        </button>
                        <button class="btn-modal btn-cancelar" id="cancelVolver">
                            Permanecer aquí
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(confirmModal);
        
        document.getElementById('confirmVolver').addEventListener('click', function() {
            window.location.href = '../menuAsistente.php';
            document.body.removeChild(confirmModal);
        });

        const cancelVolver = document.getElementById('cancelVolver');
        cancelVolver.addEventListener('click', function() {
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
    
    // Manejo del modal de clave (para parametrizar obligaciones)
    const modalClave = document.getElementById('modalClave');
    const btnIngresarClave = document.getElementById('btnIngresarClave');
    const btnCancelarClave = document.getElementById('btnCancelarClave');
    const togglePassword = document.getElementById('togglePassword');
    const inputClave = document.getElementById('inputClave');
    const errorMessage = document.getElementById('errorMessage');
    
    if (modalClave && btnCancelarClave) {
        btnCancelarClave.addEventListener('click', function() {
            modalClave.classList.remove('active');
            inputClave.value = '';
            errorMessage.classList.remove('show');
        });
        
        // Cerrar modal al hacer clic fuera
        modalClave.addEventListener('click', function(e) {
            if (e.target === modalClave) {
                modalClave.classList.remove('active');
                inputClave.value = '';
                errorMessage.classList.remove('show');
            }
        });
        
        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalClave.classList.contains('active')) {
                modalClave.classList.remove('active');
                inputClave.value = '';
                errorMessage.classList.remove('show');
            }
        });
    }
    
    // Toggle para mostrar/ocultar contraseña
    if (togglePassword && inputClave) {
        togglePassword.addEventListener('click', function() {
            const type = inputClave.getAttribute('type') === 'password' ? 'text' : 'password';
            inputClave.setAttribute('type', type);
            togglePassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Validar clave (ejemplo)
    if (btnIngresarClave && inputClave) {
        btnIngresarClave.addEventListener('click', function() {
            const clave = inputClave.value.trim();
            
            if (!clave) {
                showError('Por favor ingrese la clave');
                return;
            }
            
            // Aquí puedes agregar la validación real de la clave
            // Por ahora es solo un ejemplo
            if (clave === 'admin123') {
                // Clave correcta, redirigir
                window.location.href = 'parametrizar_obligaciones.php';
            } else {
                showError('Clave incorrecta. Intente nuevamente.');
                inputClave.value = '';
                inputClave.focus();
            }
        });
        
        // Permitir enviar con Enter
        inputClave.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                btnIngresarClave.click();
            }
        });
    }
    
    function showError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                errorMessage.classList.remove('show');
            }, 5000);
        }
    }
});