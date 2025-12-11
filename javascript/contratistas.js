// javascript/aom.js
document.addEventListener('DOMContentLoaded', function() {
    
    initButtons();
    
    console.log('AOM Contratistas - Usuario:', USER_CORREO);
    console.log('AOM Contratistas - Rol:', USER_TIPO);

    function initButtons() {

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
                window.location.href = '../contratistas/agregar_contratista.php';
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

            setTimeout(() => {
                errorMessage.classList.remove('show');
            }, 5000);
        }
    }
    document.getElementById('volverBtn').addEventListener('click', function() {
            window.location.href = 'OpcionesCPS.php';
        });
});