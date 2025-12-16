document.addEventListener('DOMContentLoaded', function () {
    // ==========================
    // 1. INICIALIZACIÓN
    // ==========================
    cargarConfiguracion(); // Cargar datos al abrir la página
    
    // ==========================
    // 2. EVENT LISTENERS (BOTONES Y ACCIONES)
    // ==========================
    
    // --- Botón Guardar: Configuración del SISTEMA ---
    const saveConfigBtn = document.getElementById('saveConfigBtn');
    if (saveConfigBtn) {
        saveConfigBtn.addEventListener('click', actualizarConfiguracionSistema);
    }
    
    // --- Botón Guardar: Configuración del LOGO ---
    const saveLogoBtn = document.getElementById('saveLogoBtn');
    if (saveLogoBtn) {
        saveLogoBtn.addEventListener('click', actualizarConfiguracionLogo);
    }
    
    // --- Botón Restaurar: Logo Predeterminado ---
    const restoreLogoBtn = document.getElementById('restoreLogoBtn');
    if (restoreLogoBtn) {
        restoreLogoBtn.addEventListener('click', restaurarLogoPredeterminado);
    }
    
    // --- Botón Restaurar: Configuración de Fábrica ---
    const resetConfigBtn = document.getElementById('resetConfigBtn');
    if (resetConfigBtn) {
        resetConfigBtn.addEventListener('click', restaurarConfiguracionPredeterminada);
    }

    // --- Input File: Vista previa al seleccionar imagen ---
    const newLogoInput = document.getElementById('newLogo');
    if (newLogoInput) {
        newLogoInput.addEventListener('change', function(e) {
            handleFileSelection(e);
        });
    }

    // --- Modal: Eventos de cierre y confirmación ---
    setupModalEvents();
});

// =======================================
// VARIABLES GLOBALES
// =======================================
let pendingAction = null; // Almacena la función a ejecutar tras confirmar en el modal
let actionData = null;    // Almacena los datos para esa función
window.currentConfig = {}; // Almacena el estado actual de la BD para comparar cambios

// =======================================
// 3. CARGAR DATOS (READ)
// =======================================
function cargarConfiguracion() {
    fetch("../../api/parametrizacion_obtener.php")
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showError(data.error || "No se pudo cargar la configuración.");
                return;
            }

            const config = data.data;
            window.currentConfig = config; // Guardar referencia

            // Llenar formulario de Sistema
            setValue('version', config.version_sistema);
            setValue('tipoLicencia', config.tipo_licencia);
            setValue('validaHasta', config.valida_hasta);
            setValue('desarrolladoPor', config.desarrollado_por);
            setValue('direccion', config.direccion);
            setValue('contacto', config.correo_contacto);
            setValue('telefono', config.telefono);

            // Llenar formulario de Logo
            setValue('logoAltText', config.entidad);
            setValue('logoLink', config.enlace_web);
            setValue('logoNit', config.nit);

            // Actualizar imagen del logo actual
            if (config.ruta_logo) {
                updateLogoImages(config.ruta_logo);
            }
            
            // UI Limpia
            hideImagePreview();
        })
        .catch(error => {
            console.error("Error cargando config:", error);
            showError("Error de conexión al cargar datos.");
        });
}

// =======================================
// 4. LÓGICA DE ACTUALIZACIÓN DEL LOGO
// =======================================
function actualizarConfiguracionLogo() {
    const entidad = document.getElementById('logoAltText').value.trim();
    const nit= document.getElementById('logoNit').value.trim();
    const enlaceWeb = document.getElementById('logoLink').value.trim();
    const logoFile = document.getElementById('newLogo').files[0];

    // Validaciones
    if (!entidad ||!nit||!enlaceWeb) {
        showError('La Entidad y el Enlace Web son campos obligatorios.');
        return;
    }

    // Datos a enviar
    const datos = {
        entidad: entidad,
        nit: nit,
        enlace_web: enlaceWeb,
        logoFile: logoFile || null
    };

    // Detectar cambios para el resumen del modal
    const changes = [];
    const current = window.currentConfig;

    if (entidad !== current.entidad) changes.push({ field: 'Entidad', value: entidad });
    if (nit !== current.nit) changes.push({ field: 'NIT', value: nit });
    if (enlaceWeb !== current.enlace_web) changes.push({ field: 'Web', value: enlaceWeb });
    if (logoFile) changes.push({ field: 'Logo', value: `Nuevo archivo: ${logoFile.name}` });

    if (changes.length === 0) {
        showError('No hay cambios para guardar.');
        return;
    }

    showConfirmationModal('logo', datos, changes);
}

function executeLogoUpdate(datos) {
    const btn = document.getElementById('saveLogoBtn');
    const originalText = setButtonLoading(btn, true);

    const formData = new FormData();
    formData.append('entidad', datos.entidad);
    formData.append('enlace_web', datos.enlace_web);
    formData.append('nit', datos.nit);
    if (datos.logoFile) {
        formData.append('logo', datos.logoFile);
    }

    fetch('../../api/parametrizacion_actualizar_logo.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            
            // Solo refrescar los datos - cargarConfiguracion() ya actualiza el logo
            // No necesitamos data.new_logo_url porque no existe en la respuesta
            
            cleanupLogoForm(); // Limpiar input file y preview
            cargarConfiguracion(); // Refrescar datos globales (línea 139)
        } else {
            showError(data.error);
        }
    })
    .catch(err => showError("Error de conexión: " + err.message))
    .finally(() => setButtonLoading(btn, false, originalText));
}

// =======================================
// 5. LÓGICA DE ACTUALIZACIÓN DEL SISTEMA
// =======================================
function actualizarConfiguracionSistema() {
    // Recolectar datos
    const version_sistema = document.getElementById('version').value.trim();
    const tipo_licencia = document.getElementById('tipoLicencia').value.trim();
    const valida_hasta = document.getElementById('validaHasta').value;
    const desarrollado_por = document.getElementById('desarrolladoPor').value.trim();
    const direccion = document.getElementById('direccion').value.trim();
    const correo_contacto = document.getElementById('contacto').value.trim();
    const telefono = document.getElementById('telefono').value.trim();

    const datos = {
        version_sistema: version_sistema,
        tipo_licencia: tipo_licencia,
        valida_hasta: valida_hasta,
        desarrollado_por: desarrollado_por,
        direccion: direccion,
        correo_contacto: correo_contacto,
        telefono: telefono
    };
    // Validación básica
    if (!datos.version_sistema || !datos.desarrollado_por || !datos.correo_contacto || !datos.telefono || !datos.direccion || !datos.valida_hasta || !datos.tipo_licencia) {
        showError('Complete los campos obligatorios.');
        return;
    }
    const changes = [];
    const current = window.currentConfig;
    if(version_sistema !== current.version_sistema) changes.push({ field: 'Versión del Sistema', value: version_sistema });
    if(tipo_licencia !== current.tipo_licencia) changes.push({ field: 'Tipo de Licencia', value: tipo_licencia });
    if(valida_hasta !== current.valida_hasta) changes.push({ field: 'Válida Hasta', value: valida_hasta });
    if(desarrollado_por !== current.desarrollado_por) changes.push({ field: 'Desarrollado Por', value: desarrollado_por });
    if(direccion !== current.direccion) changes.push({ field: 'Dirección', value: direccion });
    if(correo_contacto !== current.correo_contacto) changes.push({ field: 'Correo de Contacto', value: correo_contacto });
    if(telefono !== current.telefono) changes.push({ field: 'Teléfono', value: telefono });

    if (changes.length === 0) {
        showError('No hay cambios para guardar.');
        return;
    }

    showConfirmationModal('system', datos, changes);
}

function executeSystemUpdate(datos) {
    const btn = document.getElementById('saveConfigBtn');
    const originalText = setButtonLoading(btn, true);

    fetch('../../api/parametrizacion_actualizar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSuccess("Configuración del sistema actualizada.");
            cargarConfiguracion();
        } else {
            showError(data.error);
        }
    })
    .catch(err => showError("Error: " + err.message))
    .finally(() => setButtonLoading(btn, false, originalText));
}

// =======================================
// 6. GESTIÓN DEL MODAL
// =======================================
function setupModalEvents() {
    const modal = document.getElementById('confirmationModal');
    const confirmBtn = document.getElementById('confirmSaveBtn');
    const cancelBtn = document.querySelector('.modal-actions .btn-secondary'); // Asumiendo clase

    if (!modal) return;

    // Click fuera cierra
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    
    // ESC cierra
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    // Botones
    if (confirmBtn) confirmBtn.addEventListener('click', executePendingAction);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
}

function showConfirmationModal(type, data, changes) {
    // Configurar acción
    if (type === 'logo') pendingAction = executeLogoUpdate;
    else if (type === 'system') pendingAction = executeSystemUpdate;
    
    actionData = data;

    // Llenar UI del Modal
    const changesList = document.getElementById('changesList');
    changesList.innerHTML = '';
    
    changes.forEach(c => {
        const li = document.createElement('li');
        li.innerHTML = `<strong>${c.field}:</strong> ${c.value}`;
        changesList.appendChild(li);
    });

    document.getElementById('modalDetails').style.display = changes.length ? 'block' : 'none';
    document.getElementById('confirmationModal').style.display = 'flex';
}

function executePendingAction() {
    if (pendingAction && actionData) pendingAction(actionData);
    closeModal();
}

function closeModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    pendingAction = null;
    actionData = null;
}

// =======================================
// 7. UTILIDADES Y AYUDAS UI
// =======================================

// Manejo de Input File y Preview
function handleFileSelection(e) {
    const file = e.target.files[0];
    const fileNameDisplay = document.getElementById('fileName');
    
    if (file) {
        fileNameDisplay.textContent = file.name;
        if (file.type.startsWith('image/')) {
            previewImage(file);
        } else {
            showError("Por favor seleccione un archivo de imagen válido.");
            e.target.value = ''; // Reset
            hideImagePreview();
        }
    } else {
        fileNameDisplay.textContent = "Seleccionar archivo...";
        hideImagePreview();
    }
}

function previewImage(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('currentLogo').style.display = 'none'; // Ocultar actual
        
        let preview = document.getElementById('imagePreview');
        if (!preview) {
            // Crear elemento si no existe en el DOM (Opcional, depende de tu HTML)
            // Asumimos que existe o lo insertamos dinámicamente
            preview = document.createElement('img');
            preview.id = 'imagePreview';
            document.querySelector('.logo-preview').appendChild(preview);
        }
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function hideImagePreview() {
    const preview = document.getElementById('imagePreview');
    if (preview) preview.style.display = 'none';
    
    const current = document.getElementById('currentLogo');
    if (current) current.style.display = 'block';
}

function cleanupLogoForm() {
    document.getElementById('newLogo').value = '';
    document.getElementById('fileName').textContent = 'Seleccionar archivo...';
    hideImagePreview();
}

// Actualizar todas las imágenes del logo en la página
function updateLogoImages(url) {
    if (!url) return;
    
    const timestamp = new Date().getTime();
    
    // ✅ Si ya viene con / al inicio (ej: /imagenes/logos/logo.jpg)
    // solo agregamos timestamp
    let fullUrl = url + '?t=' + timestamp;
    
    console.log('Actualizando logo con ruta:', fullUrl);
    
    const mainLogo = document.getElementById('currentLogo');
    if (mainLogo) {
        mainLogo.src = fullUrl;
        // Debug
        mainLogo.onload = function() {
            console.log('✅ Logo cargado correctamente:', fullUrl);
        };
        mainLogo.onerror = function() {
            console.error('❌ Error cargando logo:', fullUrl);
            // Si falla, intentar sin la /
            if (url.startsWith('/')) {
                mainLogo.src = url.substring(1) + '?t=' + timestamp;
            }
        };
    }

    const footerLogos = document.querySelectorAll('.footer-logo');
    footerLogos.forEach(img => {
        img.src = fullUrl;
    });
}

// Helpers generales
function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val || '';
}

function setButtonLoading(btn, isLoading, originalText = '') {
    if (isLoading) {
        const text = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        btn.disabled = true;
        return text;
    } else {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

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
        alert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${msg}`;
        alert.style.display = "block";
        setTimeout(() => alert.style.display = "none", 5000);
    }
}

// Funciones placeholder para los botones de Restaurar (puedes implementarlas igual que executeLogoUpdate)
function restaurarLogoPredeterminado() {
    if(!confirm("¿Restaurar logo por defecto?")) return;
    // Fetch a parametrizacion_restaurar_logo.php...
}

function restaurarConfiguracionPredeterminada() {
    if(!confirm("¿Restaurar toda la configuración?")) return;
    // Fetch a parametrizacion_restaurar.php...
}