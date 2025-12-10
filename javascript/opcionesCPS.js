document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    const aomCard = document.getElementById('aom-card');
    const volvertBtn = document.getElementById('volvertBtn');
    
    initServiceCards();
    initLogoutButton();

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
    function initLogoutButton() {
        if (volvertBtn) {
            volvertBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showVolverConfirmation();
            });
        }
    }
    function showVolverConfirmation() {
    const confirmModal = document.createElement('div');
    // Usamos 'modal-overlay active' para que se muestre inmediatamente
    confirmModal.className = 'modal-overlay active';
    
    // --- Estructura del Modal de Confirmación para Volver ---
    confirmModal.innerHTML = `
        <div class="modal-clave">
            <div class="modal-header">
                <h3>¿Volver al Menú Principal?</h3>
                <p>Confirmación requerida</p>
            </div>
            <div class="modal-body">
                <div style="text-align: center; margin-bottom: 20px;">
                    <i class="fas fa-arrow-circle-left" style="font-size: 48px; color: #004a8d; margin-bottom: 15px;"></i>
                    <p style="margin-top: 10px; margin-bottom: 5px;">¿Confirma que desea regresar al menú anterior?</p> 
                    <p style="font-size: 14px; color: #6c757d; margin-top: 0; margin-bottom: 0;">Será redirigido(a) al menú de servicios.</p>
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

    // --- Lógica de Eventos del Modal ---

    // 1. CONFIRMAR VOLVER (Redirecciona al menú principal)
    document.getElementById('confirmVolver').addEventListener('click', function() {
        // Opción 1: Usa window.history.back() para ir a la página anterior
        // window.history.back(); 
        
        // Opción 2: Redirige directamente a la URL de tu menú principal (más seguro)
        // Ya que estás en views/CPS/OpcionesCPS.php, necesitas subir dos niveles para views/menuAsistente.php
        window.location.href = '../menuAsistente.php'; 
        
        document.body.removeChild(confirmModal);
    });

    // 2. CANCELAR (Cierra el modal)
    const cancelVolver = document.getElementById('cancelVolver');
    cancelVolver.addEventListener('click', function() {
        document.body.removeChild(confirmModal);
    });

    // 3. Cierre al hacer clic fuera del modal (overlay)
    confirmModal.addEventListener('click', function(e) {
        // Solo si el clic fue directamente en el fondo
        if (e.target === confirmModal) {
            document.body.removeChild(confirmModal);
        }
    });

    // 4. Cierre con la tecla ESC
    const handleEscape = function(e) {
        if (e.key === 'Escape') {
            document.body.removeChild(confirmModal);
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}
    function handleServiceClick(card) {
        const serviceName = card.querySelector('.service-name').textContent;
        const statusElement = card.querySelector('.service-status');

        if (card === aomCard && statusElement.classList.contains('status-available')) {
            irAOM();
            return;
        }
        if (statusElement.classList.contains('status-available')) {
            showNotification(`Accediendo a: ${serviceName}`, 'info');
        } else {
            showNotification(`El servicio "${serviceName}" se encuentra en mantenimiento.`, 'error');
        }
    }
    function irAOM() {
    window.location.href = 'menuContratistas.php'; 
    }
});