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
    
    // Actualizar días restantes cuando cambia la fecha
    const validaHastaInput = document.getElementById('validaHasta');
    if (validaHastaInput) {
        validaHastaInput.addEventListener('change', function() {
            actualizarDiasRestantesUI(this.value);
        });
    }
    
    // Mostrar nombre de archivo seleccionado
    const newLogoInput = document.getElementById('newLogo');
    if (newLogoInput) {
        newLogoInput.addEventListener('change', function(e) {
            const fileName = document.getElementById('fileName');
            if (e.target.files.length > 0) {
                fileName.textContent = e.target.files[0].name;
            } else {
                fileName.textContent = 'Haga clic para seleccionar un archivo';
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

            showSuccess("Configuración cargada correctamente");
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
// Función para actualizar SOLO la configuración del sistema
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
    
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('saveConfigBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    // Obtener SOLO datos del SISTEMA (NO logo)
    const datos = {
        version_sistema: version,
        tipo_licencia: document.getElementById('tipoLicencia').value.trim(),
        valida_hasta: document.getElementById('validaHasta').value,
        desarrollado_por: desarrolladoPor,
        direccion: document.getElementById('direccion').value.trim(),
        correo_contacto: contacto,
        telefono: document.getElementById('telefono').value.trim()
        // NO incluir: entidad, enlace_web, ruta_logo
    };
    
    console.log('Enviando datos del SISTEMA:', datos);
    
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

// =======================================
// Función para actualizar SOLO la configuración del logo
// =======================================
function actualizarConfiguracionLogo() {
    const entidad = document.getElementById('logoAltText').value.trim();
    const enlaceWeb = document.getElementById('logoLink').value.trim();
    const logoFile = document.getElementById('newLogo').files[0];
    
    // Validar que haya al menos un cambio
    if (!logoFile && entidad === '' && enlaceWeb === '') {
        showError('No hay cambios para guardar');
        return;
    }
    
    // Validar URL si se proporciona
    if (enlaceWeb && !isValidUrl(enlaceWeb)) {
        showError('Por favor ingrese una URL válida (ej: https://www.ejemplo.com)');
        return;
    }
    
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('saveLogoBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    // Crear FormData para enviar (permite archivos)
    const formData = new FormData();
    
    if (logoFile) {
        // Validar tamaño del archivo (máx 2MB)
        if (logoFile.size > 2 * 1024 * 1024) {
            showError('El archivo es demasiado grande. Máximo 2MB permitido.');
            restoreButtonState(saveBtn, originalText);
            return;
        }
        
        // Validar tipo de archivo
        const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/gif'];
        if (!validTypes.includes(logoFile.type)) {
            showError('Tipo de archivo no válido. Use PNG, JPG, SVG o GIF.');
            restoreButtonState(saveBtn, originalText);
            return;
        }
        
        formData.append('logo', logoFile);
    }
    
    formData.append('entidad', entidad);
    formData.append('enlace_web', enlaceWeb);
    
    console.log('Enviando datos del LOGO:', { 
        entidad, 
        enlaceWeb, 
        tieneArchivo: !!logoFile 
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
                const footerLogo = document.querySelector(".footer-logo");
                if (footerLogo) {
                    footerLogo.src = data.ruta_logo + '?t=' + timestamp;
                }
            }
            
            // Limpiar campo de archivo
            document.getElementById('newLogo').value = '';
            document.getElementById('fileName').textContent = 'Haga clic para seleccionar un archivo';
            
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
            document.querySelector(".footer-logo").src = data.ruta_logo + '?t=' + timestamp;
            document.getElementById('logoAltText').value = data.entidad || 'Logo Gobernación del Meta';
            document.getElementById('logoLink').value = data.enlace_web || 'https://www.meta.gov.co';
            
            // Limpiar campo de archivo
            document.getElementById('newLogo').value = '';
            
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
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

function restoreButtonState(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}