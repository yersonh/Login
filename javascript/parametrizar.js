// Mantener el mismo script que ya tienes...
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar nombre del archivo seleccionado
    const fileInput = document.getElementById('newLogo');
    const fileNameSpan = document.getElementById('fileName');
    
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            fileNameSpan.textContent = this.files[0].name;
            
            // Vista previa de la imagen
            const reader = new FileReader();
            reader.onload = function(e) {
                // Crear vista previa
                const preview = document.createElement('div');
                preview.innerHTML = `
                    <div style="margin-top: 15px; border: 2px solid #28a745; border-radius: 8px; padding: 10px;">
                        <p style="color: #28a745; font-weight: bold; margin-bottom: 10px;">
                            <i class="fas fa-eye"></i> Vista previa del nuevo logo:
                        </p>
                        <img src="${e.target.result}" alt="Vista previa" 
                             style="max-width: 100%; max-height: 100px; object-fit: contain;">
                    </div>
                `;
                
                // Eliminar vista previa anterior si existe
                const oldPreview = document.querySelector('.logo-preview-new');
                if (oldPreview) {
                    oldPreview.remove();
                }
                
                preview.className = 'logo-preview-new';
                document.querySelector('.logo-form').appendChild(preview);
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            fileNameSpan.textContent = 'Haga clic para seleccionar un archivo';
        }
    });
    
    // Validar tamaño del archivo
    fileInput.addEventListener('change', function() {
        if (this.files[0] && this.files[0].size > 2 * 1024 * 1024) {
            showError('El archivo es demasiado grande. El tamaño máximo es 2MB.');
            this.value = '';
            fileNameSpan.textContent = 'Haga clic para seleccionar un archivo';
        }
    });
    
    // Asignar eventos a los botones
    document.getElementById('saveLogoBtn').addEventListener('click', uploadLogo);
    document.getElementById('restoreLogoBtn').addEventListener('click', restoreDefaultLogo);
    document.getElementById('saveConfigBtn').addEventListener('click', saveParameters);
    document.getElementById('resetConfigBtn').addEventListener('click', resetParameters);
});

function uploadLogo() {
    const fileInput = document.getElementById('newLogo');
    const altText = document.getElementById('logoAltText').value;
    const logoLink = document.getElementById('logoLink').value;
    
    if (!fileInput.files[0] && !altText && !logoLink) {
        showError('No hay cambios para guardar.');
        return;
    }
    
    // Simulación de carga (en un caso real, aquí iría una petición AJAX)
    showSuccess('Guardando logo...');
    
    setTimeout(() => {
        // Actualizar vista previa
        if (fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('currentLogo').src = e.target.result;
                document.querySelector('.footer-logo').src = e.target.result;
                
                // Actualizar todos los logos en el sistema
                updateAllLogos(e.target.result);
            };
            reader.readAsDataURL(fileInput.files[0]);
        }
        
        showSuccess('Logo actualizado correctamente.');
    }, 1500);
}

function restoreDefaultLogo() {
    if (confirm('¿Está seguro de restaurar el logo predeterminado? Se perderán los cambios no guardados.')) {
        const defaultLogo = '../../imagenes/logo-default.png';
        
        showSuccess('Restaurando logo predeterminado...');
        
        setTimeout(() => {
            document.getElementById('currentLogo').src = defaultLogo;
            document.querySelector('.footer-logo').src = defaultLogo;
            document.getElementById('logoAltText').value = 'Logo Gobernación del Meta';
            document.getElementById('logoLink').value = 'https://www.meta.gov.co';
            document.getElementById('newLogo').value = '';
            document.getElementById('fileName').textContent = 'Haga clic para seleccionar un archivo';
            
            // Eliminar vista previa
            const preview = document.querySelector('.logo-preview-new');
            if (preview) {
                preview.remove();
            }
            
            showSuccess('Logo predeterminado restaurado correctamente.');
        }, 1000);
    }
}

function saveParameters() {
    // Obtener valores del formulario
    const version = document.getElementById('version').value;
    const tipoLicencia = document.getElementById('tipoLicencia').value;
    const validaHasta = document.getElementById('validaHasta').value;
    const desarrolladoPor = document.getElementById('desarrolladoPor').value;
    const direccion = document.getElementById('direccion').value;
    const contacto = document.getElementById('contacto').value;
    const telefono = document.getElementById('telefono').value;
    
    // Validaciones básicas
    if (!version || !desarrolladoPor || !contacto || !telefono) {
        showError('Por favor complete todos los campos requeridos.');
        return;
    }
    
    // Validar fecha
    if (!validaHasta) {
        showError('Por favor seleccione una fecha de validez.');
        return;
    }
    
    // Simulación de guardado
    showSuccess('Guardando configuración del sistema...');
    
    setTimeout(() => {
        // Aquí normalmente se enviaría una petición AJAX al servidor
        console.log('Datos a guardar:', {
            version,
            tipoLicencia,
            validaHasta,
            desarrolladoPor,
            direccion,
            contacto,
            telefono
        });
        
        showSuccess('Configuración guardada correctamente.');
        
        // Actualizar información visible en la página si es necesario
        updateDisplayedInfo();
    }, 1500);
}

function resetParameters() {
    if (confirm('¿Está seguro de restaurar todos los valores predeterminados? Se perderán todos los cambios no guardados.')) {
        showSuccess('Restaurando valores predeterminados...');
        
        setTimeout(() => {
            // Restaurar valores predeterminados
            document.getElementById('version').value = '1.0.0';
            document.getElementById('tipoLicencia').value = 'evaluacion';
            document.getElementById('validaHasta').value = '2026-03-31';
            document.getElementById('desarrolladoPor').value = 'SisgonTech';
            document.getElementById('direccion').value = 'Carrera 33 # 38-45, Edificio Central, Plazoleta Los Libertadores, Villavicencio, Meta';
            document.getElementById('contacto').value = 'gobernaciondelmeta@meta.gov.co';
            document.getElementById('telefono').value = '(57 -608) 6 818503';
            
            showSuccess('Valores predeterminados restaurados correctamente.');
        }, 1000);
    }
}

function updateAllLogos(newLogoUrl) {
    // Actualizar todos los logos en la página
    const allLogos = document.querySelectorAll('img[src*="logo"]');
    allLogos.forEach(logo => {
        if (logo.id !== 'currentLogo' && !logo.src.includes('placeholder')) {
            logo.src = newLogoUrl;
        }
    });
}

function updateDisplayedInfo() {
    // Actualizar información mostrada en la página si es necesario
    const desarrolladoPor = document.getElementById('desarrolladoPor').value;
    const contacto = document.getElementById('contacto').value;
    const telefono = document.getElementById('telefono').value;
    
    // Actualizar footer si es necesario
    const footerContact = document.querySelectorAll('.contact-info div span');
    if (footerContact.length >= 3) {
        footerContact[1].textContent = contacto; // Correo
        footerContact[0].textContent = telefono; // Teléfono principal
    }
    
    // Actualizar info del desarrollador
    const devName = document.querySelector('.developer-name');
    if (devName) {
        devName.textContent = desarrolladoPor;
    }
}

function showSuccess(message) {
    const alert = document.getElementById('successAlert');
    alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    alert.style.display = 'block';
    
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}

function showError(message) {
    const alert = document.getElementById('errorAlert');
    alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    alert.style.display = 'block';
    
    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}