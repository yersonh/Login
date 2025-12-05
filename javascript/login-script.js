// Funcionalidad para mostrar/ocultar contraseña
document.addEventListener('DOMContentLoaded', function() {
    // ========== 1. INICIALIZAR MODALES COMO OCULTOS ==========
    const modal = document.getElementById('recoveryModal');
    const licenseModal = document.getElementById('licenseModal');
    
    // Asegurar que los modales estén ocultos al cargar la página
    if (modal) modal.style.display = 'none';
    if (licenseModal) licenseModal.style.display = 'none';
    
    // ========== 2. FUNCIONALIDAD MOSTRAR/OCULTAR CONTRASEÑA ==========
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password-field');
    
    if (togglePassword && passwordField) {
        const toggleIcon = togglePassword.querySelector('i');
        
        togglePassword.addEventListener('click', function() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.className = 'fa-solid fa-eye-slash';
            } else {
                passwordField.type = 'password';
                toggleIcon.className = 'fa-solid fa-eye';
            }
        });
    }

    // ========== 3. VALIDACIÓN FORMULARIO LOGIN ==========
    const loginForm = document.getElementById('loginForm');
    let isSubmitting = false;

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const email = document.querySelector('#loginForm input[name="email"]').value;
            const password = document.querySelector('#loginForm input[name="password"]').value;

            if (!email || !password) {
                alert('Por favor, completa todos los campos.');
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Por favor, ingresa un email válido.');
                return false;
            }

            if (isSubmitting) {
                return;
            }

            isSubmitting = true;
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Ingresando...';

            setTimeout(() => {
                isSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 5000);

            this.submit();
        });
    }

    // ========== 4. "RECUÉRDAME" CON LOCALSTORAGE ==========
    const emailInput = document.querySelector('input[name="email"]');
    const rememberCheckbox = document.querySelector('input[name="remember"]');

    if (emailInput && rememberCheckbox) {
        const savedEmail = localStorage.getItem('remembered_email');
        if (savedEmail && emailInput.value === '') {
            emailInput.value = savedEmail;
            rememberCheckbox.checked = true;
        }

        rememberCheckbox.addEventListener('change', function() {
            if (this.checked && emailInput.value) {
                localStorage.setItem('remembered_email', emailInput.value);
            } else {
                localStorage.removeItem('remembered_email');
            }
        });
    }

    // ========== 5. AUTO-FOCUS EN PASSWORD ==========
    if (emailInput && passwordField) {
        emailInput.addEventListener('input', function() {
            if (this.value.length > 3) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(this.value)) {
                    passwordField.focus();
                }
            }
        });
    }

    // ========== 6. MODAL DE RECUPERACIÓN ==========
    const openBtn = document.getElementById('openRecoveryModal');
    
    if (modal && openBtn) {
        const closeBtn = modal.querySelector('.close');
        
        openBtn.addEventListener('click', function() {
            modal.style.display = 'flex'; // CAMBIADO A 'flex'
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }

        // Cerrar al hacer clic fuera del modal
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Validación del formulario de recuperación
        const recoveryForm = document.getElementById('recoveryForm');
        if (recoveryForm) {
            recoveryForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const email = document.querySelector('#recoveryForm input[name="email"]').value;

                if (!email) {
                    alert('Por favor, ingresa tu correo electrónico.');
                    return false;
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Por favor, ingresa un email válido.');
                    return false;
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 5000);

                this.submit();
            });
        }
    }

    // ========== 7. MODAL DE LICENCIA ==========
    const openLicenseBtn = document.getElementById('openLicenseModal');
    
    if (licenseModal && openLicenseBtn) {
        //const closeLicenseBtn = licenseModal.querySelector('.close');
        //const closeLicenseButton = document.getElementById('closeLicenseModal');
        
        openLicenseBtn.addEventListener('click', function() {
            licenseModal.style.display = 'flex';
        });

        /*if (closeLicenseBtn) {
            closeLicenseBtn.addEventListener('click', function() {
                licenseModal.style.display = 'none';
            });
        }

        if (closeLicenseButton) {
            closeLicenseButton.addEventListener('click', function() {
                licenseModal.style.display = 'none';
            });
        }*/

        // Cerrar al hacer clic fuera del modal de licencia
        window.addEventListener('click', function(event) {
            if (event.target === licenseModal) {
                licenseModal.style.display = 'none';
            }
        });
    }

    // ========== 8. CERRAR MODALES CON TECLA ESC ==========
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (licenseModal && licenseModal.style.display === 'flex') {
                licenseModal.style.display = 'none';
            }
            if (modal && modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        }
    });
});