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
        confirmModal.className = 'modal-overlay active';
        
        confirmModal.innerHTML = `
            <div class="modal-clave modal-volver">
                <div class="modal-header">
                    <h3>¿Volver al Menú Principal?</h3>
                    <p>Confirmación requerida</p>
                </div>
                <div class="modal-body">
                    <div class="volver-content">
                        <i class="fas fa-arrow-circle-left volver-icon"></i>
                        <p class="volver-message">¿Confirma que desea regresar al menú anterior?</p> 
                        <p class="volver-submessage">Será redirigido(a) al menú de servicios.</p>
                    </div>
                    <div class="modal-buttons">
                        <button class="btn-modal btn-volver" id="confirmVolver">
                            <i class="fas fa-arrow-left"></i> Sí, Volver
                        </button>
                        <button class="btn-modal btn-cancelar" id="cancelVolver">
                            <i class="fas fa-times"></i> Permanecer aquí
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