document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    const aomCard = document.getElementById('aom-card');
    
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
    document.getElementById('volverBtn').addEventListener('click', function() {
            window.history.back();
        });
});