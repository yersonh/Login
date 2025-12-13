document.addEventListener('DOMContentLoaded', function () {
    // ==========================
    // CARGAR CONFIGURACIÓN AL INICIAR
    // ==========================
    cargarConfiguracion();
    
    // ==========================
    // EVENT LISTENERS
    // ==========================
    
    // Botón para guardar CONFIGURACIÓN DEL SISTEMA
    const saveConfigBtn = document.getElementById('saveConfigBtn');
    if (saveConfigBtn) {
        saveConfigBtn.addEventListener('click', actualizarConfiguracionSistema);
    }
    
    // Botón para guardar CONFIGURACIÓN DEL LOGO
    const saveLogoBtn = document.getElementById('saveLogoBtn');
    if (saveLogoBtn) {
        saveLogoBtn.addEventListener('click', actualizarConfiguracionLogo);
    }
    
    // Botón para restaurar logo predeterminado
    const restoreLogoBtn = document.getElementById('restoreLogoBtn');
    if (restoreLogoBtn) {
        restoreLogoBtn.addEventListener('click', restaurarLogoPredeterminado);
    }
    
    // Botón para restaurar valores predeterminados
    const resetConfigBtn = document.getElementById('resetConfigBtn');
    if (resetConfigBtn) {
        resetConfigBtn.addEventListener('click', restaurarConfiguracionPredeterminada);
    }
    
    // Event listener para el botón de confirmar en el modal
    const confirmSaveBtn = document.getElementById('confirmSaveBtn');
    if (confirmSaveBtn) {
        confirmSaveBtn.addEventListener('click', executePendingAction);
    }
    
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
    
    // Cerrar modal al hacer clic fuera del contenido
    const modal = document.getElementById('confirmationModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    
    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal();
        }
    });
    
    // Configurar validación en tiempo real para URL
    const logoLinkInput = document.getElementById('logoLink');
    if (logoLinkInput) {
        logoLinkInput.addEventListener('input', function() {
            validateUrlInRealTime(this);
        });
    }
});

// =======================================
// VARIABLES GLOBALES PARA EL MODAL
// =======================================
let pendingAction = null; // Guardará la función a ejecutar después de confirmar
let actionData = null;    // Guardará los datos para la acción
let actionType = null;    // 'system' o 'logo'

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
// FUNCIONES DEL MODAL
// =======================================
function showConfirmationModal(type, data, changes) {
    // Guardar la acción pendiente
    pendingAction = type === 'system' ? executeSystemUpdate : executeLogoUpdate;
    actionData = data;
    actionType = type;
    
    // Configurar mensaje del modal
    const modal = document.getElementById('confirmationModal');
    const message = document.getElementById('modalMessage');
    const details = document.getElementById('modalDetails');
    const changesList = document.getElementById('changesList');
    
    // Mensaje según el tipo
    if (type === 'system') {
        message.innerHTML = '<i class="fas fa-cogs"></i> ¿Está seguro de guardar los cambios en la configuración del sistema?';
    } else {
        message.innerHTML = '<i class="fas fa-image"></i> ¿Está seguro de guardar los cambios en la configuración del logo?';
    }
    
    // Mostrar detalles de cambios si existen
    if (changes && changes.length > 0) {
        details.style.display = 'block';
        changesList.innerHTML = '';
        
        changes.forEach(change => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${change.field}:</strong> ${change.value}`;
            changesList.appendChild(li);
        });
    } else {
        details.style.display = 'none';
    }
    
    // Mostrar modal
    modal.style.display = 'flex';
    
    // Enfocar el botón de confirmar
    setTimeout(() => {
        document.getElementById('confirmSaveBtn').focus();
    }, 100);
}

function closeModal() {
    const modal = document.getElementById('confirmationModal');
    modal.style.display = 'none';
    
    // Limpiar variables
    pendingAction = null;
    actionData = null;
    actionType = null;
}

function executePendingAction() {
    if (pendingAction && actionData) {
        pendingAction(actionData);
    }
    closeModal();
}

// =======================================
// FUNCIÓN CON MODAL: actualizarConfiguracionSistema
// =======================================
function actualizarConfiguracionSistema() {
    // Validar campos requeridos
    const version = document.getElementById('version').value.trim();
    const desarrolladoPor = document.getElementById('desarrolladoPor').value.trim();
    const contacto = document.getElementById('contacto').value.trim();
    
    if (!version || !desarrolladoPor || !contacto) {
        showError('Por favor complete los campos requeridos: Versión, Desarrollado por y Contacto');
        return;
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(contacto)) {
        showError('Por favor ingrese un correo electrónico válido');
        document.getElementById('contacto').focus();
        return;
    }
    
    // Obtener datos del formulario
    const datos = {
        version_sistema: version,
        tipo_licencia: document.getElementById('tipoLicencia').value.trim(),
        valida_hasta: document.getElementById('validaHasta').value,
        desarrollado_por: desarrolladoPor,
        direccion: document.getElementById('direccion').value.trim(),
        correo_contacto: contacto,
        telefono: document.getElementById('telefono').value.trim()
    };
    
    // Preparar lista de cambios para mostrar en el modal
    const changes = [];
    const currentConfig = window.currentConfig || {};
    
    // Solo mostrar cambios si tenemos configuración actual
    if (Object.keys(currentConfig).length > 0) {
        if (datos.version_sistema !== (currentConfig.version_sistema || '')) {
            changes.push({ 
                field: 'Versión', 
                value: `${currentConfig.version_sistema || 'N/A'} → ${datos.version_sistema}` 
            });
        }
        if (datos.desarrollado_por !== (currentConfig.desarrollado_por || '')) {
            changes.push({ 
                field: 'Desarrollado por', 
                value: `${currentConfig.desarrollado_por || 'N/A'} → ${datos.desarrollado_por}` 
            });
        }
        if (datos.tipo_licencia !== (currentConfig.tipo_licencia || '')) {
            changes.push({ 
                field: 'Tipo de licencia', 
                value: `${currentConfig.tipo_licencia || 'N/A'} → ${datos.tipo_licencia}` 
            });
        }
        if (datos.valida_hasta !== (currentConfig.valida_hasta || '')) {
            changes.push({ 
                field: 'Válida hasta', 
                value: `${currentConfig.valida_hasta || 'N/A'} → ${datos.valida_hasta || 'No definida'}` 
            });
        }
        if (datos.correo_contacto !== (currentConfig.correo_contacto || '')) {
            changes.push({ 
                field: 'Correo de contacto', 
                value: `${currentConfig.correo_contacto || 'N/A'} → ${datos.correo_contacto}` 
            });
        }
        if (datos.telefono !== (currentConfig.telefono || '')) {
            changes.push({ 
                field: 'Teléfono', 
                value: `${currentConfig.telefono || 'N/A'} → ${datos.telefono}` 
            });
        }
    }
    
    // Mostrar modal de confirmación
    showConfirmationModal('system', datos, changes);
}

// =======================================
// FUNCIÓN MEJORADA: actualizarConfiguracionLogo
// =======================================
function actualizarConfiguracionLogo() {
    const entidad = document.getElementById('logoAltText').value.trim();
    const enlaceWeb = document.getElementById('logoLink').value.trim();
    const logoFile = document.getElementById('newLogo').files[0];
    
    // Obtener valores actuales para comparación
    const currentConfig = window.currentConfig || {};
    const currentEntidad = currentConfig.entidad || '';
    const currentEnlaceWeb = currentConfig.enlace_web || '';
    
    console.log('Datos actuales:', {
        entidad, 
        enlaceWeb, 
        tieneLogo: !!logoFile,
        currentEntidad,
        currentEnlaceWeb
    });
    
    // Validar que haya al menos un cambio REAL
    const entidadChanged = entidad !== currentEntidad;
    const enlaceWebChanged = enlaceWeb !== currentEnlaceWeb;
    const hasFile = !!logoFile;
    
    const hasChanges = entidadChanged || enlaceWebChanged || hasFile;
    
    if (!hasChanges) {
        showError('No hay cambios para guardar');
        return;
    }
    
    // Validar URL SOLO si se proporciona y es diferente del actual
    if (enlaceWeb && enlaceWebChanged) {
        if (!isValidUrlImproved(enlaceWeb)) {
            showError('Por favor ingrese una URL válida (ej: ejemplo.com o https://ejemplo.com)');
            return;
        }
    }
    
    // Validar archivo si se proporciona
    if (logoFile) {
        // Validar tamaño del archivo (máx 2MB)
        if (logoFile.size > 2 * 1024 * 1024) {
            showError('El archivo es demasiado grande. Máximo 2MB permitido.');
            return;
        }
        
        // Validar tipo de archivo
        const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/gif'];
        if (!validTypes.includes(logoFile.type)) {
            showError('Tipo de archivo no válido. Use PNG, JPG, SVG o GIF.');
            return;
        }
    }
    
    // Preparar datos (mantener valores actuales si no hay cambios)
    const datos = {
        entidad: entidadChanged ? entidad : currentEntidad,
        enlace_web: enlaceWebChanged ? enlaceWeb : currentEnlaceWeb,
        logoFile: hasFile ? logoFile : null
    };
    
    // Preparar lista de cambios
    const changes = [];
    
    if (entidadChanged) {
        changes.push({ 
            field: 'Nombre entidad', 
            value: `${currentEntidad || '(vacío)'} → ${entidad || '(vacío)'}` 
        });
    }
    
    if (enlaceWebChanged) {
        changes.push({ 
            field: 'Website', 
            value: `${currentEnlaceWeb || '(vacío)'} → ${enlaceWeb || '(vacío)'}` 
        });
    }
    
    if (hasFile) {
        changes.push({ 
            field: 'Logo', 
            value: `Nuevo archivo: ${logoFile.name} (${formatFileSize(logoFile.size)})` 
        });
    }
    
    console.log('Cambios detectados:', changes);
    
    // Mostrar modal de confirmación
    showConfirmationModal('logo', datos, changes);
}

// =======================================
// FUNCIONES DE EJECUCIÓN (llamadas después de confirmar)
// =======================================
function executeSystemUpdate(datos) {
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('saveConfigBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    console.log('Ejecutando actualización del sistema:', datos);
    
    // Enviar datos al servidor
    fetch('../../controllers/parametrizacion_actualizar.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess('Configuración del sistema actualizada correctamente');
            
            // Actualizar días restantes en la interfaz
            if (datos.valida_hasta) {
                actualizarDiasRestantesUI(datos.valida_hasta);
            }
            
            // Recargar los datos desde el servidor
            setTimeout(cargarConfiguracion, 1000);
        } else {
            showError(data.error || 'Error al actualizar la configuración');
        }
    })
    .catch(error => {
        showError('Error de conexión: ' + error.message);
        console.error('Error:', error);
    })
    .finally(() => {
        // Restaurar botón
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function executeLogoUpdate(datos) {
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('saveLogoBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    // Crear FormData para enviar (permite archivos)
    const formData = new FormData();
    
    if (datos.logoFile) {
        formData.append('logo', datos.logoFile);
    }
    
    formData.append('entidad', datos.entidad);
    formData.append('enlace_web', datos.enlace_web);
    
    console.log('Ejecutando actualización del logo:', { 
        entidad: datos.entidad, 
        enlaceWeb: datos.enlace_web, 
        tieneArchivo: !!datos.logoFile 
    });
    
    // Enviar al servidor
    fetch('../../controllers/parametrizacion_actualizar_logo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess('Configuración del logo actualizada correctamente');
            
            // Actualizar vista del logo si hay nueva imagen
            if (data.ruta_logo) {
                const timestamp = new Date().getTime();
                document.getElementById('currentLogo').src = data.ruta_logo + '?t=' + timestamp;
                document.getElementById('currentLogo').style.display = 'block';
                const footerLogo = document.querySelector(".footer-logo");
                if (footerLogo) {
                    footerLogo.src = data.ruta_logo + '?t=' + timestamp;
                }
            }
            
            // Limpiar campo de archivo y vista previa
            cleanupAfterSave();
            
            // Recargar configuración completa
            setTimeout(cargarConfiguracion, 1000);
        } else {
            showError(data.error || 'Error al actualizar el logo');
        }
    })
    .catch(error => {
        showError('Error de conexión: ' + error.message);
        console.error('Error:', error);
    })
    .finally(() => {
        restoreButtonState(saveBtn, originalText);
    });
}

// =======================================
// Función para restaurar logo predeterminado
// =======================================
function restaurarLogoPredeterminado() {
    if (!confirm('¿Restaurar el logo predeterminado del sistema? Esto eliminará el logo actual.')) {
        return;
    }
    
    const saveBtn = document.getElementById('restoreLogoBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restaurando...';
    saveBtn.disabled = true;
    
    fetch('../../controllers/parametrizacion_restaurar_logo.php', {
        method: 'POST'
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess('Logo restaurado correctamente');
            
            // Actualizar vista
            const timestamp = new Date().getTime();
            document.getElementById('currentLogo').src = data.ruta_logo + '?t=' + timestamp;
            document.getElementById('currentLogo').style.display = 'block';
            document.querySelector(".footer-logo").src = data.ruta_logo + '?t=' + timestamp;
            document.getElementById('logoAltText').value = data.entidad || 'Logo Gobernación del Meta';
            document.getElementById('logoLink').value = data.enlace_web || 'https://www.meta.gov.co';
            
            // Limpiar campo de archivo y vista previa
            cleanupAfterSave();
            
            // Recargar configuración
            setTimeout(cargarConfiguracion, 1000);
        } else {
            showError(data.error || 'Error al restaurar el logo');
        }
    })
    .catch(error => {
        showError('Error de conexión: ' + error.message);
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// =======================================
// Función para restaurar configuración predeterminada
// =======================================
function restaurarConfiguracionPredeterminada() {
    if (!confirm('¿Está seguro de restaurar todos los valores predeterminados? Esto afectará toda la configuración del sistema.')) {
        return;
    }
    
    const saveBtn = document.getElementById('resetConfigBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restaurando...';
    saveBtn.disabled = true;
    
    fetch('../../controllers/parametrizacion_restaurar.php', {
        method: 'POST'
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`Error HTTP: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.success) {
            showSuccess('Configuración restaurada correctamente');
            
            // Recargar toda la configuración
            cargarConfiguracion();
            
            // Actualizar vista del logo también
            if (data.ruta_logo) {
                const timestamp = new Date().getTime();
                document.getElementById('currentLogo').src = data.ruta_logo + '?t=' + timestamp;
                document.querySelector(".footer-logo").src = data.ruta_logo + '?t=' + timestamp;
            }
        } else {
            showError(data.error || 'Error al restaurar la configuración');
        }
    })
    .catch(error => {
        showError('Error de conexión: ' + error.message);
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// =======================================
// FUNCIONES DE VALIDACIÓN MEJORADAS
// =======================================
function isValidUrlImproved(string) {
    if (!string || string.trim() === '') {
        return false;
    }
    
    try {
        // Agregar https:// si no tiene protocolo
        let urlString = string.trim();
        if (!urlString.startsWith('http://') && !urlString.startsWith('https://')) {
            urlString = 'https://' + urlString;
        }
        
        const url = new URL(urlString);
        
        // Validar que tenga un hostname válido
        const hostname = url.hostname;
        if (!hostname || hostname.length < 3) {
            return false;
        }
        
        // Validar formato básico de dominio
        const domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)+$/;
        return domainRegex.test(hostname);
    } catch (_) {
        return false;
    }
}

function validateUrlInRealTime(input) {
    const url = input.value.trim();
    
    if (url === '') {
        input.style.borderColor = '';
        input.style.borderWidth = '';
        return;
    }
    
    if (isValidUrlImproved(url)) {
        input.style.borderColor = '#28a745';
        input.style.borderWidth = '2px';
    } else {
        input.style.borderColor = '#dc3545';
        input.style.borderWidth = '2px';
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
function isValidUrl(string) {
    // Mantener la antigua para compatibilidad
    return isValidUrlImproved(string);
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function restoreButtonState(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}