document.addEventListener('DOMContentLoaded', function () {
    // ==========================
    // CARGAR CONFIGURACIÓN AL INICIAR
    // ==========================
    cargarConfiguracion();
    
    // ==========================
    // EVENT LISTENERS PARA CARGA DE DATOS
    // ==========================
    
    // Actualizar días restantes cuando cambia la fecha
    const validaHastaInput = document.getElementById('validaHasta');
    if (validaHastaInput) {
        validaHastaInput.addEventListener('change', function() {
            actualizarDiasRestantesUI(this.value);
        });
    }
    
    // Mostrar nombre de archivo seleccionado y vista previa
    const newLogoInput = document.getElementById('newLogo');
    if (newLogoInput) {
        newLogoInput.addEventListener('change', function(e) {
            const fileName = document.getElementById('fileName');
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                fileName.textContent = file.name;
                
                // Validar que sea una imagen
                if (file.type.startsWith('image/')) {
                    // Vista previa de imagen
                    previewImage(file);
                } else {
                    showError('Por favor seleccione un archivo de imagen');
                    e.target.value = ''; // Limpiar el input
                    fileName.textContent = 'Haga clic para seleccionar un archivo';
                    hideImagePreview();
                }
            } else {
                fileName.textContent = 'Haga clic para seleccionar un archivo';
                hideImagePreview();
            }
        });
    }
});

// =======================================
// Cargar datos desde la BD
// =======================================
function cargarConfiguracion() {
    // Mostrar indicador de carga
    const successAlert = document.getElementById("successAlert");
    if (successAlert) {
        successAlert.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando configuración...';
        successAlert.style.display = "block";
    }
    
    // Llamar al endpoint
    fetch("../../controllers/parametrizacion_obtener.php")
        .then(res => {
            if (!res.ok) {
                throw new Error(`Error HTTP: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            // Ocultar indicador de carga
            if (successAlert) {
                successAlert.style.display = "none";
            }
            
            if (!data.success) {
                showError(data.error || "No se pudo cargar la configuración.");
                return;
            }

            const config = data.data;
            
            // Guardar configuración actual para comparaciones
            window.currentConfig = config;
            
            // Rellenar todos los campos del formulario con los datos de la BD
            setValueIfExists("version", config.version_sistema);
            setValueIfExists("tipoLicencia", config.tipo_licencia);
            setValueIfExists("validaHasta", config.valida_hasta);
            setValueIfExists("desarrolladoPor", config.desarrollado_por);
            setValueIfExists("direccion", config.direccion);
            setValueIfExists("contacto", config.correo_contacto);
            setValueIfExists("telefono", config.telefono);
            setValueIfExists("logoAltText", config.entidad);
            setValueIfExists("logoLink", config.enlace_web);

            // Actualizar días restantes
            if (config.valida_hasta) {
                actualizarDiasRestantesUI(config.valida_hasta);
            } else if (config.dias_restantes) {
                document.getElementById('diasRestantes').value = config.dias_restantes + ' días';
            }

            // Actualizar logo si existe
            if (config.ruta_logo) {
                const currentLogo = document.getElementById("currentLogo");
                if (currentLogo) {
                    currentLogo.src = config.ruta_logo;
                    currentLogo.alt = config.entidad || "Logo del sistema";
                }
                
                // También actualizar logo del footer si existe
                const footerLogo = document.querySelector(".footer-logo");
                if (footerLogo) {
                    footerLogo.src = config.ruta_logo;
                    footerLogo.alt = config.entidad || "Logo del sistema";
                }
            }
            
            // Asegurar que el logo actual sea visible
            hideImagePreview();
        })
        .catch(error => {
            if (successAlert) {
                successAlert.style.display = "none";
            }
            showError("Error al conectar con el servidor: " + error.message);
            console.error("Error:", error);
        });
}

// =======================================
// VISTA PREVIA DE IMAGEN (CORREGIDA - SOLO UNA IMAGEN VISIBLE)
// =======================================
function previewImage(file) {
    if (!file || !file.type.startsWith('image/')) {
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        // Ocultar el logo actual
        const currentLogo = document.getElementById('currentLogo');
        if (currentLogo) {
            currentLogo.style.display = 'none';
        }
        
        // Crear o actualizar elemento de vista previa
        let preview = document.getElementById('imagePreview');
        
        if (!preview) {
            preview = document.createElement('img');
            preview.id = 'imagePreview';
            preview.style.cssText = `
                max-width: 300px;
                max-height: 120px;
                margin: 0 auto;
                display: block;
                border: 2px solid #004a8d;
                border-radius: 5px;
                padding: 5px;
                background: white;
            `;
            
            // Insertar en el contenedor del logo
            const logoPreviewContainer = document.querySelector('.current-logo .logo-preview');
            if (logoPreviewContainer) {
                logoPreviewContainer.appendChild(preview);
            }
        }
        
        preview.src = e.target.result;
        preview.style.display = 'block';
        preview.alt = 'Vista previa del nuevo logo';
        console.log('Vista previa mostrada correctamente');
    };
    
    reader.readAsDataURL(file);
}

function hideImagePreview() {
    // Mostrar el logo actual
    const currentLogo = document.getElementById('currentLogo');
    if (currentLogo) {
        currentLogo.style.display = 'block';
    }
    
    // Ocultar la vista previa
    const preview = document.getElementById('imagePreview');
    if (preview) {
        preview.style.display = 'none';
    }
}

// =======================================
// LIMPIAR DESPUÉS DE GUARDAR
// =======================================
function cleanupAfterSave() {
    // Limpiar campo de archivo
    document.getElementById('newLogo').value = '';
    document.getElementById('fileName').textContent = 'Haga clic para seleccionar un archivo';
    
    // Limpiar vista previa
    hideImagePreview();
    
    // Asegurar que el logo actual sea visible
    const currentLogo = document.getElementById('currentLogo');
    if (currentLogo) {
        currentLogo.style.display = 'block';
    }
}

// =======================================
// Función para actualizar días restantes en la UI
// =======================================
function actualizarDiasRestantesUI(fechaValidaHasta) {
    const diasElement = document.getElementById('diasRestantes');
    if (!diasElement || !fechaValidaHasta) {
        if (diasElement) diasElement.value = 'N/A';
        return;
    }
    
    const hoy = new Date();
    const validaHasta = new Date(fechaValidaHasta);
    
    // Validar fecha
    if (isNaN(validaHasta.getTime())) {
        diasElement.value = 'Fecha inválida';
        return;
    }
    
    const diferenciaTiempo = validaHasta.getTime() - hoy.getTime();
    const dias = Math.ceil(diferenciaTiempo / (1000 * 3600 * 24));
    
    let textoDias;
    if (dias > 0) {
        textoDias = dias + ' días';
    } else if (dias === 0) {
        textoDias = 'Hoy expira';
    } else {
        textoDias = '0 días (Expirada)';
    }
    
    diasElement.value = textoDias;
}

// =======================================
// Función auxiliar para establecer valores
// =======================================
function setValueIfExists(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.value = value || "";
    }
}

// =======================================
// ALERTAS
// =======================================
function showSuccess(msg) {
    const alert = document.getElementById("successAlert");
    if (alert) {
        alert.innerHTML = `<i class="fas fa-check-circle"></i> ${msg}`;
        alert.style.display = "block";
        setTimeout(() => alert.style.display = "none", 4000);
    }
}

function showError(msg) {
    const alert = document.getElementById("errorAlert");
    if (alert) {
        alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;
        alert.style.display = "block";
        setTimeout(() => alert.style.display = "none", 5000);
    }
}

// =======================================
// FUNCIONES AUXILIARES
// =======================================
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}