// Funcionalidad para mostrar/ocultar contraseña
document.addEventListener('DOMContentLoaded', function() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password-field');
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

    // Validación de formulario de login
    const loginForm = document.getElementById('loginForm');
    let isSubmitting = false;

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

        // Si todo está bien, enviar el formulario
        this.submit();
    });

    // "Recuérdame" con localStorage
    const emailInput = document.querySelector('input[name="email"]');
    const rememberCheckbox = document.querySelector('input[name="remember"]');

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

    // Auto-focus en password cuando el email sea válido
    emailInput.addEventListener('input', function() {
        if (this.value.length > 3) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(this.value)) {
                passwordField.focus();
            }
        }
    });

    // Funcionalidad del modal de recuperación
    const modal = document.getElementById('recoveryModal');
    const openBtn = document.getElementById('openRecoveryModal');
    const closeBtn = modal.querySelector('.close');

    openBtn.addEventListener('click', function() {
        modal.style.display = 'block';
    });

    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Validación del formulario de recuperación
    document.getElementById('recoveryForm').addEventListener('submit', function(e) {
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

        // Si todo está bien, enviar el formulario
        this.submit();
    });

    // Funcionalidad del modal de licencia
    const licenseModal = document.getElementById('licenseModal');
    const openLicenseBtn = document.getElementById('openLicenseModal');
    const closeLicenseBtn = licenseModal.querySelector('.close');
    const closeLicenseButton = document.getElementById('closeLicenseModal');

    openLicenseBtn.addEventListener('click', function() {
        licenseModal.style.display = 'block';
    });

    closeLicenseBtn.addEventListener('click', function() {
        licenseModal.style.display = 'none';
    });

    closeLicenseButton.addEventListener('click', function() {
        licenseModal.style.display = 'none';
    });

    // Cerrar modales con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (licenseModal.style.display === 'block') {
                licenseModal.style.display = 'none';
            }
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        }
    });
});