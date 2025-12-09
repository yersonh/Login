document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    const logoutBtn = document.getElementById('logoutBtn');

    initServiceCards();
    initLogoutButton();

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

        if (statusElement.classList.contains('status-available')) {
            showNotification(`Accediendo a: ${serviceName}`, 'info');
        } else {
            showNotification(`El servicio "${serviceName}" se encuentra en mantenimiento.`, 'error');
        }
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
                        
                        <p style="margin-top: 10px; margin-bottom: 5px;">¿Está seguro que desea cerrar la sesión actual?</p> 
                        
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
});